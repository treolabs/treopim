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

namespace Espo\Modules\Pim\Hooks\Association;

use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\Pim\Core\Hooks\AbstractHook;
use Espo\ORM\Entity;

/**
 * Association hook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class AssociationHook extends AbstractHook
{

    /**
     * Before save action
     *
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        if (empty($entity->get('isActive')) && $this->hasProduct($entity, true)) {
            throw new BadRequest(
                $this->translate(
                    'You can not deactivate association with active product(s)',
                    'exceptions',
                    'Association'
                )
            );
        }
    }

    /**
     * Before remove action
     *
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeRemove(Entity $entity, $options = [])
    {
        if ($this->hasProduct($entity)) {
            throw new BadRequest(
                $this->translate(
                    'Association is linked with product(s). Please, unlink product(s) first',
                    'exceptions',
                    'Association'
                )
            );
        }
    }

    /**
     * Is association used in product(s)
     *
     * @param Entity $entity
     * @param bool   $isActive
     *
     * @return bool
     */
    protected function hasProduct(Entity $entity, bool $isActive = false): bool
    {
        // prepare attribute id
        $associationId = $entity->get('id');

        $sql
            = "SELECT
                  COUNT(ap.id) as total
                FROM associated_product AS ap
                  JOIN product AS pm 
                    ON pm.id = ap.main_product_id AND pm.deleted = 0
                  JOIN product AS pr 
                    ON pr.id = ap.related_product_id AND pr.deleted = 0
                WHERE ap.deleted = 0 AND ap.association_id = '{$associationId}'";

        if ($isActive) {
            $sql .= " AND (pm.is_active=1 OR pr.is_active=1)";
        }

        // execute
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        // get data
        $data = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($data['total']);
    }
}
