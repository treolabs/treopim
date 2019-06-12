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

use Espo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ProductImageController
 *
 * @author r.ratsun@treolabs.com
 */
class ProductImageController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeActionCreate(Event $event)
    {
        $data = $event->getArgument('data');

        if (empty($data->name) && !empty($data->imageName)) {
            // prepare name
            $name = explode(".", $data->imageName);
            $name = str_replace([' ', '-'], ['_', '_'], strtolower($name[0]));
            $name = preg_replace('/[^a-z0-9_]/', "", $name);
            $name .= $name . '_' . Util::generateId();

            // set name
            $data->name = $name;

            $event->setArgument('data', $data);
        }
    }
}
