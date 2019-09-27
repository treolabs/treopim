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
use Treo\Core\Utils\Util;

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
                // prepare field
                $field = Util::toUnderScore(lcfirst($row['data']['pimImageRelation']['scope']) . 'Id');

                // prepare id
                $id = (string)$row['data']['pimImageRelation']['id'];

                // prepare sql
                $sql = "SELECT id FROM pim_image WHERE deleted=0 AND image_id NOT IN (SELECT DISTINCT image_id FROM pim_image WHERE $field='$id' AND deleted=0) GROUP BY image_id";

                // get ids
                $sth = $this->getEntityManager()->getPDO()->prepare($sql);
                $sth->execute();
                $ids = array_column($sth->fetchAll(\PDO::FETCH_ASSOC), 'id');

                // prepare where clause
                $result['whereClause'][] = [
                    'id' => $ids
                ];
            }
        }
    }
}
