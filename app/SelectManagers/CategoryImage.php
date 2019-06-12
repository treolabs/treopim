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

use Pim\Core\SelectManagers\AbstractSelectManager;
use PDO;

/**
 * CategoryImage select manager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CategoryImage extends AbstractSelectManager
{

    /**
     * LinkedWithCategory filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithCategory(array &$result)
    {
        if (!empty($categoryId = (string)$this->getSelectCondition('notLinkedWithCategory'))) {
            foreach ($this->getCategoryImageCategories($categoryId) as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['categoryImageId']
                ];
            }
        }
    }

    /**
     * Get images related to category
     *
     * @param string $categoryId
     *
     * @return array
     */
    protected function getCategoryImageCategories(string $categoryId): array
    {
        $sql
            = 'SELECT category_image_id AS categoryImageId
                FROM category_image_category
                WHERE deleted = 0 
                      AND category_id = :categoryId';

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute(['categoryId' => $categoryId]);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
