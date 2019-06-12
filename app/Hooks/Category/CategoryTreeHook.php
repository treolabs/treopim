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

namespace Pim\Hooks\Category;

use Espo\Core\ORM\Entity;

/**
 * Class CategoryTree
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CategoryTreeHook extends \Espo\Core\Hooks\Base
{
    /**
     * Get category route
     *
     * @param Entity $entity
     * @param bool   $isName
     *
     * @return string
     */
    public static function getCategoryRoute(Entity $entity, bool $isName = false): string
    {
        // prepare result
        $result = '';

        // prepare data
        $data = [];

        while (!empty($parent = $entity->get('categoryParent'))) {
            // push id
            if (!$isName) {
                $data[] = $parent->get('id');
            } else {
                $data[] = trim($parent->get('name'));
            }

            // to next category
            $entity = $parent;
        }

        if (!empty($data)) {
            if (!$isName) {
                $result = '|' . implode('|', array_reverse($data)) . '|';
            } else {
                $result = implode(' > ', array_reverse($data));
            }
        }

        return $result;
    }

    /**
     * Update category tree
     *
     * @param Entity $entity
     * @param array  $params
     */
    public function afterSave(Entity $entity, $params = [])
    {
        // build tree
        $this->updateCategoryTree($entity, $params);

        // activate parents
        $this->activateParents($entity, $params);

        // deactivate children
        $this->deactivateChildren($entity, $params);
    }

    /**
     * Update category tree
     *
     * @param Entity $entity
     * @param array  $params
     */
    protected function updateCategoryTree(Entity $entity, $params)
    {
        // is has changes
        if ((empty($params['isSaved'])
            && ($entity->isAttributeChanged('categoryParentId')
                || $entity->isNew()
                || $entity->isAttributeChanged('name')))) {
            // set route for current category
            $entity->set('categoryRoute', self::getCategoryRoute($entity));
            $entity->set('categoryRouteName', self::getCategoryRoute($entity, true));
            $this->saveEntity($entity);

            // update all children
            if (!$entity->isNew()) {
                $children = $this->getEntityChildren($entity->get('categories'), []);
                foreach ($children as $child) {
                    // set route for child category
                    $child->set('categoryRoute', self::getCategoryRoute($child));
                    $child->set('categoryRouteName', self::getCategoryRoute($child, true));
                    $this->saveEntity($child);
                }
            }
        }
    }

    /**
     * Activate parents categories if it needs
     *
     * @param Entity $entity
     * @param array  $params
     */
    protected function activateParents(Entity $entity, $params)
    {
        // is activate action
        $isActivate = $entity->isAttributeChanged('isActive') && $entity->get('isActive');

        if (empty($params['isSaved']) && $isActivate && !$entity->isNew()) {
            // update all parents
            foreach ($this->getEntityParents($entity, []) as $parent) {
                $parent->set('isActive', true);
                $this->saveEntity($parent);
            }
        }
    }

    /**
     * Deactivate children categories if it needs
     *
     * @param Entity $entity
     * @param array  $params
     */
    protected function deactivateChildren(Entity $entity, $params)
    {
        // is deactivate action
        $isDeactivate = $entity->isAttributeChanged('isActive') && !$entity->get('isActive');

        if (empty($params['isSaved']) && $isDeactivate && !$entity->isNew()) {
            // update all children
            $children = $this->getEntityChildren($entity->get('categories'), []);
            foreach ($children as $child) {
                $child->set('isActive', false);
                $this->saveEntity($child);
            }
        }
    }

    /**
     * Save entity
     *
     * @param Entity $entity
     */
    protected function saveEntity(Entity $entity)
    {
        $this
            ->getEntityManager()
            ->saveEntity($entity, ['isSaved' => true, 'categoryTreeHook' => true]);
    }

    /**
     * Get entity parents
     *
     * @param Entity $category
     * @param array  $parents
     *
     * @return array
     */
    protected function getEntityParents(Entity $category, array $parents): array
    {
        $parent = $category->get('categoryParent');
        if (!empty($parent)) {
            $parents[] = $parent;
            $parents = $this->getEntityParents($parent, $parents);
        }

        return $parents;
    }

    /**
     * Get all children by recursive
     *
     * @param array $entities
     * @param array $children
     *
     * @return array
     */
    protected function getEntityChildren($entities, array $children)
    {
        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $children[] = $entity;
            }
            foreach ($entities as $entity) {
                $children = $this->getEntityChildren($entity->get('categories'), $children);
            }
        }

        return $children;
    }
}
