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

use Espo\Core\Utils\Util;
use Slim\Http\Request;
use PDO;

/**
 * ProductImage service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductImage extends AbstractImageService
{

    /**
     * @var array
     */
    protected $linkSelectParams
        = [
            'products' => [
                'additionalColumns' => [
                    'sortOrder' => 'sortOrder',
                ]
            ]
        ];

    /**
     * Update ProductImage channels
     *
     * @param string $productId
     * @param string $productImageId
     * @param array  $channels
     *
     * @return bool
     */
    public function updateProductImageChannels(string $productId, string $productImageId, $channels): bool
    {
        /**
         * Delete old records
         */
        $sql = "DELETE FROM product_image_channel WHERE product_image_id='%s' AND product_id='%s';";
        $sql = sprintf($sql, $productImageId, $productId);


        /**
         * Create new records
         */
        if (!empty($channels) && is_array($channels)) {
            $template = "INSERT INTO product_image_channel SET id='%s', product_image_id='%s', product_id='%s'";
            foreach ($channels as $channelId) {
                // prepare data
                $target = $template . ", channel_id='%s';";
                $id = Util::generateId();

                $sql .= sprintf($target, $id, $productImageId, $productId, $channelId);
            }
        }

        // execute
        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        return true;
    }

    /**
     * Get ProductImage channels
     *
     * @param string  $productId
     * @param string  $productImageId
     * @param Request $request
     *
     * @return array
     */
    public function getProductImageChannels(string $productId, string $productImageId, Request $request): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        /**
         * Get from DB
         */
        $sql
            = "SELECT
                  pic.channel_id AS id,
                  c.name AS name
                FROM product_image_channel AS pic
                JOIN channel AS c ON c.id=pic.channel_id
                WHERE pic.deleted = 0
                      AND pic.product_id = '{$productId}'
                      AND pic.product_image_id = '{$productImageId}'";
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);

        // prepare result
        if (!empty($data)) {
            $result = [
                'total' => count($data),
                'list'  => $data
            ];
        }

        return $result;
    }

    /**
     * Update product image sort order
     *
     * @param string $productId
     * @param array  $data
     *
     * @return bool
     */
    public function updateSortOrder(string $productId, array $data): bool
    {
        // prepare data
        $result = false;

        if (!empty($data)) {
            $template
                = "UPDATE product_image_product SET sort_order = %s 
                      WHERE product_image_id = '%s' AND product_id = '%s';";
            $sql = '';
            foreach ($data as $k => $productImageId) {
                $sql .= sprintf($template, $k, $productImageId, $productId);
            }

            // update DB data
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }
}
