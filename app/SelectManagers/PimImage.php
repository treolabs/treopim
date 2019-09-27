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

namespace Pim\SelectManagers;

use Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class PimImage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class PimImage extends AbstractSelectManager
{
    /**
     * @param array $result
     */
    protected function boolFilterPimImageRelation(array &$result)
    {
        foreach ($this->getSelectData('where') as $row) {
            if ($row['type'] == 'bool' && !empty($row['data']['pimImageRelation'])) {
                // prepare id
                $id = (string)$row['data']['pimImageRelation']['id'];

                // prepare where clause
                switch ($row['data']['pimImageRelation']['scope']) {
                    case 'Product':
                        $result['whereClause'][] = [
                            'OR'         => [
                                'productId'   => null,
                                'productId!=' => $id,
                            ],
                            'categoryId' => null
                        ];
                        break;
                    case 'Category':
                        $result['whereClause'][] = [
                            'OR'        => [
                                'categoryId'   => null,
                                'categoryId!=' => $id,
                            ],
                            'productId' => null
                        ];
                        break;
                }
            }
        }
    }
}
