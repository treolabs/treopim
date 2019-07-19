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

namespace Pim\Services;

use Espo\Core\Templates\Services\Base;

/**
 * Class ProductCategory
 *
 * @author r.ratsun@treolabs.com
 */
class ProductCategory extends Base
{
    /**
     * Remove ProductCategory by  categoryId and catalogId
     *
     * @param string $categoryId
     * @param string $catalogId
     */
    public function removeProductCategory(string $categoryId, string $catalogId): void
    {
        /** @var Category $serviceCategory */
        $serviceCategory = $this->getServiceFactory()->create('Category');
        // get id parent category and ids children category
        $categoriesIds = $serviceCategory->getIdsTree($categoryId);

        $productsCategory = $this
            ->getEntityManager()
            ->getRepository('ProductCategory')
            ->join('product')
            ->where(['categoryId' => $categoriesIds])
            ->where(['product.catalogId' => $catalogId])
            ->find()
            ->toArray();

        foreach ($productsCategory as $productCategory) {
            $this->deleteEntity($productCategory['id']);
        }
    }
}
