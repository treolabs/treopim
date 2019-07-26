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

namespace Pim\SelectManagers;

use Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Attribute
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Attribute extends AbstractSelectManager
{

    /**
     * NotLinkedWithProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProduct(&$result)
    {
        // prepare data
        $productId = (string)$this->getSelectCondition('notLinkedWithProduct');

        foreach ($this->createService('Product')->getAttributes($productId) as $row) {
            $result['whereClause'][] = [
                'id!=' => $row['attributeId']
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedProductAttributeValues(array &$result)
    {
        // prepare data
        $data = (array)$this->getSelectCondition('notLinkedProductAttributeValues');

        if (isset($data['productId']) && isset($data['scope'])) {
            // get linked to product attributes
            $attributes = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->select(['attributeId'])
                ->where([
                    'productId' => $data['productId'],
                    'scope' => $data['scope']
                ])
                ->find()
                ->toArray();

            $result['whereClause'][] = [
                'id!=' => array_column($attributes, 'attributeId')
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedProductFamilyAttributes(array &$result)
    {
        // prepare data
        $data = (array)$this->getSelectCondition('notLinkedProductFamilyAttributes');

        if (isset($data['productFamilyId']) && isset($data['scope'])) {
            // get linked to product attributes
            $attributes = $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->select(['attributeId'])
                ->where([
                    'productFamilyId' => $data['productFamilyId'],
                    'scope' => $data['scope']
                ])
                ->find()
                ->toArray();

            $result['whereClause'][] = [
                'id!=' => array_column($attributes, 'attributeId')
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterUnitTypeDisabled(array &$result)
    {
        $unitAttributes = $this
            ->getEntityManager()
            ->getRepository('Attribute')
            ->select(['id'])
            ->where([
                'type' => 'unit'
            ])
            ->find()
            ->toArray();

        if (count($unitAttributes) > 0) {
            $result['whereClause'][] = [
                'id!=' =>  array_column($unitAttributes, 'id')
            ];
        }
    }
}
