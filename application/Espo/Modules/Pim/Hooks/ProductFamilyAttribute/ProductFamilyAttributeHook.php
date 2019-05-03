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

namespace Espo\Modules\Pim\Hooks\ProductFamilyAttribute;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Hooks\Base as BaseHook;
use Espo\ORM\Entity;

/**
 * Class ProductFamilyAttributeHook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductFamilyAttributeHook extends BaseHook
{

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        // exit
        if (!empty($options['skipValidation'])) {
            return true;
        }

        if (empty($productFamily = $entity->get('productFamily')) || empty($attribute = $entity->get('attribute'))) {
            throw new BadRequest($this->exception('ProductFamily and Attribute cannot be empty'));
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }

        // clearing channels ids
        if ($entity->get('scope') == 'Global') {
            $entity->set('channelsIds', []);
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        // update product attribute value
        $this->updateProductAttributeValues($entity);
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterRemove(Entity $entity, $options = [])
    {
        $this->removeProductAttributeValues($entity);
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        // parent init
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('ProductFamilyAttribute')
            ->where(
                [
                    'id!='            => $entity->get('id'),
                    'productFamilyId' => $entity->get('productFamilyId'),
                    'attributeId'     => $entity->get('attributeId'),
                    'scope'           => $entity->get('scope'),
                ]
            )
            ->count();

        return empty($count);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function updateProductAttributeValues(Entity $entity): bool
    {
        // get products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['productFamilyId' => $entity->get('productFamilyId')])
            ->find();

        // exit
        if (count($products) == 0) {
            return true;
        }

        // get exists
        $exists = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(
                [
                    'productId'   => array_column($products->toArray(), 'id'),
                    'attributeId' => $entity->get('attributeId')
                ]
            )
            ->find();

        foreach ($products as $product) {
            // prepare product attribute value
            $productAttributeValue = null;

            // find related
            foreach ($exists as $exist) {
                if ($exist->get('productFamilyAttributeId') == $entity->get('id') && $product->get('id') == $exist->get('productId')) {
                    $productAttributeValue = $exist;
                    break;
                }
            }

            // find not related
            if (empty($productAttributeValue)) {
                foreach ($exists as $exist) {
                    if ($product->get('id') == $exist->get('productId')
                        && $entity->get('attributeId') == $exist->get('attributeId')
                        && $entity->get('scope') == $exist->get('scope')) {
                        $productAttributeValue = $exist;
                        $productAttributeValue->set('productFamilyAttributeId', $entity->get('id'));
                        break;
                    }
                }
            }

            // create new product attribute value if it needs
            if (empty($productAttributeValue)) {
                $productAttributeValue = $this->getEntityManager()->getEntity('ProductAttributeValue');
                $productAttributeValue->set('productId', $product->get('id'));
                $productAttributeValue->set('attributeId', $entity->get('attributeId'));
                $productAttributeValue->set('productFamilyAttributeId', $entity->get('id'));
                $productAttributeValue->set('ownerUserId', $entity->get('ownerUserId'));
                $productAttributeValue->set('assignedUserId', $entity->get('assignedUserId'));
                $productAttributeValue->set('teamsIds', $entity->get('teamsIds'));
            }

            $productAttributeValue->set('scope', $entity->get('scope'));
            $productAttributeValue->set('isRequired', $entity->get('isRequired'));
            $productAttributeValue->set('channelsIds', $entity->get('channelsIds'));

            $this->getEntityManager()->saveEntity($productAttributeValue, ['skipProductAttributeValueHook' => true]);
        }

        return true;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function removeProductAttributeValues(Entity $entity): bool
    {
        $records = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['productFamilyAttributeId' => $entity->get('id')])
            ->find();

        if (count($records) > 0) {
            foreach ($records as $record) {
                $record->set('productFamilyAttributeId', null);
                $record->set('deleted', true);

                $this->getEntityManager()->saveEntity($record, ['skipProductAttributeValueHook' => true]);
            }
        }

        return true;
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
