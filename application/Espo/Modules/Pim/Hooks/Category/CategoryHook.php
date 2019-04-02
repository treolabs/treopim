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

namespace Espo\Modules\Pim\Hooks\Category;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\Entity;

/**
 * Class CategoryHook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CategoryHook extends \Espo\Modules\Pim\Core\Hooks\AbstractHook
{
    /**
     * @param Entity $entity
     * @param array  $params
     */
    public function beforeSave(Entity $entity, $params = [])
    {
        if (!$this->isCodeValid($entity)) {
            throw new BadRequest(
                $this->translate(
                    'Code is invalid',
                    'exceptions',
                    'Global'
                )
            );
        }
    }

    /**
     * @param Entity $entity
     * @param array  $params
     */
    public function beforeRemove(Entity $entity, $params = [])
    {
        if (count($entity->get('categories')) > 0) {
            throw new BadRequest(
                $this->translate(
                    "Category has child category and can't be deleted",
                    'exceptions',
                    'Category'
                )
            );
        }
    }
}
