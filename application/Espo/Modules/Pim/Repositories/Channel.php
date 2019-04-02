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

use \Espo\Modules\Pim\Core\Repositories\AbstractRepositories;
use Espo\ORM\EntityCollection;

/**
 * Channel repository
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Channel extends AbstractRepositories
{
    /**
     * Get channel products
     *
     * @param string $channelId
     * @param array $select
     *
     * @return EntityCollection|null
     */
    public function getProducts(string $channelId, array $select = []): ?EntityCollection
    {
        // prepare result
        $result = null;

        if (!empty($productsIds = $this->getProductsIds($channelId))) {
            $repository = $this
                ->getEntityManager()
                ->getRepository('Product');
            if (!empty($select)) {
                $repository->select($select);
            }

            // prepare result
            $result = $repository->where(['id' => $productsIds])->find();
        }

        return $result;
    }

    /**
     * Get channel products ids
     *
     * @param string $channelId
     *
     * @return array
     */
    public function getProductsIds(string $channelId): array
    {
        $result = [];

        if (!empty($channelId)) {
            $sql = "
             SELECT p.id
            FROM product p
              JOIN product_category_linker pcl
                ON pcl.product_id = p.id AND pcl.deleted = 0
              JOIN category cat
                ON cat.id = pcl.category_id AND cat.deleted = 0
              JOIN catalog ct
                ON ct.category_id = cat.id AND ct.deleted = 0
              JOIN channel ch
                ON ch.catalog_id = ct.id AND ch.deleted = 0
            WHERE p.deleted = 0 AND ch.id = :id";

            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute(['id' => $channelId]);

            $result = $sth->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_COLUMN);
        }

        return $result;
    }
}
