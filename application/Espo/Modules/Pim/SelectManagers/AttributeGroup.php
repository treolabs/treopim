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

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of AttributeGroup
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class AttributeGroup extends AbstractSelectManager
{

    /**
     * @param array $result
     */
    protected function boolFilterWithNotLinkedAttributesToProduct(array &$result)
    {
        // get product attributes
        $productAttributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->select(['attributeId'])
            ->where([
                'productId' => (string)$this->getSelectCondition('withNotLinkedAttributesToProduct'),
                'scope' => 'Global'
            ])
            ->find()
            ->toArray();

        if (count($productAttributes) > 0) {
            $result['whereClause'][] = [
                'id' => $this->getNotLinkedAttributeGroups($productAttributes)
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterWithNotLinkedAttributesToProductFamily(array &$result)
    {
        // get product family attributes
        $productFamilyAttributes = $this
            ->getEntityManager()
            ->getRepository('ProductFamilyAttribute')
            ->select(['attributeId'])
            ->where([
                'productFamilyId' => (string)$this->getSelectCondition('withNotLinkedAttributesToProductFamily'),
                'scope' => 'Global'
            ])
            ->find()
            ->toArray();

        if (count($productFamilyAttributes) > 0) {
            $result['whereClause'][] = [
                'id' => $this->getNotLinkedAttributeGroups($productFamilyAttributes)
            ];
        }
    }

    /**
     * Get attributeGroups with not linked all related attributes to product or productFamily
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function getNotLinkedAttributeGroups(array $attributes): array
    {
        // prepare result
        $result = [];

        // get all attribute groups
        $attributeGroups = $this
            ->getEntityManager()
            ->getRepository('AttributeGroup')
            ->select(['id'])
            ->find();

        foreach ($attributeGroups as $attributeGroup) {
            $attr = $attributeGroup->get('attributes')->toArray();

            if (!empty(array_diff(
                array_column($attr, 'id'),
                array_column($attributes, 'attributeId')
            ))) {
                $result[] = $attributeGroup->get('id');
            }
        }

        return $result;
    }
}
