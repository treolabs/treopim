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

namespace Espo\Modules\Pim\Hooks\Product;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;

/**
 * Class ProductHook
 *
 * @author r.ratsun@treolabs.com
 */
class ProductHook extends \Espo\Modules\Pim\Core\Hooks\AbstractHook
{
    /**
     * @param Entity $product
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $product, $options = [])
    {
        // SKU validation
        if (!$this->isSkuUnique($product)) {
            throw new BadRequest($this->exception('Product with such SKU already exist'));
        }

        // check catalog
        if (!$this->isCatalogValid($product)) {
            throw new BadRequest($this->exception('Wrong catalog'));
        }
    }

    /**
     * @param Entity $product
     * @param string $field
     *
     * @return bool
     */
    protected function isSkuUnique(Entity $product): bool
    {
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['sku' => $product->get('sku'), 'catalogId' => $product->get('catalogId')])
            ->find();

        if (count($products) > 0) {
            foreach ($products as $item) {
                if ($item->get('id') != $product->get('id')) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param Entity $product
     *
     * @return bool
     */
    protected function isCatalogValid(Entity $product): bool
    {
        if (empty($catalog = $product->get('catalog')) || empty($categoryId = $catalog->get('categoryId'))) {
            return false;
        }

        if (!in_array($categoryId, $product->get('categoriesIds'))) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'Product');
    }
}
