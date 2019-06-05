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

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;
use Espo\Modules\Pim\Services\GeneralStatisticsDashlet;
use Espo\Modules\Pim\Services\Product as ProductService;
use Treo\Core\EventManager\Event;

/**
 * Product select manager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Product extends AbstractSelectManager
{
    /**
     * @inheritdoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        // include category tree in search on categories
        if (!empty($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $i => $p) {
                if (!empty($p['attribute']) && $p['attribute'] == 'categories') {
                    $children = [];
                    foreach ($p['value'] as $id) {
                        // get children
                        $rowChildren = $this
                            ->getEntityManager()
                            ->getRepository('Category')
                            ->select(['id'])
                            ->where(['categoryRoute*' => "%{$id}%"])
                            ->find()
                            ->toArray();
                        $children = array_merge($children, array_column($rowChildren, 'id'));
                    }
                    $params['where'][$i]['value'] = array_merge($params['where'][$i]['value'], $children);
                }
            }
        }

        // filtering by product types
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'type',
            'value'     => array_keys($this->getMetadata()->get('pim.productType', []))
        ];

        // call parent
        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }

    /**
     * Products without associated products filter
     *
     * @param $result
     */
    protected function boolFilterWithoutAssociatedProducts(&$result)
    {
        $result['whereClause'][] = [
            'id' => array_column($this->getProductWithoutAssociatedProduct(), 'id')
        ];
    }

    /**
     * @param array $result
     */
    protected function boolFilterOnlyCatalogProducts(&$result)
    {
        if (!empty($category = $this->getEntityManager()->getEntity('Category', (string)$this->getSelectCondition('notLinkedWithCategory')))) {
            // prepare ids
            $ids = ['-1'];

            // get root id
            if (empty($category->get('categoryParent'))) {
                $rootId = $category->get('id');
            } else {
                $tree = explode("|", (string)$category->get('categoryRoute'));
                $rootId = (!empty($tree[1])) ? $tree[1] : null;
            }

            if (!empty($rootId)) {
                $catalogs = $this
                    ->getEntityManager()
                    ->getRepository('Catalog')
                    ->distinct()
                    ->join('categories')
                    ->where(['categories.id' => $rootId])
                    ->find();

                if (count($catalogs) > 0) {
                    foreach ($catalogs as $catalog) {
                        $ids = array_merge($ids, array_column($catalog->get('products')->toArray(), 'id'));
                    }
                }
            }

            // prepare where
            $result['whereClause'][] = [
                'id' => $ids
            ];
        }
    }

    /**
     * Get product without AssociatedProduct
     *
     * @return array
     */
    protected function getProductWithoutAssociatedProduct(): array
    {
        return $this->fetchAll($this->getGeneralStatisticService()->getQueryProductWithoutAssociatedProduct());
    }

    /**
     * Products without Category filter
     *
     * @param $result
     */
    protected function boolFilterWithoutAnyCategory(&$result)
    {
        $result['whereClause'][] = [
            'id' => array_column($this->getProductWithoutCategory(), 'id')
        ];
    }

    /**
     * Get product without Category
     *
     * @return array
     */
    protected function getProductWithoutCategory(): array
    {
        return $this->fetchAll($this->getGeneralStatisticService()->getQueryProductWithoutCategory());
    }

    /**
     * Products without Attribute filter
     *
     * @param $result
     */
    protected function boolFilterWithoutProductAttributes(&$result)
    {
        $result['whereClause'][] = [
            'id' => array_column($this->getProductWithoutProductAttributes(), 'id')
        ];
    }

    /**
     * Get product without Attribute
     *
     * @return array
     */
    protected function getProductWithoutProductAttributes(): array
    {
        return $this->fetchAll($this->getGeneralStatisticService()->getQueryProductWithoutAttribute());
    }

    /**
     * Products without Image filter
     *
     * @param $result
     */
    protected function boolFilterWithoutImageAssets(&$result)
    {
        $result['whereClause'][] = [
            'id' => array_column($this->getProductWithoutImageAssets(), 'id')
        ];
    }

    /**
     * Get products without Image
     *
     * @return array
     */
    protected function getProductWithoutImageAssets(): array
    {
        return $this->fetchAll($this->getGeneralStatisticService()->getQueryProductWithoutImage());
    }

    /**
     * NotAssociatedProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotAssociatedProducts(&$result)
    {
        // prepare data
        $data = (array)$this->getSelectCondition('notAssociatedProducts');

        if (!empty($data['associationId'])) {
            $associatedProducts = $this->getAssociatedProducts($data['associationId'], $data['mainProductId']);
            foreach ($associatedProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string)$row['related_product_id']
                ];
            }
        }
    }

    /**
     * OnlySimple filter
     *
     * @param array $result
     */
    protected function boolFilterOnlySimple(&$result)
    {
        $result['whereClause'][] = [
            'type' => 'simpleProduct'
        ];
    }

    /**
     * NotConfigurabledProducts filter
     *
     * @param array $result
     */
    protected function boolFilterNotConfigurabledProducts(&$result)
    {
        // prepare data
        $productId = (string)$this->getSelectCondition('notConfigurabledProducts');

        if (!empty($productId)) {
            $variants = $this->getProductVariants($productId);
            foreach ($variants as $id) {
                $result['whereClause'][] = [
                    'id!=' => (string)$id
                ];
            }
        }
    }

    /**
     * NotBundledProducts filter
     *
     * @param array $result
     */
    protected function boolFilterNotBundledProducts(&$result)
    {
        //prepare data
        $productId = (string)$this->getSelectCondition('notBundledProducts');

        if (!empty($productId)) {
            $variants = $this->getBundleItems($productId);
            foreach ($variants as $id) {
                $result['whereClause'][] = [
                    'id!=' => (string)$id
                ];
            }
        }
    }

    /**
     * Get assiciated products
     *
     * @param string $associationId
     * @param string $productId
     *
     * @return array
     */
    protected function getAssociatedProducts($associationId, $productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT
          related_product_id
        FROM
          associated_product
        WHERE
          main_product_id =' . $pdo->quote($productId) . '
          AND association_id = ' . $pdo->quote($associationId) . '
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get product variants
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getProductVariants($productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT
          product_id
        FROM
          product_type_configurable
        WHERE
          configurable_product_id =' . $pdo->quote($productId) . '
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'product_id') : [];
    }

    /**
     * Get bundle items
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getBundleItems($productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT
          product_id
        FROM
          product_type_bundle
        WHERE
          bundle_product_id =' . $pdo->quote($productId) . '
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'product_id') : [];
    }

    /**
     * NotLinkedWithOrder filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithOrder(&$result)
    {
        $orderId = (string)$this->getSelectCondition('notLinkedWithOrder');

        if (!empty($orderId)) {
            $orderProducts = $this->getOrderProducts($orderId);
            foreach ($orderProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string)$row['product_id']
                ];
            }
        }
    }

    /**
     * Get order products
     *
     * @param string $orderId
     *
     * @return array
     */
    protected function getOrderProducts($orderId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT product_id
                FROM order_product
                WHERE order_id = ' . $pdo->quote($orderId);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithChannel filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithChannel(&$result)
    {
        $channelId = (string)$this->getSelectCondition('notLinkedWithChannel');

        if (!empty($channelId)) {
            $channelProducts = $this->createService('Channel')->getProducts($channelId);
            foreach ($channelProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string)$row['productId']
                ];
            }
        }
    }

    /**
     * NotLinkedWithBrand filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithBrand(array &$result)
    {
        // prepare data
        $brandId = (string)$this->getSelectCondition('notLinkedWithBrand');

        if (!empty($brandId)) {
            // get Products linked with brand
            $products = $this->getBrandProducts($brandId);
            foreach ($products as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productId']
                ];
            }
        }
    }

    /**
     * Get productIds related with brand
     *
     * @param string $brandId
     *
     * @return array
     */
    protected function getBrandProducts(string $brandId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT id AS productId
                FROM product
                WHERE deleted = 0 
                      AND brand_id = :brandId';

        $sth = $pdo->prepare($sql);
        $sth->execute(['brandId' => $brandId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithProductFamily filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProductFamily(array &$result)
    {
        // prepare data
        $productFamilyId = (string)$this->getSelectCondition('notLinkedWithProductFamily');

        if (!empty($productFamilyId)) {
            // get Products linked with brand
            $products = $this->getProductFamilyProducts($productFamilyId);
            foreach ($products as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productId']
                ];
            }
        }
    }

    /**
     * Get productIds related with productFamily
     *
     * @param string $productFamilyId
     *
     * @return array
     */
    protected function getProductFamilyProducts(string $productFamilyId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT id AS productId
                FROM product
                WHERE deleted = 0
                      AND product_family_id = :productFamilyId';

        $sth = $pdo->prepare($sql);
        $sth->execute(['productFamilyId' => $productFamilyId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithPackaging filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithPackaging(&$result)
    {
        // find products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(
                [
                    'packagingId' => (string)$this->getSelectCondition('notLinkedWithPackaging')
                ]
            )
            ->find();

        if (!empty($products)) {
            foreach ($products as $product) {
                $result['whereClause'][] = [
                    'id!=' => $product->get('id')
                ];
            }
        }
    }

    /**
     * Fetch all result from DB
     *
     * @param string $query
     *
     * @return array
     */
    protected function fetchAll(string $query): array
    {
        $sth = $this->getEntityManager()->getPDO()->prepare($query);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create dashlet service
     *
     * @return GeneralStatisticsDashlet
     */
    protected function getGeneralStatisticService(): GeneralStatisticsDashlet
    {
        return $this->createService('GeneralStatisticsDashlet');
    }

    /**
     * NotLinkedWithProductSerie filter
     *
     * @param $result
     */
    protected function boolFilterNotLinkedWithProductSerie(&$result)
    {
        //find products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->join(['productSerie'])
            ->where(
                [
                    'productSerie.id' => (string)$this->getSelectCondition('notLinkedWithProductSerie')
                ]
            )
            ->find();

        // add product ids to whereClause
        if (!empty($products)) {
            foreach ($products as $product) {
                $result['whereClause'][] = [
                    'id!=' => $product->get('id')
                ];
            }
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterLinkedWithCategory(array &$result)
    {
        // prepare category
        $category = $this
            ->getEntityManager()
            ->getEntity('Category', (string)$this->getSelectCondition('linkedWithCategory'));

        if (!empty($category)) {
            // get category tree products
            $products = $category->getTreeProducts();

            $result['whereClause'][] = [
                'id' => count($products > 0) ? array_column($products->toArray(), 'id') : []
            ];
        }
    }
}
