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

namespace Espo\Modules\Pim\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\Pim\Core\Repositories\AbstractRepositories;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

/**
 * Repository Category
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Category extends AbstractRepositories
{
    /**
     * @param string     $id
     * @param array|null $select
     *
     * @return EntityCollection|null
     */
    public function getChildren(string $id, array $select = null): ?EntityCollection
    {
        if (!is_null($select)) {
            $this->select($select);
        }

        return $this->where(['categoryRoute*' => "%|$id|%"])->find();
    }

    /**
     * Init
     */
    protected function init()
    {
        // call parent
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @param Entity     $entity
     * @param string     $relationName
     * @param Entity     $foreign
     * @param array|null $data
     * @param array      $options
     */
    protected function beforeRelate(
        Entity $entity,
        $relationName,
        $foreign,
        $data = null,
        array $options = []
    ) {
        if ($relationName !== 'categoryImages' && $relationName !== 'catalogs') {
            $count = $this
                ->select(['id'])
                ->where(['categoryParentId' => $entity->get('id')])
                ->count();

            if (!empty($count)) {
                throw new BadRequest($this->exception('Category has children'));
            }
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this
            ->getInjection('language')
            ->translate($key, 'exceptions', 'Category');
    }
}
