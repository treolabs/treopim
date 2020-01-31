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

namespace Pim\SelectManagers;

use Espo\Core\Exceptions\NotFound;
use Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Category
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Category extends AbstractSelectManager
{
    /**
     * @inheritDoc
     */
    public function applyBoolFilter($filterName, &$result)
    {
        parent::applyBoolFilter($filterName, $result);

        if (preg_match_all('/^allowedForProduct_(.*)$/', $filterName, $matches)) {
            $this->boolAdvancedFilterAllowedForProduct((string)$matches[1][0], $result);
        }
    }

    /**
     * @inheritDoc
     */
    public function applyAdditional(array &$result, array $params)
    {
        // prepare additional select columns
        $additionalSelectColumns = [
            'childrenCount' => '(SELECT COUNT(c1.id) FROM category AS c1 WHERE c1.category_parent_id=category.id AND c1.deleted=0)'
        ];

        // add additional select columns
        foreach ($additionalSelectColumns as $alias => $sql) {
            $result['additionalSelectColumns'][$sql] = $alias;
        }
    }

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
    protected function boolFilterNotChildCategory(array &$result)
    {
        // prepare category id
        $categoryId = (string)$this->getSelectCondition('notChildCategory');

        $result['whereClause'][] = [
            'categoryRoute!*' => "%|$categoryId|%"
        ];
    }

    /**
     * @param string $id
     * @param array  $result
     *
     * @throws NotFound
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function boolAdvancedFilterAllowedForProduct(string $id, array &$result)
    {
        // get allowed ids
        $ids = $this->getEntityManager()->getRepository('Product')->getCategoriesIdsThatCanBeRelatedWithProduct($id);

        $result['whereClause'][] = [
            'id' => empty($ids) ? ['no-such-id'] : $ids
        ];
    }
}
