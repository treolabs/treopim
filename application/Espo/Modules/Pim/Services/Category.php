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

namespace Espo\Modules\Pim\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\EntityCollection;

/**
 * Service of Category
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Category extends Base
{
    /**
     * @var array
     */
    protected $linkSelectParams
        = [
            'categoryImages' => [
                'order'             => 'ASC',
                'orderBy'           => 'category_image_category.sort_order',
                'additionalColumns' => [
                    'sortOrder' => 'sortOrder',
                    'scope'     => 'scope'
                ]
            ]
        ];

    /**
     * Get category entity
     *
     * @param string $id
     *
     * @return array
     * @throws Forbidden
     */
    public function getEntity($id = null)
    {
        // call parent
        $entity = parent::getEntity($id);

        // set hasChildren param
        $entity->set('hasChildren', $entity->hasChildren());

        return $entity;
    }

    /**
     * Is child category
     *
     * @param string $categoryId
     * @param string $selectedCategoryId
     *
     * @return bool
     */
    public function isChildCategory(string $categoryId, string $selectedCategoryId): bool
    {
        return in_array($selectedCategoryId, $this->getCategoryChildren($categoryId));
    }

    /**
     * Find linked Products for Category
     *
     * @param string $parentCategoryId current category
     * @param array  $params           select params
     *
     * @return array
     */
    public function findLinkedEntitiesProducts(string $parentCategoryId, array $params): array
    {
        $link = 'Product';
        // get children categories
        $categoriesId = $this->getCategoryChildren($parentCategoryId);
        $categoriesId['parent'] = $parentCategoryId;
        // set custom join
        $customJoin
            = "JOIN (SELECT DISTINCT `pcl`.`product_id` 
                                        FROM `product_category_linker` AS `pcl`
                                        WHERE 
                                            `pcl`.`deleted` = 0 
                                            AND `pcl`.`category_id` IN ('" . implode("', '", $categoriesId) . "'))
                                        AS link ON `link`.`product_id` = `product`.`id`";

        $data = $this->findCustomLinkedEntities($link, $params, $customJoin);

        return [
            'total' => $data['total'],
            'list'  => $this->setCategoriesToProducts($data['collection'], $categoriesId)
        ];
    }

    /**
     * Get attributes and linked products of duplicate category
     *
     * @param string $categoryId duplication category id
     *
     * @return object
     */
    public function getDuplicateAttributes($categoryId)
    {
        $attributes = parent::getDuplicateAttributes($categoryId);
        $products = $this->findLinkedEntitiesProducts($categoryId, []);

        foreach ($products['list'] as $product) {
            $attributes->{'productsIds'}[] = $product['id'];
            $attributes->{'productsNames'}{$product['id']}[] = $product['name'];
        }

        return $attributes;
    }

    /**
     * Set categories for products
     *
     * @param EntityCollection $products
     * @param array            $categoriesId children categories and parent category Id
     *
     * @return array
     */
    protected function setCategoriesToProducts(EntityCollection $products, array $categoriesId): array
    {
        $pdo = $this->getEntityManager()->getPDO();
        // select categories links with products
        $sql
            = "SELECT
                  pcl.product_id,
                  pcl.category_id,
                  cat.name
                FROM product_category_linker AS pcl
                  JOIN category AS cat ON cat.id = pcl.category_id
                WHERE pcl.deleted = 0 AND pcl.category_id IN ('" . implode("', '", $categoriesId) . "')";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $categories = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = $products->toArray();
        // set categories
        foreach ($result as $key => $product) {
            foreach ($categories as $catKey => $categoryVal) {
                if ($product['id'] === $categoryVal['product_id']) {
                    $result[$key]['categories'][] = (string)$categoryVal['name'];
                    // if this current category relate with this product - set isEditable
                    if ($categoryVal['category_id'] == $categoriesId['parent'] || $result[$key]['isEditable']) {
                        $result[$key]['isEditable'] = true;
                    } else {
                        $result[$key]['isEditable'] = false;
                    }
                    unset($categories[$catKey]);
                }
            }
        }

        return $result;
    }

    /**
     * Find linked Entities with the use of custom relation
     * prepare selectParams for query and entityCollection for output
     *
     * @param string $link       name of the related entities
     * @param array  $params
     * @param string $customJoin custom relation (left, right or inner join)
     *
     * @return array
     * @throws Forbidden
     */
    protected function findCustomLinkedEntities(string $link, array $params, string $customJoin): array
    {
        // check acl for related entity
        if (!$this->getAcl()->check($link, 'read')) {
            throw new Forbidden();
        }
        // prepare select params
        $selectParams = $this->getSelectManager($link)->getSelectParams($params, true);
        $selectParams['customJoin'] = $customJoin;
        $this->getEntityManager()->getRepository($link)->handleSelectParams($selectParams);

        // find linked entities
        $collection = $this->getRepository()->findCustomLinkedEntities($link, $selectParams);
        // prepare entity for output
        $recordService = $this->getRecordService($link);
        foreach ($collection as $entity) {
            $recordService->loadAdditionalFieldsForList($entity);
            $recordService->prepareEntityForOutput($entity);
        }

        return [
            'collection' => $collection,
            'total'      => $this->getRepository()->getCustomTotal($link, $selectParams)
        ];
    }


    /**
     * Get routes
     *
     * @param array $data
     *
     * @return array
     */
    protected function getRoutes(array $data): array
    {

        foreach ($data as $productId => $route) {
            if (!empty($route)) {
                $ids = [];
                foreach (explode("|", $route) as $id) {
                    if (!empty($id)) {
                        $ids[] = $id;
                    }
                }
                $data[$productId] = $ids;
            } else {
                $data[$productId] = [];
            }
        }

        return $data;
    }

    /**
     * Get categories
     *
     * @param array $ids
     *
     * @return array
     */
    protected function getCategories(array $ids): array
    {
        // prepare result
        $result = [];

        $categories = $this
            ->getEntityManager()
            ->getRepository('Category')
            ->select(['id', 'name'])
            ->where(['id' => $ids])
            ->find();

        if (!empty($categories)) {
            foreach ($categories->toArray() as $category) {
                $result[$category['id']] = trim($category['name']);
            }
        }

        return $result;
    }

    /**
     * @param string $id
     *
     * @return array
     */
    protected function getCategoryChildren(string $id): array
    {
        // prepare result
        $result = [];

        if (!empty($data = $this->getRepository()->getChildren($id, ['id']))) {
            $result = array_column($data->toArray(), 'id');
        }

        return $result;
    }
}
