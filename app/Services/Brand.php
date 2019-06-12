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

namespace Pim\Services;

use Espo\ORM\Entity;

/**
 * Brand service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Brand extends AbstractService
{
    /**
     * @param Entity $entity
     */
    protected function afterDeleteEntity(Entity $entity)
    {
        // call parent action
        parent::afterDeleteEntity($entity);

        // unlink
        $this->unlinkBrand([$entity->get('id')]);
    }

    /**
     * @param array $idList
     */
    protected function afterMassRemove(array $idList)
    {
        // call parent action
        parent::afterMassRemove($idList);

        // unlink
        $this->unlinkBrand($idList);
    }

    /**
     * Unlink brand from products
     *
     * @param array $ids
     *
     * @return bool
     */
    protected function unlinkBrand(array $ids): bool
    {
        // prepare data
        $result = false;

        if (!empty($ids)) {
            // prepare ids
            $ids = implode("','", $ids);

            // prepare sql
            $sql = sprintf("UPDATE product SET brand_id = null WHERE brand_id IN ('%s');", $ids);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }
}
