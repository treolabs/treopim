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

namespace Espo\Modules\Pim\AclPortal;

use Espo\Core\AclPortal\Base;
use Espo\Entities\User;
use Espo\ORM\Entity as Entity;
use Espo\Modules\Pim\Services\Product as ProductService;

/**
 * Product ACL portal class
 *
 * @author r.ratsun@treolabs.com
 */
class Product extends Base
{
    /**
     * @param User   $user
     * @param Entity $entity
     *
     * @return bool
     */
    public function checkInAccount(User $user, Entity $entity)
    {
        // prepare result
        $result = false;

        // get accounts
        $accounts = $user->getLinkMultipleIdList('accounts');
        if (!empty($accounts)) {
            // get products ids
            $productIds = ProductService::getAccountProductIds($this->getEntityManager(), $accounts);

            // prepare result
            $result = in_array($entity->get('id'), $productIds);
        }

        return $result;
    }
}
