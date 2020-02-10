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

namespace Pim\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Catalog repository
 *
 * @author r.ratsun@treolabs.com
 */
class Catalog extends Base
{
    /**
     * @inheritDoc
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        /** @var string $id */
        $id = $entity->get('id');

        // remove catalog products
        $this->getEntityManager()->nativeQuery("UPDATE product SET deleted=1 WHERE catalog_id='$id'");

        parent::afterRemove($entity, $options);
    }

    /**
     * @inheritDoc
     */
    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        parent::afterUnrelate($entity, $relationName, $foreign, $options);

        if ($relationName == 'categories') {
            $this->unrelateProductsCategories((string)$entity->get('id'), is_string($foreign) ? $foreign : (string)$foreign->get('id'));
        }
    }

    /**
     * @param string $catalogId
     * @param string $categoryId
     */
    protected function unrelateProductsCategories(string $catalogId, string $categoryId): void
    {
        $ids = $this
            ->getEntityManager()
            ->nativeQuery(
                "SELECT pcl.id 
                     FROM product_category_linker pcl 
                         JOIN product p ON p.id=pcl.product_id AND p.deleted=0 
                         JOIN category c ON c.id=pcl.category_id AND c.deleted=0 
                     WHERE pcl.deleted=0 
                       AND p.catalog_id=:id 
                       AND c.category_route LIKE :likeRoute",
                [
                    'id'        => $catalogId,
                    'likeRoute' => "%|$categoryId|%",
                ]
            )
            ->fetchAll(\PDO::FETCH_COLUMN);

        $this
            ->getEntityManager()
            ->nativeQuery("UPDATE product_category_linker SET deleted=1 WHERE id IN ('" . implode("','", $ids) . "')");
    }
}
