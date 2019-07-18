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

namespace Pim\Hooks\ExportProfile;

use Espo\Core\Hooks\Base;
use Espo\ORM\Entity;

/**
 * ExportProfileProductImageHook hook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ExportProfileProductImageHook extends Base
{
    /**
     * Before save action
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        if ($entity->isNew() && $entity->get('type') == 'productImage') {
            $entity->set('isHeaderRow', true);
        }
    }
}
