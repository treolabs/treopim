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

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Treo\Listeners\AbstractListener;

/**
 * Listener Category
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Category extends AbstractListener
{
    /**
     * @var string
     */
    protected $entityType = 'Category';

    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionUpdate(array $data): array
    {
        if (isset($data['data']->categoryParentId) && !empty($categoryParentId = $data['data']->categoryParentId)) {
            if ($this->getService($this->entityType)->isChildCategory($data['params']['id'], $categoryParentId)) {
                $message = $this
                    ->getLanguage()
                    ->translate("You can not choose a child category", 'exceptions', 'Category');

                throw new BadRequest($message);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionPatch(array $data): array
    {
        return $this->beforeActionUpdate($data);
    }
}
