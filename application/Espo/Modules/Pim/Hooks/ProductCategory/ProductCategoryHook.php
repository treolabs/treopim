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

namespace Espo\Modules\Pim\Hooks\ProductCategory;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Hooks\Base as BaseHook;
use Espo\ORM\Entity;

/**
 * Class ProductCategoryHook
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class ProductCategoryHook extends BaseHook
{
    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        if (empty($product = $entity->get('product')) || empty($category = $entity->get('category'))) {
            throw new BadRequest($this->exception('Product and Category cannot be empty'));
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }

        if (count($category->get('categories')) > 0) {
            throw new BadRequest($this->exception('Category has child category'));
        }

        if (empty($catalog = $product->get('catalog'))) {
            throw new BadRequest($this->exception('No such product catalog'));
        }

        if (count($catalog->get('categories')) == 0) {
            throw new BadRequest($this->exception('No category trees in product catalog'));
        }

        if (!$this->isCategoryInCatalog($category, $catalog)) {
            throw new BadRequest($this->exception('Category should be in catalog trees'));
        }
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        // parent init
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('ProductCategory')
            ->where(
                [
                    'id!='       => $entity->get('id'),
                    'productId'  => $entity->get('productId'),
                    'categoryId' => $entity->get('categoryId'),
                    'scope'      => $entity->get('scope'),
                ]
            )
            ->count();

        return empty($count);
    }

    /**
     * @param Entity $category
     * @param Entity $catalog
     *
     * @return bool
     */
    protected function isCategoryInCatalog(Entity $category, Entity $catalog): bool
    {
        $categoryTree = array_merge([$category->get('id')], explode("|", (string)$category->get('categoryRoute')));
        foreach ($catalog->get('categories') as $tree) {
            if (in_array($tree->get('id'), $categoryTree)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'ProductCategory');
    }
}
