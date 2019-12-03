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

namespace Pim\Listeners;

use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class AssetRelationController
 * @package Pim\Listeners
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class AssetRelationController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionByEntity(Event $event)
    {
        $this->addAdditionalFields($event);
    }

    /**
     * Set Channels and fox view Role
     * @param Event $event
     */
    protected function addAdditionalFields(Event $event)
    {
        $result = $event->getArgument('result');
        $list = &$result['list'];
        $ids = "'" . join("','", array_column($list, 'id')) . "'";
        $channelsRelations = $this
            ->getEntityManager()
            ->nativeQuery(
                "SELECT ar.id,
                  (SELECT GROUP_CONCAT(c.id ORDER BY c.id  SEPARATOR '##')
                             FROM asset_relation_channel arc
                             RIGHT JOIN channel c ON c.id = arc.channel_id 
                             WHERE arc.asset_relation_id = ar.id AND arc.deleted = 0
                  ) AS channelsIds,
                  (SELECT GROUP_CONCAT(c.name ORDER BY c.id SEPARATOR '##')
                   FROM asset_relation_channel arc
                            RIGHT JOIN channel c ON c.id = arc.channel_id 
                            WHERE arc.asset_relation_id = ar.id AND arc.deleted = 0
                  ) AS channelsNames
              FROM asset_relation ar
              WHERE ar.deleted = 0 AND ar.scope = 'Channel' AND ar.id IN ({$ids})"
            )->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
        foreach ($list as $key => $item) {
            if (!empty($item['role']) && is_string($item['role'])) {
                $list[$key]['role'] = json_decode($item['role']);
            }
            if (!empty($item['scope']) && $item['scope'] === 'Channel' && !empty($channelsRelations[$item['id']])) {
                $list[$key]['channelsIds'] = explode('##', $channelsRelations[$item['id']][0]['channelsIds']);
                $list[$key]['channelsNames'] = explode('##', $channelsRelations[$item['id']][0]['channelsNames']);
                foreach ($list[$key]['channelsNames'] as $index => $name) {
                    $list[$key]['channelsNames'][$list[$key]['channelsIds'][$index]] = $name;
                    unset($list[$key]['channelsNames'][$index]);
                }
            }
        }
        $event->setArgument('result', $result);
    }
}
