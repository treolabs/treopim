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

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\Pim\Entities\Category;
use Espo\Modules\Pim\Entities\Product;
use Treo\Listeners\AbstractListener;

/**
 * Class Mapper
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class Mapper extends AbstractListener
{
    /**
     * @param array $event
     *
     * @return array
     * @throws BadRequest
     */
    public function beforeAddRelation(array $event): array
    {
        if (($event['entity']->getEntityType() == 'Product' && $event['relationName'] == 'categories' && !$this->isValidCategory($event['entity'], $event['relEntity']))
            || ($event['entity']->getEntityType() == 'Category' && $event['relationName'] == 'products' && !$this->isValidCategory($event['relEntity'], $event['entity']))) {
            throw new BadRequest('You cannot linked current product with selected category');
        }

        return $event;
    }

    /**
     * @param Product  $product
     * @param Category $category
     *
     * @return bool
     */
    protected function isValidCategory($product, $category): bool
    {
        // exit if empty
        if (empty($product) || empty($category)) {
            return false;
        }

        // get catalog
        if (empty($catalog = $product->get('catalog'))) {
            return false;
        }

        // get catalog categories trees
        $trees = $catalog->get('categories');

        if (count($trees) == 0) {
            return false;
        }

        // prepare category tree
        $categoryTree = array_merge([$category->get('id')], explode("|", $category->get('categoryRoute')));

        foreach ($trees as $tree) {
            if (in_array($tree->get('id'), $categoryTree)) {
                return true;
            }
        }

        return false;
    }
}
