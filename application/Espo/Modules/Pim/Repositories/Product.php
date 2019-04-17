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

namespace Espo\Modules\Pim\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

/**
 * Class Product
 *
 * @author r.ratsun@treolabs.com
 */
class Product extends \Espo\Core\Templates\Repositories\Base
{
    /**
     * Get products attributes
     *
     * @param array $ids
     *
     * @return EntityCollection[]
     */
    public function getProductsAttributes(array $ids): array
    {
        // prepare result
        $result = [];

        // prepare params
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'productId',
            'value'     => $ids
        ];

        if (!empty($data = $this->getCollection('ProductAttributeValue', $params))) {
            foreach ($data as $item) {
                $result[$item->get('productId')][] = $item;
            }
            foreach ($result as $k => $v) {
                $result[$k] = new EntityCollection($v);
            }
        }

        return $result;
    }

    /**
     * Get products channel attributes
     *
     * @param string $ids
     *
     * @return array
     */
    public function getProductsChannelAttributes(array $ids): array
    {
        // prepare result
        $result = [];

        // get channels ids
        $channelsIds = $this
            ->getEntityManager()
            ->getRepository('Channel')
            ->select(['id'])
            ->join('products')
            ->where(['products.id' => $ids])
            ->find()
            ->toArray();
        $channelsIds = array_column($channelsIds, 'id');

        // exit if no linked channels
        if (empty($channelsIds)) {
            return $result;
        }

        // get attributes data
        $attributes = $this->getProductsAttributes($ids);

        // exit if no linked attributes
        if (count($attributes) == 0) {
            return $result;
        };

        // prepare product attribute value ids
        $productAttributeIds = [];
        foreach ($attributes as $productId => $collection) {
            $productAttributeIds = array_merge($productAttributeIds, array_column($collection->toArray(), 'id'));
        }

        // prepare params
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'productAttributeId',
            'value'     => $productAttributeIds
        ];
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'channelId',
            'value'     => $channelsIds
        ];

        // get channel product attribute data
        if (empty($data = $this->getCollection('ChannelProductAttributeValue', $params))) {
            return $result;
        }

        foreach ($data as $v) {
            $exists[$v->get('productAttributeId') . "_" . $v->get('channelId')] = $v;
        }

        foreach ($ids as $id) {
            if (isset($attributes[$id])) {
                foreach ($channelsIds as $channelId) {
                    foreach ($attributes[$id] as $attribute) {
                        if (isset($exists[$attribute->get('id') . "_" . $channelId])) {
                            $result[$id][] = $exists[$attribute->get('id') . "_" . $channelId];
                        }
                    }
                }
            }
        }
        foreach ($result as $productId => $row) {
            $result[$productId] = new EntityCollection($row);
        }

        return $result;
    }

    /**
     * Get product(s) categiries
     *
     * @param array $productsIds
     * @param bool  $tree
     *
     * @return EntityCollection[]
     */
    public function getCategories(array $productsIds, bool $tree = false): array
    {
        // get data
        if (empty($data = $this->getCategoriesArray($productsIds, $tree))) {
            return [];
        }

        // prepare params
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'id',
            'value'     => array_column($data, 'categoryId')
        ];

        // get collection
        $categories = $this->getCollection('Category', $params);

        // prepare result
        $result = [];
        foreach ($categories as $category) {
            foreach ($data as $row) {
                if ($row['categoryId'] == $category->get('id')) {
                    $result[$row['productId']][] = $category;
                }
            }
        }

        // create collection
        foreach ($result as $productId => $categories) {
            $result[$productId] = new EntityCollection($categories);
        }

        return $result;
    }

    /**
     * Get products categories array data
     *
     * @param array $ids
     * @param bool  $isTree
     *
     * @return array
     */
    public function getCategoriesArray(array $ids, bool $isTree = false): array
    {
        return $this->getCategoriesArrayData($ids, $isTree);
    }

    /**
     * Translate
     *
     * @param string $key
     * @param string $label
     * @param string $scope
     *
     * @return string
     */
    public function translate(string $key, string $label, $scope = ''): string
    {
        return $this->getInjection('language')->translate($key, $label, $scope);
    }

    /**
     * @return array
     */
    public function getInputLanguageList(): array
    {
        return $this->getConfig()->get('inputLanguageList', []);
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        // call parent
        parent::init();

        // add dependencies
        $this->addDependency('serviceFactory');
        $this->addDependency('selectManagerFactory');
        $this->addDependency('language');
        $this->addDependency('acl');
    }

    /**
     * @inheritdoc
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        // save attributes
        $this->saveAttributes($entity);

        // save channel attributes
        $this->saveChannelAttributes($entity);

        // parent action
        parent::afterSave($entity, $options);
    }

    /**
     * @inheritdoc
     */
    protected function beforeRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = array())
    {
        // call parent
        parent::beforeRelate($entity, $relationName, $foreign, $data, $options);

        if ($relationName == 'categories') {
            // prepare category id
            $categoryId = (is_string($foreign)) ? $foreign : $foreign->get('id');

            $count = $this
                ->getEntityManager()
                ->getRepository('Category')
                ->where(['categoryParentId' => $categoryId])
                ->count();

            if (!empty($count)) {
                // prepare message
                $message = $this
                    ->getInjection('language')
                    ->translate('Category has children', 'exceptions', 'Category');

                // show message
                throw new BadRequest($message);
            }
        }
    }

    /**
     * Save product attribute values if it needs
     *
     * @param Entity $entity
     *
     * @return bool
     * @throws Error
     */
    protected function saveAttributes(Entity $entity): bool
    {
        if (empty($entity->productAttribute)) {
            return false;
        }

        // prepare attribute values
        $attributesValue = [];
        $pav = $this->getEntityManager()->getRepository('ProductAttributeValue')->where(['productId' => $entity->get('id')])->find();
        if (count($pav) > 0) {
            foreach ($pav as $v) {
                $attributesValue[$v->get('attributeId')] = $v;
            }
        }

        foreach ($entity->productAttribute as $id => $values) {
            // save value
            if (!isset($attributesValue[$id]) && $this->getInjection('acl')->check('ProductAttributeValue', 'create')) {
                $attributeValue = $this->getEntityManager()->getEntity('ProductAttributeValue');
                $attributeValue->set('productId', $entity->get('id'));
                $attributeValue->set('attributeId', $id);
            } elseif ($this->getInjection('acl')->check($attributesValue[$id], 'edit')) {
                $attributeValue = $attributesValue[$id];
            }

            // skip if not allowed
            if (empty($attributeValue)) {
                continue;
            }

            foreach ($values['locales'] as $locale => $value) {
                if ($locale == 'default') {
                    $attributeValue->set('value', $value);
                } else {
                    // prepare locale
                    $locale = Util::toCamelCase(strtolower($locale), '_', true);

                    $attributeValue->set("value$locale", $value);
                }
            }

            if (isset($values['data']) && !empty($values['data'])) {
                foreach ($values['data'] as $field => $item) {
                    $attributeValue->set($field, $item);
                }
            }


            $this->getEntityManager()->saveEntity($attributeValue, ['force' => true]);
        }

        return true;
    }

    /**
     * Save product channel attribute values if it needs
     *
     * @param Entity $entity
     *
     * @return bool
     * @throws Error
     */
    protected function saveChannelAttributes(Entity $entity): bool
    {
        if (empty($entity->productChannelAttribute)) {
            return false;
        }

        // prepare product attributes
        $productAttributes = [];

        // get existings product attributes
        $pav = $this->getEntityManager()->getRepository('ProductAttributeValue')->where(['productId' => $entity->get('id')])->find();
        if (count($pav) > 0) {
            foreach ($pav as $v) {
                $productAttributes[$v->get('attributeId')] = $v->get('id');
            }
        }

        // create product attributes if it needs
        if ($this->getInjection('acl')->check('ChannelProductAttributeValue', 'create')) {
            foreach ($entity->productChannelAttribute as $attributeId => $rows) {
                if (!isset($productAttributes[$attributeId])) {
                    $pa = $this->getEntityManager()->getEntity('ProductAttributeValue');
                    $pa->set('productId', $entity->get('id'));
                    $pa->set('attributeId', $attributeId);
                    $pa->set('value', null);
                    $this->getEntityManager()->saveEntity($pa, ['skipAll' => true]);
                    $productAttributes[$attributeId] = $pa->get('id');
                }
            }
        }

        // prepare exists
        $exists = [];
        $rows = $this
            ->getEntityManager()
            ->getRepository('ChannelProductAttributeValue')
            ->where(['productAttributeId' => array_values($productAttributes)])
            ->find();
        if (count($rows) > 0) {
            foreach ($rows as $item) {
                $exists[$item->get('productAttributeId') . "_" . $item->get('channelId')] = $item;
            }
        }

        foreach ($entity->productChannelAttribute as $attributeId => $rows) {
            // prepare product attribute id
            $productAttributeId = $productAttributes[$attributeId];

            foreach ($rows as $channelId => $values) {
                // prepare key
                $key = "{$productAttributeId}_{$channelId}";

                if (!empty($exists[$key]) && $this->getInjection('acl')->check($exists[$key], 'edit')) {
                    $item = $exists[$key];
                } elseif ($this->getInjection('acl')->check('ChannelProductAttributeValue', 'create')) {
                    $item = $this->getEntityManager()->getEntity('ChannelProductAttributeValue');
                    $item->set('channelId', $channelId);
                    $item->set('productAttributeId', $productAttributes[$attributeId]);
                }

                // skip if not allowed
                if (empty($item)) {
                    continue;
                }

                foreach ($values as $locale => $value) {
                    if ($locale == 'default') {
                        $item->set('value', $value);
                    } else {
                        // prepare locale
                        $locale = Util::toCamelCase(strtolower($locale), '_', true);

                        $item->set("value$locale", $value);
                    }
                }

                $this->getEntityManager()->saveEntity($item, ['skipAll' => true]);
            }
        }

        return true;
    }

    /**
     * Get collection by where param
     *
     * @param string $repositoryName
     * @param array  $params
     *
     * @return EntityCollection|null
     */
    protected function getCollection(string $repositoryName, array $params = []): ?EntityCollection
    {
        // prepare select params
        $selectParams = $this
            ->getInjection('selectManagerFactory')
            ->create($repositoryName)
            ->getSelectParams($params, true, true);

        return $this
            ->getEntityManager()
            ->getRepository($repositoryName)
            ->find($selectParams);

    }

    /**
     * @param array $productsIds
     *
     * @return array
     */
    protected function getMultiChannelAttributes(array $productsIds): array
    {
        $sql
            = "SELECT
                 p.id            as productId,
                 pav.id          as productAttributeId,
                 pav.value       as attributeValue
               FROM
                 product_family_attribute_linker AS pfal
               JOIN
                 product AS p ON p.product_family_id=pfal.product_family_id AND p.deleted=0
               JOIN
                 product_attribute_value AS pav ON pav.attribute_id=pfal.attribute_id AND pav.product_id=p.id AND pav.product_family_id=pfal.product_family_id AND pav.deleted=0
               WHERE
                     pfal.deleted=0
                 AND pfal.is_multi_channel=1
                 AND p.id IN ('" . implode("','", $productsIds) . "')";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data)) ? $data : [];
    }

    /**
     * @param array $ids
     * @param bool  $isTree
     *
     * @return array
     */
    protected function getCategoriesArrayData(array $ids, bool $isTree = false): array
    {
        // prepare result
        $result = [];

        // prepare types
        $types = implode("','", array_keys($this->getMetadata()->get('pim.productType')));

        $sql
            = "SELECT
                  product.id              AS productId,
                  product.name            AS productName,
                  product.type            AS productType,
                  product.is_active       AS productIsActive,
                  category.id             AS categoryId,
                  category.category_route AS categoryRoute
                FROM product_category_linker AS pcl
                JOIN category ON category.id=pcl.category_id AND category.deleted=0
                JOIN product ON product.id=pcl.product_id AND product.deleted=0
                WHERE pcl.deleted = 0 
                  AND product.type IN ('{$types}')";

        if (!empty($ids)) {
            $sql .= " AND pcl.product_id IN ('" . implode("','", $ids) . "')";
        }
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            foreach ($data as $row) {
                // push
                $result[] = $row;

                if ($isTree) {
                    if (!empty($row['categoryRoute'])) {
                        $categories = array_reverse(explode("|", $row['categoryRoute']));
                        foreach ($categories as $categoryId) {
                            if (!empty($categoryId)) {
                                // set categoryId
                                $row['categoryId'] = $categoryId;

                                // push
                                $result[] = $row;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
