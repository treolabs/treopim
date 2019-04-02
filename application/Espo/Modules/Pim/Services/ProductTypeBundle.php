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

declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Util;

/**
 * ProductTypeBundle service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductTypeBundle extends Base
{

    /**
     * Get bundle product
     *
     * @param string $id
     * @return array
     */
    public function getBundleProduct(string $id): array
    {
        // get data from db
        $pdo  = $this->getEntityManager()->getPDO();
        $sql  = "SELECT
                  ptb.id                AS id,
                  ptb.product_id        AS productId,
                  ptb.bundle_product_id AS bundleProductId,
                  ptb.amount            AS amount
                FROM product_type_bundle AS ptb
                WHERE ptb.deleted = 0
                      AND ptb.id =".$pdo->quote($id);
        $sth  = $pdo->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data[0])) ? $data[0] : [];
    }

    /**
     * Get bundle products
     *
     * @param string $productId
     * @return array
     */
    public function getBundleProducts(string $productId): array
    {
        // default result
        $result = [];

        // get data from db
        $pdo  = $this->getEntityManager()->getPDO();
        $sql  = "SELECT
                  ptb.id     AS productTypeBundleId,
                  p.id       AS productId,
                  p.name     AS productName,
                  p.sku      AS productSku,
                  ptb.amount AS amount
                FROM product_type_bundle AS ptb
                  JOIN product AS p ON p.id = ptb.product_id AND p.is_active = 1
                WHERE ptb.deleted = 0
                      AND p.deleted = 0
                      AND ptb.bundle_product_id =".$pdo->quote($productId);
        $sth  = $pdo->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        // prepare result
        if (!empty($data)) {
            foreach ($data as $row) {
                // prepare data
                $row['amount'] = (float) $row['amount'];

                // push row
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * Create bundle product
     *
     * @param string $bundleProductId
     * @param string $productId
     * @param float $amount
     *
     * @return bool
     */
    public function create(string $bundleProductId, string $productId, float $amount): bool
    {
        // prepare data
        $result = false;

        if (!empty($bundleProductId) && !empty($productId) && !empty($amount)) {
            // prepare sql
            $sql = "INSERT INTO product_type_bundle SET "
                ."id='%s', product_id='%s', bundle_product_id='%s', amount=%s;";
            $sql = sprintf($sql, Util::generateId(), $productId, $bundleProductId, $amount);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Update data
     *
     * @param string $id
     * @param array $data
     *
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        // prepare data
        $result = false;

        if (!empty($data['amount'])) {
            // prepare sql
            $sql = "UPDATE product_type_bundle SET `amount`=%s WHERE id='%s'";
            $sql = sprintf($sql, $data['amount'], $id);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Delete bundle product
     *
     * @param string $id
     *
     * @return bool
     */
    public function delete(string $id): bool
    {
        // prepare data
        $result = false;

        if (!empty($id)) {
            // prepare sql
            $sql = sprintf("DELETE FROM product_type_bundle WHERE id = '%s'", $id);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Delete by product id
     *
     * @param array $ids
     *
     * @return bool
     */
    public function deleteByProductId(array $ids): bool
    {
        // prepare data
        $result = false;

        if (!empty($ids)) {
            // prepare sql
            $sql = "DELETE FROM product_type_bundle WHERE bundle_product_id IN ('%s')";
            $sql = sprintf($sql, implode("','", $ids));

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }
}
