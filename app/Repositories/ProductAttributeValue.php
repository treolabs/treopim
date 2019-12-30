<?php
/**
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Pim\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Treo\Core\Utils\Util;

/**
 * Class ProductAttributeValue
 *
 * @author r.ratsun@treolabs.com
 */
class ProductAttributeValue extends Base
{
    /**
     * @param string $id
     */
    public function removeCollectionByProductFamilyAttribute(string $id)
    {
        $this
            ->where(['productFamilyAttributeId' => $id])
            ->removeCollection(['skipProductAttributeValueHook' => true]);
    }

    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        if (empty($options['skipValidation'])) {
            if ($entity->isNew() && !empty($entity->get('attribute')->get('locale'))) {
                throw new BadRequest("Locale attribute can't be linked");
            }
            if ($entity->get('attributeType') == 'enum' && !empty($entity->get('locale'))) {
                throw new BadRequest("Locale enum attribute can't be changed");
            }
            if ($entity->get('attributeType') == 'multiEnum' && !empty($entity->get('locale'))) {
                throw new BadRequest("Locale multiEnum attribute can't be changed");
            }
        }

        $entity->set('attributeType', $entity->get('attribute')->get('type'));

        parent::beforeSave($entity, $options);
    }

    /**
     * @inheritDoc
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        // create locales attributes
        $this->createLocaleAttributes($entity);

        if ($entity->isAttributeChanged('value') && $entity->get('attribute')->get('isMultilang')) {
            // update locales enum fields
            if ($entity->get('attributeType') == 'enum') {
                $this->updateLocalesEnum($entity);
            }

            // update locales multiEnum fields
            if ($entity->get('attributeType') == 'multiEnum') {
                $this->updateLocalesMultiEnum($entity);
            }
        }

        parent::afterSave($entity, $options);
    }

    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    public function beforeRemove(Entity $entity, array $options = [])
    {
        if (empty($options['skipProductAttributeValueHook']) && !empty($entity->get('locale'))) {
            throw new BadRequest("Locale attribute can't be deleted");
        }

        parent::beforeRemove($entity, $options);
    }

    /**
     * @inheritDoc
     */
    public function afterRemove(Entity $entity, array $options = [])
    {
        $this->deleteLocaleAttributes($entity);

        parent::afterRemove($entity, $options);
    }

    /**
     * @param Entity $entity
     */
    protected function createLocaleAttributes(Entity $entity): void
    {
        if ($entity->isNew() && empty($entity->get('productFamilyAttributeId')) && empty($entity->get('locale'))) {
            $localeAttributes = $entity->get('attribute')->get('attributes');
            if (count($localeAttributes) > 0) {
                foreach ($localeAttributes as $localeAttribute) {
                    $newEntity = $this->get();
                    $newEntity->set($entity->toArray());
                    $newEntity->id = Util::generateId();
                    $newEntity->set('attributeId', $localeAttribute->get('id'));
                    $newEntity->set('locale', $localeAttribute->get('locale'));
                    $this->getEntityManager()->saveEntity($newEntity, ['skipValidation' => true]);

                    if ($entity->get('scope') == 'Channel') {
                        $channels = $entity->get('channels');
                        if (count($channels) > 0) {
                            foreach ($channels as $channel) {
                                $this->relate($newEntity, 'channels', $channel);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Entity $entity
     */
    protected function deleteLocaleAttributes(Entity $entity): void
    {
        /** @var string $productId */
        $productId = $entity->get('productId');

        /** @var string $attributeId */
        $attributeId = $entity->get('attributeId');

        // remove locales attributes
        $this->getEntityManager()->nativeQuery(
            "UPDATE product_attribute_value SET deleted=1 WHERE attribute_id IN (SELECT id FROM attribute WHERE parent_id='$attributeId' AND deleted=0) AND deleted=0 AND product_id='$productId' AND locale IS NOT NULL"
        );
    }

    /**
     * @param Entity $entity
     */
    protected function updateLocalesEnum(Entity $entity): void
    {
        if (!empty($attribute = $entity->get('attribute')) && !empty($localeAttributes = $attribute->get('attributes')->toArray())) {
            /** @var int $key */
            $key = array_search($entity->get('value'), $attribute->get('typeValue'));

            if (is_int($key)) {
                /** @var string $productId */
                $productId = $entity->get('productId');

                foreach ($localeAttributes as $localeAttribute) {
                    if (isset($localeAttribute['typeValue'][$key])) {
                        $value = $localeAttribute['typeValue'][$key];
                        $this->getEntityManager()->nativeQuery(
                            "UPDATE product_attribute_value SET value='$value' WHERE attribute_id='{$localeAttribute['id']}' AND product_id='$productId' AND deleted=0"
                        );
                    }
                }
            }
        }
    }

    /**
     * @param Entity $entity
     */
    protected function updateLocalesMultiEnum(Entity $entity): void
    {
        if (!empty($attribute = $entity->get('attribute')) && !empty($localeAttributes = $attribute->get('attributes')->toArray())) {
            $keys = [];
            foreach (Json::decode($entity->get('value'), true) as $value) {
                $key = array_search($value, $attribute->get('typeValue'));
                if (is_int($key)) {
                    $keys[] = $key;
                }
            }

            if (!empty($keys)) {
                /** @var string $productId */
                $productId = $entity->get('productId');

                foreach ($localeAttributes as $localeAttribute) {
                    $value = [];
                    foreach ($keys as $key) {
                        if (isset($localeAttribute['typeValue'][$key])) {
                            $value[] = $localeAttribute['typeValue'][$key];
                        }
                    }

                    if (!empty($value)) {
                        $value = Json::encode($value);
                        $this->getEntityManager()->nativeQuery(
                            "UPDATE product_attribute_value SET value='$value' WHERE attribute_id='{$localeAttribute['id']}' AND product_id='$productId' AND deleted=0"
                        );
                    }
                }
            }
        }
    }
}
