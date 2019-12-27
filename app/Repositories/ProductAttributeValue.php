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
            if (!empty($entity->get('attribute')->get('locale'))) {
                throw new BadRequest("Locale attribute can't be linked");
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
        $this->createLocaleAttributes($entity);

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
        if (empty($entity->get('productFamilyAttributeId')) && empty($entity->get('locale'))) {
            $localeAttributes = $entity->get('attribute')->get('attributes');
            if (count($localeAttributes) > 0) {
                foreach ($localeAttributes as $localeAttribute) {
                    $newEntity = $this->get();
                    $newEntity->set($entity->toArray());
                    $newEntity->id = Util::generateId();
                    $newEntity->set('attributeId', $localeAttribute->get('id'));
                    $newEntity->set('locale', $localeAttribute->get('locale'));
                    $this->getEntityManager()->saveEntity($newEntity, ['skipValidation' => true]);
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
}
