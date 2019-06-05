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
use Espo\Modules\Pim\Core\Hooks\AbstractHook as BaseHook;

/**
 * Class ProductHook
 *
 * @author r.ratsun@treolabs.com
 */
class ProductHook extends BaseHook
{
    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        // is sku valid
        if (!$this->isSkuUnique($entity)) {
            if (isset($options['isImport']) && $options['isImport']) {
                $entity->setIsNew(false);
            } else {
                throw new BadRequest($this->exception('Product with such SKU already exist'));
            }
        }

        if ($entity->isAttributeChanged('catalogId')) {
            // is product categories in selected catalog
            $this->isProductCategoriesInSelectedCatalog($entity);
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        if (empty($options['skipProductFamilyHook']) && !empty($entity->get('productFamily')) && empty($entity->isDuplicate)) {
            $this->updateProductAttributesByProductFamily($entity);
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
     * @param Entity $entity
     *
     * @return bool
     * @throws BadRequest
     */
    protected function isProductCategoriesInSelectedCatalog(Entity $entity): bool
    {
        // get product categories
        $productCategories = $this
            ->getEntityManager()
            ->getRepository('ProductCategory')
            ->where(['productId' => $entity->get('id')])
            ->find();

        if (count($productCategories) > 0) {
            // get catalog categories ids
            $catalogCategories = array_column($entity->get('catalog')->get('categories')->toArray(), 'id');

            foreach ($productCategories as $productCategory) {
                // get category
                if (empty($category = $productCategory->get('category'))) {
                    throw new BadRequest($this->exception("No such category"));
                }

                if (empty($category->get('categoryParent'))) {
                    $root = $category->get('id');
                } else {
                    $tree = explode("|", (string)$category->get('categoryRoute'));
                    $root = null;
                    if (!empty($tree[1])) {
                        $root = $tree[1];
                    }
                }
                if (!in_array($root, $catalogCategories)) {
                    throw new BadRequest($this->exception("Some category cannot be linked with selected catalog"));
                }
            }
        }
        return true;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function updateProductAttributesByProductFamily(Entity $entity): bool
    {
        // get product family
        $productFamily = $entity->get('productFamily');

        // get product family attributes
        $productFamilyAttributes = $productFamily->get('productFamilyAttributes');

        if ($entity->isNew()) {
            if (count($productFamilyAttributes) > 0) {
                foreach ($productFamilyAttributes as $productFamilyAttribute) {
                    // create
                    $productAttributeValue = $this->getEntityManager()->getEntity('ProductAttributeValue');
                    $productAttributeValue->set(
                        [
                            'productId'                => $entity->get('id'),
                            'attributeId'              => $productFamilyAttribute->get('attributeId'),
                            'productFamilyAttributeId' => $productFamilyAttribute->get('id'),
                            'isRequired'               => $productFamilyAttribute->get('isRequired'),
                            'scope'                    => $productFamilyAttribute->get('scope')
                        ]
                    );
                    // save
                    $this->getEntityManager()->saveEntity($productAttributeValue);

                    // relate channels if it needs
                    if ($productFamilyAttribute->get('scope') == 'Channel') {
                        $channels = $productFamilyAttribute->get('channels');
                        if (count($channels) > 0) {
                            foreach ($channels as $channel) {
                                $this
                                    ->getEntityManager()
                                    ->getRepository('ProductAttributeValue')
                                    ->relate($productAttributeValue, 'channels', $channel);
                            }
                        }
                    }
                }
            }

            return true;
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
        return $this->getInjection('language')->translate($key, 'exceptions', 'Product');
    }
}
