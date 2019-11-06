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

/**
 * Class ProductFamilyAttribute
 *
 * @author r.ratsun@treolabs.com
 */
class ProductFamilyAttribute extends Base
{
    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        // exit
        if (!empty($options['skipValidation'])) {
            return true;
        }

        // is valid
        if (empty($productFamily = $entity->get('productFamily')) || empty($attribute = $entity->get('attribute'))) {
            throw new BadRequest($this->exception('ProductFamily and Attribute cannot be empty'));
        }

        // is unique
        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }

        // clearing channels ids
        if ($entity->get('scope') == 'Global') {
            $entity->set('channelsIds', []);
        }
    }

    /**
     * @inheritDoc
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        // update product attribute values
        $this->updateProductAttributeValues($entity);
    }

    /**
     * @inheritDoc
     */
    public function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->removeCollectionByProductFamilyAttribute($entity->get('id'));
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        // prepare count
        $item = null;

        if ($entity->get('scope') == 'Global') {
            $item = $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->select(['id'])
                ->where(
                    [
                        'id!='            => $entity->get('id'),
                        'productFamilyId' => $entity->get('productFamilyId'),
                        'attributeId'     => $entity->get('attributeId'),
                        'scope'           => 'Global',
                    ]
                )
                ->findOne();
        } elseif ($entity->get('scope') == 'Channel') {
            $item = $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->distinct()
                ->select(['id'])
                ->join('channels')
                ->where(
                    [
                        'id!='            => $entity->get('id'),
                        'productFamilyId' => $entity->get('productFamilyId'),
                        'attributeId'     => $entity->get('attributeId'),
                        'scope'           => 'Channel',
                        'channels.id'     => $entity->get('channelsIds'),
                    ]
                )
                ->findOne();
        }

        return empty($item);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function updateProductAttributeValues(Entity $entity): bool
    {
        // get products ids
        if (empty($productsIds = $entity->get('productFamily')->get('productsIds'))) {
            return true;
        }

        // prepare repository
        $repository = $this->getEntityManager()->getRepository('ProductAttributeValue');

        // get exists
        $exists = $repository
            ->where(['productId' => $productsIds, 'attributeId' => $entity->get('attributeId')])
            ->find();

        // prepare channels for entity
        $channels = array_column($entity->get('channels')->toArray(), 'id');
        sort($channels);

        foreach ($productsIds as $productId) {
            // prepare product attribute value
            $productAttributeValue = null;

            // find related
            foreach ($exists as $exist) {
                if ($exist->get('productFamilyAttributeId') == $entity->get('id') && $productId == $exist->get('productId')) {
                    $productAttributeValue = $exist;
                    break;
                }
            }

            // find not related
            if (empty($productAttributeValue)) {
                foreach ($exists as $exist) {
                    // prepare channels for exist
                    $existChannels = array_column($exist->get('channels')->toArray(), 'id');
                    sort($existChannels);

                    if ($productId == $exist->get('productId')
                        && $entity->get('attributeId') == $exist->get('attributeId')
                        && $entity->get('scope') == $exist->get('scope')
                        && empty($exist->get('productFamilyAttributeId'))
                        && ($entity->get('scope') == 'Global' || ($entity->get('scope') == 'Channel' && $channels == $existChannels))) {
                        $productAttributeValue = $exist;
                        $productAttributeValue->set('productFamilyAttributeId', $entity->get('id'));
                        break;
                    }
                }
            }

            // create new product attribute value if it needs
            if (empty($productAttributeValue)) {
                $productAttributeValue = $this->getEntityManager()->getEntity('ProductAttributeValue');
                $productAttributeValue->set('productId', $productId);
                $productAttributeValue->set('attributeId', $entity->get('attributeId'));
                $productAttributeValue->set('productFamilyAttributeId', $entity->get('id'));
                $productAttributeValue->set('ownerUserId', $entity->get('ownerUserId'));
                $productAttributeValue->set('assignedUserId', $entity->get('assignedUserId'));
                $productAttributeValue->set('teamsIds', $entity->get('teamsIds'));
            }

            // update data
            $productAttributeValue->set('scope', $entity->get('scope'));
            $productAttributeValue->set('isRequired', $entity->get('isRequired'));
            $productAttributeValue->set('channelsIds', $entity->get('channelsIds'));

            // save
            $this
                ->getEntityManager()
                ->saveEntity($productAttributeValue, ['skipProductAttributeValueHook' => true, 'productFamilyAttributeChanged' => true]);

            // delete channels for custom attribute values if it needs
            if ($entity->get('scope') == 'Channel' && !empty($channels)) {
                $customAttributes = $repository
                    ->select(['id'])
                    ->where(
                        [
                            'productId'                => $productId,
                            'attributeId'              => $entity->get('attributeId'),
                            'productFamilyAttributeId' => null,
                            'scope'                    => 'Channel'
                        ]
                    )
                    ->find();

                if (count($customAttributes) > 0) {
                    foreach ($customAttributes as $customAttribute) {
                        foreach ($channels as $channel) {
                            $repository->unrelate($customAttribute, 'channels', $channel);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->addDependency('language');
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'ProductFamilyAttribute');
    }
}
