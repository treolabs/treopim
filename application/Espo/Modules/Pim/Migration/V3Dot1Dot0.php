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
 * Migration class for version 3.1.0
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot1Dot0 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $sql
            = "SELECT pcl.* 
                FROM product_category_linker AS pcl 
                JOIN product AS p ON p.id=pcl.product_id 
                JOIN category AS c ON c.id=pcl.category_id 
                WHERE pcl.deleted=0 AND p.deleted=0 AND c.deleted=0";

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
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
}
