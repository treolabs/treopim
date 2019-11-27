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

namespace Pim\Services;

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
            ->getRepository('Channel')
            ->select(['id', 'name'])
            ->find();

        if (count($data) > 0) {
            foreach ($data as $row) {
                // prepare channel id
                $channelId = $row->get('id');

                $result['list'][] = [
                    'id'        => $row->get('id'),
                    'name'      => $row->get('name'),
                    'products'  => $this->count(
                        "SELECT COUNT(pc.id) AS total FROM product_channel AS pc JOIN product AS p ON p.id=pc.product_id AND p.deleted=0 WHERE pc.channel_id='$channelId'"
                    ),
                    'active'    => $this->count(
                        "SELECT COUNT(pc.id) AS total FROM product_channel AS pc JOIN product AS p ON p.id=pc.product_id AND p.deleted=0 AND p.is_active=1 WHERE pc.channel_id='$channelId'"
                    ),
                    'notActive' => $this->count(
                        "SELECT COUNT(pc.id) AS total FROM product_channel AS pc JOIN product AS p ON p.id=pc.product_id AND p.deleted=0 AND p.is_active=0 WHERE pc.channel_id='$channelId'"
                    )
                ];
            }

            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * @param string $sql
     *
     * @return int
     */
    protected function count(string $sql): int
    {
        return (int)$this->getEntityManager()->nativeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC)[0]['total'];
    }
}
