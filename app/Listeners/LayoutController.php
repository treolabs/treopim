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

use Espo\Core\Utils\Json;
use Treo\Core\EventManager\Event;
use Treo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * Class LayoutController
 *
 * @author r.ratsun@treolabs.com
 */
class LayoutController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionRead(Event $event)
    {
        /** @var string $scope */
        $scope = $event->getArgument('params')['scope'];

        /** @var string $name */
        $name = $event->getArgument('params')['name'];

        /** @var bool $isAdminPage */
        $isAdminPage = $event->getArgument('request')->get('isAdminPage') === 'true';

        $method = 'modify' . $scope . ucfirst($name);
        $methodAdmin = $method . 'Admin';

        if (!$isAdminPage && method_exists($this, $method)) {
            $this->{$method}($event);
        } else if ($isAdminPage && method_exists($this, $methodAdmin)) {
            $this->{$methodAdmin}($event);
        }
    }

    /**
     * @param Event $event
     */
    protected function modifyProductRelationshipsAdmin(Event $event)
    {
        $this->hideAssetRelation($event);
    }

    /**
     * @param Event $event
     */
    protected function modifyCategoryRelationshipsAdmin(Event $event)
    {
        $this->hideAssetRelation($event);
    }

    /**
     * @param Event $event
     */
    protected function hideAssetRelation(Event $event): void
    {
        /** @var array $result */
        $result = Json::decode($event->getArgument('result'), true);
        //hide asset relation if Dam did not install
        if (!$this->getMetadata()->isModuleInstalled('Dam')) {
            foreach ($result as $k => $item) {
                if (isset($item['name']) && $item['name'] === 'asset_relations') {
                    unset($result[$k]);
                    break;
                }
            }
        }
        $event->setArgument('result', Json::encode($result));
    }
}
