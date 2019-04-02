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

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of ProductFamily
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductFamily extends AbstractSelectManager
{

    /**
     * NotLinkedWithAttribute filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithAttribute(&$result)
    {

        $productFamiliesIds = $this->getEntityManager()
            ->getRepository('ProductFamily')
            ->select(['id'])
            ->join(['attributes'])
            ->where([
                'attributes.Id' => (string)$this->getSelectCondition('notLinkedWithAttribute'),
            ])
            ->find()
            ->toArray();

        $result['whereClause'][] = [
            'id!=' => array_column($productFamiliesIds, 'id')
        ];
    }
}
