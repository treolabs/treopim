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

namespace Espo\Modules\Pim\Migration;

use Treo\Core\Migration\AbstractMigration;

/**
 * Migration class for version 3.0.1
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot0Dot1 extends AbstractMigration
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        $this->catalogCategoryUp();
        $this->productCategoryUp();
        $this->masterCatalogUp();
        $this->channelsUp();
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
        $this->catalogCategoryDown();
        $this->productCategoryDown();
        $this->masterCatalogDown();
        $this->channelsDown();
    }

    /**
     * Migrate catalog categories up
     */
    protected function catalogCategoryUp(): void
    {
        $categories = $this->fetchAll("SELECT id FROM category WHERE category_parent_id IS NULL");
        $catalogs = $this->fetchAll("SELECT id FROM catalog");

        if (!empty($categories) && !empty($catalogs)) {
            $sql = "";
            foreach ($categories as $category) {
                foreach ($catalogs as $catalog) {
                    $sql .= "INSERT INTO catalog_category (catalog_id, category_id) VALUES ('" . $catalog['id'] . "', '" . $category['id'] . "');";
                }
            }
            $this->execute($sql);
        }
    }

    /**
     * Migrate product categories down
     */
    protected function productCategoryDown(): void
    {
        $this->execute("DELETE FROM product_category WHERE 1");
    }

    /**
     * Migrate product categories up
     */
    protected function productCategoryUp(): void
    {
        $sql
            = "SELECT pcl.* 
                FROM product_category_linker AS pcl 
                JOIN product AS p ON p.id=pcl.product_id 
                JOIN category AS c ON c.id=pcl.category_id 
                WHERE pcl.deleted=0 AND p.deleted=0 AND c.deleted=0";

        if (!empty($data = $this->fetchAll($sql))) {
            foreach ($data as $row) {
                $entity = $this->getEntityManager()->getEntity('ProductCategory');
                $entity->set('productId', $row['product_id']);
                $entity->set('categoryId', $row['category_id']);
                $entity->set('scope', 'Global');
                $entity->set('createdById', 'system');
                $entity->set('createdAt', date("Y-m-d H:i:s"));

                $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
            }
        }
    }

    /**
     * Migrate catelog categories down
     */
    protected function catalogCategoryDown(): void
    {
        $this->execute("DELETE FROM catalog_category WHERE 1");
    }

    /**
     * Migrate master catalog up
     */
    protected function masterCatalogUp(): void
    {
        $catalog = $this->getEntityManager()->getEntity('Catalog');
        $catalog->set(
            [
                'name'        => 'Main catalog',
                'code'        => 'main_catalog_migration',
                'description' => 'Auto generated catalog by migration.',
                'isActive'    => true,
            ]
        );
        $this->getEntityManager()->saveEntity($catalog);

        $this->execute("UPDATE product SET catalog_id='" . $catalog->get('id') . "' WHERE 1");

        $categories = $this
            ->getEntityManager()
            ->getRepository('Category')
            ->where(['categoryParentId' => null])
            ->find();
        if (count($categories) > 0) {
            foreach ($categories as $category) {
                $this
                    ->getEntityManager()
                    ->getRepository('Catalog')
                    ->relate($catalog, 'categories', $category);
            }
        }
    }

    /**
     * Migrate master catalog down
     */
    protected function masterCatalogDown(): void
    {
        $this->execute("DELETE FROM catalog WHERE code='main_catalog_migration'");
        $this->execute("UPDATE product SET catalog_id=NULL WHERE 1");
    }

    /**
     * Migrate channels up
     */
    protected function channelsUp()
    {
        $sql
            = "SELECT
                     p.id             AS productId,
                     c.id             AS categoryId,
                     c.category_route AS categoryRoute
                FROM product_category_linker AS pcl
                JOIN category AS c ON c.id=pcl.category_id AND c.deleted=0
                JOIN product AS p ON p.id=pcl.product_id AND p.deleted=0
                WHERE pcl.deleted = 0";

        $productCategories = [];
        foreach ($this->fetchAll($sql) as $row) {
            if (empty($productCategories[$row['productId']])) {
                $productCategories[$row['productId']] = [];
            }
            $categoryIds = [$row['categoryId']];
            foreach (explode("|", (string)$row['categoryRoute']) as $part) {
                if (!empty($part)) {
                    $categoryIds[] = $part;
                }
            }
            $productCategories[$row['productId']] = array_merge($productCategories[$row['productId']], $categoryIds);
        }

        $insertSql = "";
        foreach ($productCategories as $productId => $categories) {
            $sql
                = "SELECT 
                        channel.id as channelId
                   FROM channel
                   JOIN catalog ON catalog.id=channel.catalog_id AND catalog.deleted=0 AND catalog.category_id IN ('" . implode("','", $categories) . "')
                   WHERE channel.deleted = 0";

            foreach ($this->fetchAll($sql) as $row) {
                $insertSql .= "INSERT INTO product_channel (product_id, channel_id) VALUES ('$productId', '" . $row['channelId'] . "');";
            }
        }

        if (!empty($insertSql)) {
            $this->execute($insertSql);
        }
    }

    /**
     * Migrate channels down
     */
    protected function channelsDown()
    {
        $this->execute("DELETE FROM product_channel WHERE 1");
    }


    /**
     * @param string $sql
     *
     * @return mixed
     */
    protected function execute(string $sql)
    {
        $GLOBALS['log']->warning($sql);

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        return $sth;
    }

    /**
     * @param string $sql
     *
     * @return mixed
     */
    protected function fetchAll(string $sql)
    {
        return $this
            ->execute($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
