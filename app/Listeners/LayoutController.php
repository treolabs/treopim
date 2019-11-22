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

        if (!$isAdminPage && $scope == 'Attribute' && $name == 'detail') {
            /** @var array $result */
            $result = Json::decode($event->getArgument('result'), true);

            // push row
            $result[0]['rows'][] = [['name' => 'isMultilang', 'inlineEditDisabled' => true], false];

            // push row
            $result[0]['rows'][] = [['name' => 'name'], ['name' => 'typeValue']];

            if ($this->getConfig()->get('isMultilangActive', false)) {
                foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                    // prepare key
                    $key = ucfirst(Util::toCamelCase(strtolower($locale)));

                    // push row
                    $result[0]['rows'][] = [['name' => 'name' . $key], ['name' => 'typeValue' . $key]];
                }
            }

            $event->setArgument('result', Json::encode($result));
        }
    }
}
