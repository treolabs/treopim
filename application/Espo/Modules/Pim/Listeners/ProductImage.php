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

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * ProductImage listener
 *
 * @author r.ratsun@treolabs.com
 */
class ProductImage extends AbstractListener
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionCreate(array $data): array
    {
        if (empty($data['data']->name) && !empty($data['data']->imageName)) {
            // prepare name
            $name = explode(".", $data['data']->imageName);
            $name = str_replace([' ', '-'], ['_', '_'], strtolower($name[0]));
            $name = preg_replace('/[^a-z0-9_]/', "", $name);
            $name .= $name . '_' . Util::generateId();

            // set name
            $data['data']->name = $name;
        }

        return $data;
    }
}
