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
        if (empty($productFamily = $entity->get('productFamily')) || empty($attribute = $entity->get('attribute'))) {
            throw new BadRequest($this->exception('ProductFamily and Attribute cannot be empty'));
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        // create product attribute value
        $this->createProductAttributeValues($entity);
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
    protected function createProductAttributeValues(Entity $entity): bool
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

        // prepare exists
        $exists = [];
        $data = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(
                [
                    'productId'   => array_column($products->toArray(), 'id'),
                    'attributeId' => $entity->get('attributeId'),
                    'scope'       => $entity->get('scope')
                ]
            )
            ->find();
        if (count($data) > 0) {
            foreach ($data as $item) {
                $exists[$item->get('productId') . '_' . $item->get('attributeId') . '_' . $item->get('scope')] = $item;
            }
        }

        foreach ($products as $product) {
            // prepare key
            $key = $product->get('id') . '_' . $entity->get('attributeId') . '_' . $entity->get('scope');

            if (isset($exists[$key])) {
                $productAttributeValue = $exists[$key];
            } else {
                $productAttributeValue = $this->getEntityManager()->getEntity('ProductAttributeValue');
                $productAttributeValue->set('productId', $product->get('id'));
                $productAttributeValue->set('attributeId', $entity->get('attributeId'));
                $productAttributeValue->set('scope', $entity->get('scope'));
            }

            $productAttributeValue->set('productFamilyId', $entity->get('productFamilyId'));
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
            ->where(
                [
                    'attributeId'     => $entity->get('attributeId'),
                    'scope'           => $entity->get('scope'),
                    'productFamilyId' => $entity->get('productFamilyId')
                ]
            )
            ->find();

        if (count($records) > 0) {
            foreach ($records as $record) {
                $this->getEntityManager()->removeEntity($record, ['skipProductAttributeValueHook' => true]);
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
