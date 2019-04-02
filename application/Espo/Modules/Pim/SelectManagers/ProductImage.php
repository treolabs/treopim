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
use PDO;

/**
 * ProductImage select manager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductImage extends AbstractSelectManager
{

    /**
     * LinkedWithProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProduct(array &$result)
    {
        if (!empty($productId = (string)$this->getSelectCondition('notLinkedWithProduct'))) {
            foreach ($this->getProductImageProducts($productId) as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productImageId']
                ];
            }
        }
    }

    /**
     * Get images related to product
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getProductImageProducts(string $productId): array
    {
        $sql
            = 'SELECT product_image_id AS productImageId
                FROM product_image_product
                WHERE deleted = 0 
                      AND product_id = :productId';

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute(['productId' => $productId]);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
