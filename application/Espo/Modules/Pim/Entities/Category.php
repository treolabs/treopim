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

namespace Espo\Modules\Pim\Entities;

use Espo\ORM\EntityCollection;
use Espo\Core\Exceptions\Error;

/**
 * Entity Category
 *
 * @author r.ratsun@treolabs.com
 */
class Category extends \Espo\Core\Templates\Entities\Base
{
    /**
     * @var string
     */
    protected $entityType = "Category";

    /**
     * @return bool
     * @throws Error
     */
    public function hasChildren(): bool
    {
        // validation
        $this->isEntity();

        $count = $this
            ->getEntityManager()
            ->getRepository($this->entityType)
            ->where(['categoryParentId' => $this->get('id')])
            ->count();

        return !empty($count);
    }

    /**
     * @param array|null $select
     *
     * @return EntityCollection|null
     * @throws Error
     */
    public function getChildren(array $select = null): ?EntityCollection
    {
        // validation
        $this->isEntity();

        return $this
            ->getEntityManager()
            ->getRepository($this->entityType)
            ->getChildren($this->get('id'), $select);
    }

    /**
     * @return EntityCollection
     * @throws Error
     */
    public function getTreeProducts(): EntityCollection
    {
        // validation
        $this->isEntity();

        // prepare where
        $where = [
            [
                'OR' => [
                    ['categories.id' => $this->get('id')],
                    ['categories.categoryRoute*' => "%|" . $this->get('id') . "|%"]
                ]
            ]
        ];

        return $this
            ->getEntityManager()
            ->getRepository('Product')
            ->distinct()
            ->join('categories')
            ->where($where)
            ->find();
    }

    /**
     * @return bool
     * @throws Error
     */
    protected function isEntity(): bool
    {
        if (empty($id = $this->get('id'))) {
            throw new Error('Category is not exist');
        }

        return true;
    }
}
