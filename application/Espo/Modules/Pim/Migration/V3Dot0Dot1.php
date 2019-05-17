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
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
        $this->catalogCategoryDown();
        $this->productCategoryDown();
        $this->masterCatalogDown();
    }

    /**
     * Migrate catalog categories up
     */
    protected function catalogCategoryUp(): void
    {
        $categories = $this
            ->getEntityManager()
            ->getRepository('Category')
            ->where(['categoryParentId' => null])
            ->find();

        if (count($categories) > 0) {
            $catalogs = $this
                ->getEntityManager()
                ->getRepository('Catalog')
                ->find();

            if (count($catalogs) > 0) {
                foreach ($catalogs as $catalog) {
                    foreach ($categories as $category) {
                        $this
                            ->getEntityManager()
                            ->getRepository('Catalog')
                            ->relate($catalog, 'categories', $category);
                    }
                }
            }
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
     * @param string $sql
     *
     * @return mixed
     */
    private function execute(string $sql)
    {
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
    private function fetchAll(string $sql)
    {
        return $this
            ->execute($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
