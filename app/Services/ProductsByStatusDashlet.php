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

namespace Pim\Services;

/**
 * Class ProductsByStatusDashlet
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductsByStatusDashlet extends AbstractProductDashletService
{
    /**
     * Get Product by status
     *
     * @return array
     */
    public function getDashlet(): array
    {
        $result = ['total' => 0, 'list' => []];

        $sql = "SELECT
                    product_status AS status,
                    COUNT(id)      AS amount
                FROM product
                WHERE deleted = 0 AND type IN " . $this->getProductTypesCondition() . "
                GROUP BY product_status;";

        $sth = $this->getPDO()->prepare($sql);
        $sth->execute();
        $products = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $result['list'][] = [
                'id'     => $product['status'],
                'name'   => $product['status'],
                'amount' => (int)$product['amount']
            ];
        }

        $result['total'] = count($result['list']);

        return $result;
    }
}
