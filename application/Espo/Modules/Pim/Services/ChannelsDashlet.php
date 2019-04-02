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

namespace Espo\Modules\Pim\Services;

/**
 * ChannelsDashlet class
 *
 * @author r.ratsun@treolabs.com
 */
class ChannelsDashlet extends AbstractDashletService
{

    /**
     * Get general statistic
     *
     * @return array
     */
    public function getDashlet(): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // get data
        $data = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->getChannelsArray([]);

        if (!empty($data)) {
            $channels = [];
            foreach ($data as $row) {
                $channels[$row['channelId']]['channelId'] = $row['channelId'];
                $channels[$row['channelId']]['channelName'] = $row['channelName'];
                $channels[$row['channelId']]['catalogId'] = $row['catalogId'];
                $channels[$row['channelId']]['catalogName'] = $row['catalogName'];
                $channels[$row['channelId']]['products'][$row['productId']] = $row;
            }

            foreach ($channels as $row) {
                // prepare counts
                $count = count($row['products']);
                $active = 0;
                foreach ($row['products'] as $v) {
                    if (!empty($v['productIsActive'])) {
                        $active++;
                    }
                }
                $inActive = $count - $active;

                $result['list'][] = [
                    'id'          => $row['channelId'],
                    'name'        => $row['channelName'],
                    'catalogId'   => $row['catalogId'],
                    'catalogName' => $row['catalogName'],
                    'products'    => $count,
                    'active'      => $active,
                    'notActive'   => $inActive
                ];
            }

            $result['total'] = count($result['list']);
        }

        return $result;
    }
}
