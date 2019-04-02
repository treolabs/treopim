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

namespace Espo\Modules\Pim\Repositories;

class ProductImage extends \Espo\Core\Templates\Repositories\Base
{
    /**
     * Get product images channels
     *
     * @param string $productId
     *
     * @return array
     */
    public function getProductImagesChannels(string $productId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            SELECT 
              pic.product_image_id,
              pic.channel_id
            FROM product_image_channel pic
            WHERE pic.product_id = :id AND pic.deleted = 0
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute([
            'id' => $productId
        ]);

        return $sth->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
    }
}
