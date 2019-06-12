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
 * Class ProductTypesDashlet
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductTypesDashlet extends AbstractProductDashletService
{
    /**
     * Get Product types
     *
     * @return array
     */
    public function getDashlet(): array
    {
        $result = ['total' => 0, 'list' => []];
        $productData = [];

        // get product data form DB
        $sql = "SELECT
                    type      AS type,
                    is_active AS isActive,
                    COUNT(id) AS amount
                FROM product
                WHERE deleted = 0 AND type IN " . $this->getProductTypesCondition() . "
                GROUP BY is_active, type;";

        $sth = $this->getPDO()->prepare($sql);
        $sth->execute();
        $products = $sth->fetchAll(\PDO::FETCH_ASSOC);

        // prepare product data
        foreach ($products as $product) {
            if ($product['isActive']) {
                $productData[$product['type']]['active'] = $product['amount'];
            } else {
                $productData[$product['type']]['notActive'] = $product['amount'];
            }
        }

        // prepare result
        foreach ($productData as $type => $value) {
            $value['active'] = $value['active'] ?? 0;
            $value['notActive'] = $value['notActive'] ?? 0;

            $result['list'][] = [
                'id'        => $type,
                'name'      => $type,
                'total'     => $value['active'] + $value['notActive'],
                'active'    => (int)$value['active'],
                'notActive' => (int)$value['notActive']
            ];
        }


        $result['total'] = count($result['list']);

        return $result;
    }
}
