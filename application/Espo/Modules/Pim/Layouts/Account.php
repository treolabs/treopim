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

namespace Espo\Modules\Pim\Layouts;

use Treo\Layouts\AbstractLayout;

/**
 * Account layout
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Account extends AbstractLayout
{

    /**
     * Layout list
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutList(array $data): array
    {
        // get existing names
        $names = array_column($data, 'name');

        if (!in_array('channel', $names)) {
            $data[] = ['name' => 'channel'];
        }

        return $data;
    }

    /**
     * Layout detail
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutDetail(array $data): array
    {
        if (isset($data[0]['rows'])) {
            // get existing names
            $names = [];
            foreach ($data[0]['rows'] as $row) {
                $names = array_merge($names, array_column($row, 'name'));
            }

            if (!in_array('channel', $names)) {
                $data[0]['rows'][] = [
                    ['name' => 'channel'],
                    false
                ];
            }
        }

        return $data;
    }
}
