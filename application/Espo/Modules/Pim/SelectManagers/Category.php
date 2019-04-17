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
    protected function boolFilterOnlyChildCategory(array &$result)
    {
        if ($this->hasBoolFilter('onlyChildCategory')) {
            $result['whereClause'][] = [
                'id' => $this->getChildCategory()
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotChildCategory(array &$result)
    {
        // prepare data
        $categoryId = (string)$this->getSelectCondition('notChildCategory');

        $this->hideChildCategories($result, $categoryId);
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedWithChannel(array &$result)
    {
        // prepare data
        $channelId = (string)$this->getSelectCondition('notLinkedWithChannel');

        if (!empty($channelId)) {
            // get categories linked with channel
            $channelCategories = $this->getChannelCategories($channelId);
            foreach ($channelCategories as $category) {
                $this->hideChildCategories($result, $category['categoryId']);

                $result['whereClause'][] = [
                    'id!=' => (string)$category['categoryId']
                ];
            }
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProduct(array &$result)
    {
        // prepare data
        $productId = (string)$this->getSelectCondition('notLinkedWithProduct');

        if (!empty($productId)) {
            foreach ($this->getProductCategories($productId) as $id) {
                $result['whereClause'][] = [
                    'id!=' => (string)$id
                ];
            }
        }
    }

    /**
     * Get product categories
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getProductCategories(string $productId): array
    {
        // prepare result
        $result = [];

        $sql
            = "SELECT
                  category_id AS categoryId
                FROM
                  product_category_linker
                WHERE
                  deleted=0 AND product_id='$productId'";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $result = array_column($data, 'categoryId');
        }

        return $result;
    }

    /**
     * Get categories without children
     *
     * @return array
     */
    protected function getChildCategory(): array
    {
        $sql
            = 'SELECT 
                  category.id
                FROM 
                  category
                WHERE 
                  category.deleted=0
                 AND
                  category.id NOT IN 
                    (SELECT DISTINCT category_parent_id 
                     FROM category 
                     WHERE category_parent_id IS NOT NULL AND deleted=0)';

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        // get data
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data)) ? array_column($data, 'id') : [];
    }

    /**
     * Get Channel Categories
     *
     * @param string $channelId
     *
     * @return array
     */
    protected function getChannelCategories(string $channelId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT chl.category_id AS categoryId
                FROM 
                  category_channel_linker AS chl
                JOIN 
                  category AS c ON chl.category_id = c.id
                WHERE 
                  chl.deleted = 0 
                  AND c.deleted = 0
                  AND c.is_active = 1
                  AND chl.channel_id = ' . $pdo->quote($channelId);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Hide all subcategories
     *
     * @param array  $result
     * @param string $categoryId
     */
    protected function hideChildCategories(array &$result, string $categoryId)
    {
        $result['whereClause'][] = [
            'categoryRoute!*' => "%|$categoryId|%"
        ];
    }
}
