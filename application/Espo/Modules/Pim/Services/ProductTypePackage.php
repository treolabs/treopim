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

namespace Espo\Modules\Pim\Services;

use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Util;

/**
 * ProductTypePackage service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductTypePackage extends Base
{

    /**
     * Get package product
     *
     * @param string $productId
     *
     * @return array
     */
    public function getPackageProduct(string $productId): array
    {
        // prepare result
        $result = [
            'id'                => null,
            'measuringUnitId'   => null,
            'measuringUnitName' => null,
            'content'           => null,
            'basicUnit'         => null,
            'packingUnit'       => null,
        ];

        // get data from db
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "SELECT
                  ptp.id                 AS id,
                  ptp.measuring_unit_id      AS measuringUnitId,
                  pu.name                AS measuringUnitName,
                  ptp.content            AS content,
                  ptp.basic_unit         AS basicUnit,
                  ptp.packing_unit       AS packingUnit
                FROM product_type_package AS ptp
                JOIN measuring_unit as pu ON pu.id = ptp.measuring_unit_id AND pu.deleted = 0
                WHERE 
                  ptp.deleted = 0
                 AND ptp.package_product_id =" . $pdo->quote($productId);
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data[0])) ? $data[0] : $result;
    }

    /**
     * Update data
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        // prepare data
        $result = false;
        $product = $this->getPackageProduct($id);

        if (is_null($product['id'])) {
            // prepare data
            $measuringUnitId = $data['measuringUnitId'];
            $content = $data['content'];
            $basicUnit = $data['basicUnit'];
            $packingUnit = $data['packingUnit'];

            // prepare sql
            $sql = "INSERT INTO product_type_package SET `id`='%s',`measuring_unit_id`='%s',`content`='%s'"
                   . ",`basic_unit`='%s',`packing_unit`='%s', `package_product_id`='%s'";
            $sql = sprintf($sql, Util::generateId(), $measuringUnitId, $content, $basicUnit, $packingUnit, $id);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        } else {
            // prepare sql
            $sql = "UPDATE product_type_package SET `measuring_unit_id`='%s',`content`='%s',`basic_unit`='%s'"
                   . ",`packing_unit`='%s' WHERE package_product_id='%s'";
            $sql = sprintf(
                $sql,
                $data['measuringUnitId'],
                $data['content'],
                $data['basicUnit'],
                $data['packingUnit'],
                $id
            );

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
            $sql = "DELETE FROM product_type_package WHERE package_product_id IN ('%s')";
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
