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

namespace Pim\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;

/**
 * Class ImportFeedEntity
 *
 * @author r.zablodskiy@treolabs.com
 */
class ImportFeedEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        $entity = $event->getArgument('entity');

        if (!$this->isConfiguratorValid($entity)) {
            throw new Error('Configurator settings incorrect');
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isConfiguratorValid(Entity $entity): bool
    {
        $configurator = Json::decode(Json::encode($entity->get('data')->configuration), true);

        foreach ($configurator as $key => $item) {
            // check for same attributes
            if (isset($item['attributeId'])) {
                foreach ($configurator as $k => $i) {
                    if (isset($i['attributeId']) && $i['attributeId'] == $item['attributeId']
                        && $i['scope'] == $item['scope'] && $key != $k) {
                        if ($item['scope'] == 'Channel'
                            && empty(array_intersect($item['channelsIds'], $i['channelsIds']))) {
                            continue;
                        }

                        return false;
                    }
                }
            }

            // check for the same product categories
            if ($item['name'] == 'productCategories') {
                foreach ($configurator as $k => $i) {
                    if ($i['name'] == $item['name'] && $i['scope'] == $item['scope'] && $key != $k) {
                        if ($item['scope'] == 'Channel'
                            && empty(array_intersect($item['channelsIds'], $i['channelsIds']))) {
                            continue;
                        }

                        return false;
                    }
                }
            }
        }

        return true;
    }
}
