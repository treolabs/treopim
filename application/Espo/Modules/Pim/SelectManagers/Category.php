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

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Category
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Category extends AbstractSelectManager
{

    /**
     * @param array $result
     */
    protected function boolFilterOnlyRootCategory(array &$result)
    {
        if ($this->hasBoolFilter('onlyRootCategory')) {
            $result['whereClause'][] = [
                'categoryParentId' => null
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterOnlyCatalogCategories(array &$result)
    {
        // get catalog
        $catalog = $this
            ->getEntityManager()
            ->getEntity('Catalog', (string)$this->getSelectCondition('onlyCatalogCategories'));

        if (!empty($catalog) && !empty($catalogTrees = $catalog->get('categories')->toArray())) {
            // prepare where
            $where[] = ['id' => array_column($catalogTrees, 'id')];
            foreach ($catalogTrees as $catalogTree) {
                $where[] = ['categoryRoute*' => "%|" . $catalogTree['id'] . "|%"];
            }

            $result['whereClause'][] = ['OR' => $where];
        } else {
            $result['whereClause'][] = ['id' => -1];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotChildCategory(array &$result)
    {
        // prepare category id
        $categoryId = (string)$this->getSelectCondition('notChildCategory');

        $result['whereClause'][] = [
            'categoryRoute!*' => "%|$categoryId|%"
        ];
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedProductCategories(array &$result)
    {
        $data = $this->getSelectCondition('notLinkedProductCategories');

        // prepare product categories
        $productCategories = $this
            ->getEntityManager()
            ->getRepository('ProductCategory')
            ->distinct()
            ->select(['categoryId'])
            ->where([
                'productId' => $data['productId'],
                'scope' => $data['scope']
            ])
            ->find()
            ->toArray();

        if (!empty($productCategories)) {
            $result['whereClause'][] = ['id!=' => array_column($productCategories, 'categoryId')];
        }
    }

    /**
     * @param $result
     */
    protected function boolFilterHasOnlyChildCategory(&$result)
    {
        // prepare parent categories ids
        $parentCategories = $this
            ->getEntityManager()
            ->getRepository('Category')
            ->distinct()
            ->select(['id'])
            ->join('categories')
            ->find()
            ->toArray();

        if (!empty($parentCategories)) {
            $result['whereClause'][] = [
                'id!=' => array_column($parentCategories, 'id')
            ];
        }
    }
}
