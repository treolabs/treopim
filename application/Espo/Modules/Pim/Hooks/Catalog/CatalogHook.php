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

namespace Espo\Modules\Pim\Hooks\Catalog;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\BadRequest;

/**
 * Class CatalogHook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CatalogHook extends \Espo\Modules\Pim\Core\Hooks\AbstractHook
{
    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterRemove(Entity $entity, $options = [])
    {
        // get products
        $products = $entity->get('products');

        // delete products
        if (count($products) > 0) {
            foreach ($products as $product) {
                $this->getEntityManager()->removeEntity($product);
            }
        }
    }
}
