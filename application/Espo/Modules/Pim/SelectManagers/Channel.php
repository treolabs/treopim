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

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Channel select manager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Channel extends AbstractSelectManager
{
    /**
     * NotLinkedWithCategory filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithCategory(&$result)
    {
        if (!empty($categoryId = (string)$this->getSelectCondition('notLinkedWithCategory'))) {
            // get category
            $category = $this->getEntityManager()->getEntity('Category', $categoryId);

            if (!empty($category) && !empty($channels = $category->getChannels())) {
                $result['whereClause'][] = [
                    'id!=' => array_column($channels->toArray(), 'id')
                ];
            }
        }
    }

    /**
     * LinkedWithCategory filter
     *
     * @param array $result
     */
    protected function boolFilterLinkedWithCategory(&$result)
    {
        if (!empty($categoryId = (string)$this->getSelectCondition('linkedWithCategory'))) {
            // get category
            $category = $this->getEntityManager()->getEntity('Category', $categoryId);

            $ids = 'no-allowed-channels';
            if (!empty($category) && !empty($channels = $category->getChannels())) {
                $ids = array_column($channels->toArray(), 'id');
            }

            // prepare where
            $result['whereClause'][] = [
                'id=' => $ids
            ];
        }
    }

    /**
     * LinkedWithProduct filter
     *
     * @param array $result
     */
    protected function boolFilterLinkedWithProduct(&$result)
    {
        if (!empty($productId = (string)$this->getSelectCondition('linkedWithProduct'))) {
            // get channels
            $channels = $this->createService('Product')->getChannels($productId);

            // prepare where
            $result['whereClause'][] = [
                'id=' => array_column($channels, 'channelId')
            ];
        }
    }


    /**
     * NotLinkedWithPricing filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithPricing(&$result)
    {
        $pricingId = (string)$this->getSelectCondition('notLinkedWithPricing');

        if (!empty($pricingId)) {
            // get channel related with product
            $channel = $this->getEntityManager()
                ->getRepository('Channel')
                ->distinct()
                ->join('pricings')
                ->where(['pricings.id' => $pricingId])
                ->find();

            // set filter
            foreach ($channel as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row->get('id')
                ];
            }
        }
    }
}
