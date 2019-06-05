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

namespace Treo\Migrations\Pim;

use Treo\Core\Migration\AbstractMigration;
use Espo\Modules\Pim\Hooks\Category\CategoryTreeHook;

/**
 * Migration class for version 1.7.1
 *
 * @author r.ratsun@treolabs.com
 */
class V1Dot7Dot1 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $this->refreshCategoryRouteName();
    }

    /**
     * Refresh all category routes
     */
    protected function refreshCategoryRouteName(): void
    {
        $categories = $this
            ->getEntityManager()
            ->getRepository('Category')
            ->find();

        $sql = '';
        foreach ($categories as $category) {
            if (!empty($route = CategoryTreeHook::getCategoryRoute($category, true))) {
                // prepare id
                $id = $category->get('id');
                $sql .= "UPDATE category SET category_route_name='{$route}' WHERE id='{$id}';";
            }
        }

        if (!empty($sql)) {
            // execute
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();
        }
    }
}
