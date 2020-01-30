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

namespace Pim\SelectManagers;

use Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class Channel
 *
 * @author r.ratsun@treolabs.com
 */
class Channel extends AbstractSelectManager
{
    /**
     * NotLinkedWithPriceProfile filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithPriceProfile(&$result)
    {
        if (!empty($priceProfileId = (string)$this->getSelectCondition('notLinkedWithPriceProfile'))) {
            // get channel related with product
            $channel = $this->getEntityManager()
                ->getRepository('Channel')
                ->distinct()
                ->join('priceProfiles')
                ->where(['priceProfiles.id' => $priceProfileId])
                ->find();

            // set filter
            foreach ($channel as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row->get('id')
                ];
            }
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedWithAttributesInProduct(array &$result)
    {
        $data = (array)$this->getSelectCondition('notLinkedWithAttributesInProduct');

        if (isset($data['productId']) && isset($data['attributeId'])) {
            $channels = $this
                ->getEntityManager()
                ->getRepository('Channel')
                ->select(['id'])
                ->distinct()
                ->join(['productAttributeValues'])
                ->where([
                    'productAttributeValues.attributeId' => $data['attributeId'],
                    'productAttributeValues.productId' => $data['productId']
                ])
                ->find()
                ->toArray();

            $result['whereClause'][] = [
                'id!=' => !empty($channels) ? array_column($channels, 'id') : []
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedWithAttributesInProductFamily(array &$result)
    {
        $data = (array)$this->getSelectCondition('notLinkedWithAttributesInProductFamily');

        if (isset($data['productFamilyId']) && isset($data['attributeId'])) {
            $channels = $this
                ->getEntityManager()
                ->getRepository('Channel')
                ->select(['id'])
                ->distinct()
                ->join(['productFamilyAttributes'])
                ->where([
                    'productFamilyAttributes.attributeId' => $data['attributeId'],
                    'productFamilyAttributes.productFamilyId' => $data['productFamilyId']
                ])
                ->find()
                ->toArray();

            $result['whereClause'][] = [
                'id!=' => !empty($channels) ? array_column($channels, 'id') : []
            ];
        }
    }
}
