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

namespace Espo\Modules\Pim\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Espo\Orm\EntityManager;
use Espo\Core\Utils\Util;
use Slim\Http\Request;
use \PDO;

/**
 * Service of Product
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Product extends AbstractService
{

    /**
     * @var array
     */
    protected $linkSelectParams
        = [
            'productImages' => [
                'order'             => 'ASC',
                'orderBy'           => 'product_image_product.sort_order',
                'additionalColumns' => [
                    'sortOrder' => 'sortOrder',
                    'scope'     => 'scope'
                ]
            ]
        ];

    /**
     * @param \stdClass $data
     *
     * @return bool
     */
    public function addAssociateProducts(\stdClass $data): bool
    {
        if (empty($data->ids)
            || empty($data->foreignIds)
            || empty($data->associationId)
            || !is_array($data->ids)
            || !is_array($data->foreignIds)
            || empty($association = $this->getEntityManager()->getEntity("Association", $data->associationId))) {
            return false;
        }

        // prepare repository
        $repository = $this->getEntityManager()->getRepository("AssociatedProduct");

        // find exists entities
        $entities = $repository->where(
            [
                'associationId'    => $data->associationId,
                'mainProductId'    => $data->ids,
                'relatedProductId' => $data->foreignIds
            ]
        )->find();

        // prepare exists
        $exists = [];
        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $exists[] = $entity->get("associationId") . "_" . $entity->get("mainProductId") .
                    "_" . $entity->get("relatedProductId");
            }
        }

        foreach ($data->ids as $mainProductId) {
            foreach ($data->foreignIds as $relatedProductId) {
                if (!in_array($data->associationId . "_{$mainProductId}_{$relatedProductId}", $exists)) {
                    $entity = $repository->get();
                    $entity->set("associationId", $data->associationId);
                    $entity->set("mainProductId", $mainProductId);
                    $entity->set("relatedProductId", $relatedProductId);

                    // for backward association
                    if (!empty($backwardAssociationId = $association->get('backwardAssociationId'))) {
                        $entity->set('backwardAssociationId', $backwardAssociationId);

                        $backwardEntity = $repository->get();
                        $backwardEntity->set("associationId", $backwardAssociationId);
                        $backwardEntity->set("mainProductId", $relatedProductId);
                        $backwardEntity->set("relatedProductId", $mainProductId);

                        $this->getEntityManager()->saveEntity($backwardEntity);
                    }

                    $this->getEntityManager()->saveEntity($entity);
                }
            }
        }

        return true;
    }

    /**
     * Remove product association
     *
     * @param \stdClass $data
     *
     * @return bool
     */
    public function removeAssociateProducts(\stdClass $data): bool
    {
        if (empty($data->ids) || empty($data->foreignIds) || empty($data->associationId)) {
            return false;
        }

        // find associated products
        $associatedProducts = $this
            ->getEntityManager()
            ->getRepository('AssociatedProduct')
            ->where(
                [
                    'associationId'    => $data->associationId,
                    'mainProductId'    => $data->ids,
                    'relatedProductId' => $data->foreignIds
                ]
            )
            ->find();

        if (count($associatedProducts) > 0) {
            foreach ($associatedProducts as $associatedProduct) {
                // for backward association
                if (!empty($backwardAssociationId = $associatedProduct->get('backwardAssociationId'))) {
                    $backwards = $associatedProduct->get('backwardAssociation')->get('associatedProducts');

                    if (count($backwards) > 0) {
                        foreach ($backwards as $backward) {
                            if ($backward->get('mainProductId') == $associatedProduct->get('relatedProductId')
                                && $backward->get('relatedProductId') == $associatedProduct->get('mainProductId')
                                && $backward->get('associationId') == $backwardAssociationId) {
                                $this->getEntityManager()->removeEntity($backward);
                            }
                        }
                    }
                }

                // remove associated product
                $this->getEntityManager()->removeEntity($associatedProduct);
            }
        }

        return true;
    }

    /**
     * Get item in products data
     *
     * @param string  $productId
     * @param Request $request
     *
     * @return array
     */
    public function getItemInProducts(string $productId, Request $request): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // get total
        $total = $this->getDbCountItemInProducts($productId);

        if (!empty($total)) {
            // prepare result
            $result = [
                'total' => $total,
                'list'  => $this->getDbItemInProducts($productId, $request)
            ];
        }

        return $result;
    }

    /**
     * Get Product Attributes
     *
     * @param string $productId
     *
     * @return array
     */
    public function getAttributes(string $productId): array
    {
        // check ACL
        if (!$this->getAcl()->check('ProductAttributeValue', 'read')) {
            // prepare message
            $message = $this->getTranslate("You have no ACL rights to read attribute values", 'exceptions', 'ProductAttributeValue');

            throw new Forbidden($message);
        }

        // get product
        if (empty($product = $this->getEntityManager()->getEntity('Product', $productId))) {
            throw new NotFound();
        }

        // prepare result
        $result = [];

        if (!empty($attributeValues = $product->getProductAttributes()) && count($attributeValues) > 0) {
            // get config data
            $isMultilangActive = $this->getConfig()->get('isMultilangActive');
            $inputLanguageList = $this->getConfig()->get('inputLanguageList');
            $multilangFields = $this->getConfig()->get('modules')['multilangFields'];

            foreach ($attributeValues as $attributeValue) {
                // prepare data
                $attribute = $attributeValue->get('attribute');
                $productFamily = $attributeValue->get('productFamily');
                $attributeGroup = $attribute->get('attributeGroup');
                $isRequired = false;
                if (!empty($productFamily)) {
                    $isRequired = $productFamily->isAttributeRequired($attributeValue->get('attributeId'));
                }

                // prepare teams data
                $teamsData = [];
                if (!empty($teams = $attributeValue->get('teams'))) {
                    $teamsData = $teams->toArray();
                }

                // prepare item
                $item = [
                    'productAttributeValueId' => $attributeValue->get('id'),
                    'attributeId'             => $attributeValue->get('attributeId'),
                    'name'                    => $attributeValue->get('attributeName'),
                    'type'                    => $attribute->get('type'),
                    'isRequired'              => $isRequired,
                    'editable'                => $this->getAcl()->check($attributeValue, 'edit'),
                    'deletable'               => $this->getAcl()->check($attributeValue, 'delete'),
                    'attributeGroupId'        => $attribute->get('attributeGroupId'),
                    'attributeGroupName'      => $attribute->get('attributeGroupName'),
                    'attributeGroupOrder'     => (!empty($attributeGroup)) ? $attributeGroup->get('sortOrder') : 0,
                    'isCustom'                => empty($attributeValue->get('productFamilyId')),
                    'value'                   => $attributeValue->get('value'),
                    'typeValue'               => $attribute->get('typeValue'),
                    'sortOrder'               => $attribute->get('sortOrder'),
                    'ownerUserId'             => $attributeValue->get('ownerUserId'),
                    'ownerUserName'           => $attributeValue->get('ownerUserName'),
                    'assignedUserId'          => $attributeValue->get('assignedUserId'),
                    'assignedUserName'        => $attributeValue->get('assignedUserName'),
                    'teamsIds'                => array_column($teamsData, 'id'),
                    'teamsNames'              => array_column($teamsData, 'name', 'id'),
                    'data'                    => $attributeValue->get('data')
                ];

                // for multilang
                if ($isMultilangActive) {
                    foreach ($inputLanguageList as $locale) {
                        // prepare locale
                        $locale = Util::toCamelCase(strtolower($locale), '_', true);

                        // push
                        $item["name{$locale}"] = $attribute->get("name{$locale}");
                        $item["value{$locale}"] = $attributeValue->get("value{$locale}");
                        $item["typeValue{$locale}"] = $attribute->get("typeValue{$locale}");
                    }
                } elseif (!empty($multilangFields[$item['type']])) {
                    $item['type'] = $multilangFields[$item['type']]['fieldType'];
                }

                // push
                $result[] = $item;
            }
        }

        return $this->formatAttributeData($result);
    }

    /**
     * Get Channel product attributes
     *
     * @param string $productId
     *
     * @return array
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getChannelAttributes(string $productId): array
    {
        // check ACL
        if (!$this->getAcl()->check('ChannelProductAttributeValue', 'read')) {
            throw new Forbidden();
        }

        // get product
        if (empty($product = $this->getEntityManager()->getEntity('Product', $productId))) {
            throw new NotFound("No such product");
        }

        // prepare result
        $result = [];

        // get channels
        $channels = $product->get('channels');

        // push channels
        if (count($channels) > 0) {
            foreach ($channels as $channel) {
                $result[$channel->get('id')]['channelId'] = $channel->get('id');
                $result[$channel->get('id')]['channelName'] = $channel->get('name');
                $result[$channel->get('id')]['locales'] = $channel->get('locales');
                $result[$channel->get('id')]['attributes'] = [];
            }
        }

        // push attributes
        if (!empty($data = $product->getProductChannelAttributes())) {
            foreach ($data as $k => $item) {
                // prepare data
                $teamsData = [];
                if (!empty($teams = $item->get('teams'))) {
                    $teamsData = $teams->toArray();
                }
                $productAttribute = $item->get('productAttribute');
                $attribute = $productAttribute->get('attribute');
                $attributeGroup = $attribute->get('attributeGroup');
                $attributeGroupOrder = (!empty($attributeGroup)) ? $attributeGroup->get('sortOrder') : null;
                $attributeValue = $this->prepareValue($attribute->get('type'), (string)$item->get('value'));
                $attributeIsRequired = false;
                $attributeIsMultiChannel = false;
                if (!empty($productFamily = $product->get('productFamily'))) {
                    $attributeIsRequired = $productFamily->isAttributeRequired($productAttribute->get('attributeId'));
                    $attributeIsMultiChannel = $productFamily->isAttributeMultiChannel($productAttribute->get('attributeId'));
                }

                // push
                $result[$item->get('channelId')]['attributes'][$k] = [
                    'channelProductAttributeValueId' => $item->get('id'),
                    'productId'                      => $productAttribute->get('productId'),
                    'attributeId'                    => $productAttribute->get('attributeId'),
                    'attributeName'                  => $productAttribute->get('attributeName'),
                    'attributeType'                  => $attribute->get('type'),
                    'attributeData'                  => $item->get('data'),
                    'attributeIsRequired'            => $attributeIsRequired,
                    'attributeIsMultiChannel'        => $attributeIsMultiChannel,
                    'attributeGroupId'               => $attribute->get('attributeGroupId'),
                    'attributeGroupName'             => $attribute->get('attributeGroupName'),
                    'attributeGroupOrder'            => $attributeGroupOrder,
                    'editable'                       => $this->getAcl()->check($item, 'edit'),
                    'deletable'                      => $this->getAcl()->check($item, 'delete'),
                    'ownerUserId'                    => $item->get('ownerUserId'),
                    'ownerUserName'                  => $item->get('ownerUserName'),
                    'assignedUserId'                 => $item->get('assignedUserId'),
                    'assignedUserName'               => $item->get('assignedUserName'),
                    'teamsIds'                       => array_column($teamsData, 'id'),
                    'teamsNames'                     => array_column($teamsData, 'name', 'id'),
                    'attributeTypeValue'             => $attribute->get('typeValue'),
                    'attributeValue'                 => $attributeValue
                ];

                // for multilang
                if (!empty($languages = $this->getConfig()->get('inputLanguageList'))) {
                    foreach ($languages as $language) {
                        // prepare language
                        $lang = Util::toCamelCase(strtolower($language), '_', true);

                        // get multilang data
                        $typeValue = $attribute->get('typeValue' . $lang);
                        $value = $this->prepareValue($attribute->get('type'), (string)$item->get('value' . $lang));

                        // push
                        $result[$item->get('channelId')]['attributes'][$k]['attributeTypeValue' . $lang] = $typeValue;
                        $result[$item->get('channelId')]['attributes'][$k]['attributeValue' . $lang] = $value;
                    }
                }
            }

            // prepare attributes
            foreach ($result as $channelId => $rows) {
                $result[$channelId]['attributes'] = array_values($rows['attributes']);
            }
        }

        return array_values($result);
    }

    /**
     * Update attribute value
     *
     * @param string $productId
     * @param array  $data
     *
     * @return bool
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function updateAttributes(string $productId, array $data)
    {
        // check ACL
        if (!$this->getAcl()->check('ProductAttributeValue', 'edit')) {
            throw new Forbidden();
        }

        // find product
        if (empty($product = $this->getEntityManager()->getEntity('Product', $productId))) {
            throw new NotFound();
        }

        foreach ($data as $row) {
            if (empty($row->attributeId)) {
                throw new BadRequest('Wrong attribute id');
            }

            foreach ($row as $field => $value) {
                if (strpos($field, 'value') !== false) {
                    // prepare key
                    $key = "attr_" . $row->attributeId . str_replace("value", "", $field);

                    // set
                    $product->set($key, $value);
                } elseif ($field != 'attributeId') {
                    if (is_array($value) || is_object($value)) {
                        $value = Json::encode($value);
                    }

                    $product->setProductAttributeData($row->attributeId, $field, $value);
                }
            }

            // trigger event
            if (!empty($attributeValue = $product->getProductAttribute($row->attributeId))) {
                $this->triggered(
                    'Product', 'updateAttribute', [
                        'attributeValue' => $attributeValue,
                        'post'           => Json::decode(Json::encode($row), true),
                        'productId'      => $productId
                    ]
                );
            }
        }

        $this->getEntityManager()->saveEntity($product);

        return true;
    }

    /**
     * Get ids all active categories in tree
     *
     * @param string $productId
     *
     * @return array
     */
    public function getCategories(string $productId): array
    {
        // get categories
        $data = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->getCategoriesArray([$productId], true);

        return !empty($data) ? array_column($data, 'categoryId') : [];
    }

    /**
     * Return multiLang fields name in DB and alias
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getMultiLangName(string $fieldName): array
    {
        // all fields
        $valueMultiLang = [];
        // prepare field name
        if (preg_match_all('/[^_]+/', $fieldName, $fieldParts, PREG_PATTERN_ORDER) > 1) {
            foreach ($fieldParts[0] as $key => $value) {
                $fieldAlias[] = $key > 0 ? ucfirst($value) : $value;
            }
            $fieldAlias = implode($fieldAlias);
        } else {
            $fieldAlias = $fieldName;
        }

        $fields['db_field'] = $fieldName;
        $fields['alias'] = $fieldAlias;
        $valueMultiLang[] = $fields;
        if ($this->getConfig()->get('isMultilangActive')) {
            $languages = $this->getConfig()->get('inputLanguageList');
            foreach ($languages as $language) {
                $language = strtolower($language);
                $fields['db_field'] = $fieldName . '_' . $language;

                $alias = preg_split('/_/', $language);
                $alias = array_map('ucfirst', $alias);
                $alias = implode($alias);
                $fields['alias'] = $fieldAlias . $alias;
                $valueMultiLang[] = $fields;
                unset($fields);
            }
        }

        return $valueMultiLang;
    }

    /**
     * @inheritdoc
     */
    protected function duplicateLinks(Entity $product, Entity $duplicatingProduct)
    {
        // prepare links
        foreach ($this->getInjection('metadata')->get('entityDefs.Product.fields', []) as $field => $row) {
            if (!empty($row['type']) && $row['type'] == 'linkMultiple') {
                $links[] = $field;
            }
        }

        if (!empty($links)) {
            foreach ($links as $link) {
                // prepare method name
                $methodName = 'duplicate' . ucfirst($link);

                // call customm method
                if (method_exists($this, $methodName)) {
                    try {
                        $this->{$methodName}($product, $duplicatingProduct);
                    } catch (\Throwable $e) {
                        $GLOBALS['log']->error($e->getMessage());
                    }

                    continue 1;
                }

                $data = $duplicatingProduct->get($link);
                if (count($data) > 0) {
                    foreach ($data as $item) {
                        try {
                            $this->getEntityManager()->getRepository('Product')->relate($product, $link, $item);
                        } catch (\Throwable $e) {
                            $GLOBALS['log']->error($e->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateAttributes(Entity $product, Entity $duplicatingProduct)
    {
        // delete old
        if (!empty($attributes = $product->getProductAttributes())) {
            foreach ($attributes as $attribute) {
                $this->getEntityManager()->removeEntity($attribute);
            }
        }
        if (!empty($attributes = $product->getProductChannelAttributes())) {
            foreach ($attributes as $attribute) {
                $this->getEntityManager()->removeEntity($attribute);
            }
        }

        // get attributes
        if (empty($attributes = $duplicatingProduct->getProductAttributes())) {
            return false;
        }

        // copy attributes
        foreach ($attributes as $attribute) {
            // prepare data
            $data = $attribute->toArray();
            $data['id'] = Util::generateId();
            $data['productId'] = $product->get('id');

            // prepare entity
            $entity = $this->getEntityManager()->getEntity('ProductAttributeValue');
            $entity->set($data);

            // save
            $this->getEntityManager()->saveEntity($entity);

            // prepare attribute ids
            $ids[$data['attributeId']] = $data['id'];
        }

        // get channel attributes
        if (empty($attributes = $duplicatingProduct->getProductChannelAttributes())) {
            return false;
        }

        // copy channel attributes
        foreach ($attributes as $attribute) {
            // prepare data
            $data = $attribute->toArray();
            $data['id'] = Util::generateId();
            $data['productAttributeId'] = $ids[$attribute->get('productAttribute')->get('attributeId')];

            // prepare entity
            $entity = $this->getEntityManager()->getEntity('ChannelProductAttributeValue');
            $entity->set($data);

            // save
            $this->getEntityManager()->saveEntity($entity);
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateProductImages(Entity $product, Entity $duplicatingProduct)
    {
        // copy images
        $sql
            = "DELETE FROM product_image_product WHERE product_id = '" . $product->get('id') . "';
               INSERT INTO product_image_product (product_id, product_image_id, sort_order, scope, deleted)
               SELECT
                 '" . $product->get('id') . "',
                  product_image_id,
                  sort_order,
                  scope,
                  deleted
               FROM product_image_product
               WHERE product_id = '" . $duplicatingProduct->get('id') . "'";
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        // copy channel images
        $sql
            = "DELETE FROM product_image_channel WHERE product_id = '" . $product->get('id') . "';
               INSERT INTO product_image_channel (product_id, product_image_id, channel_id, deleted)
               SELECT
                 '" . $product->get('id') . "',
                  product_image_id,
                  channel_id,
                  deleted
               FROM product_image_channel
               WHERE product_id = '" . $duplicatingProduct->get('id') . "'";
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateAssociatedMainProducts(Entity $product, Entity $duplicatingProduct)
    {
        // get data
        $data = $duplicatingProduct->get('associatedMainProducts');

        // copy
        if (count($data) > 0) {
            foreach ($data as $row) {
                $item = $row->toArray();
                $item['id'] = Util::generateId();
                $item['mainProductId'] = $product->get('id');

                // prepare entity
                $entity = $this->getEntityManager()->getEntity('AssociatedProduct');
                $entity->set($item);

                // save
                $this->getEntityManager()->saveEntity($entity);
            }
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateAssociatedRelatedProduct(Entity $product, Entity $duplicatingProduct)
    {
        // get data
        $data = $duplicatingProduct->get('associatedRelatedProduct');

        // copy
        if (count($data) > 0) {
            foreach ($data as $row) {
                $item = $row->toArray();
                $item['id'] = Util::generateId();
                $item['relatedProductId'] = $product->get('id');

                // prepare entity
                $entity = $this->getEntityManager()->getEntity('AssociatedProduct');
                $entity->set($item);

                // save
                $this->getEntityManager()->saveEntity($entity);
            }
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateProductTypeBundles(Entity $product, Entity $duplicatingProduct)
    {
        if ($duplicatingProduct->get('type') === 'bundleProduct') {
            // create service
            $service = $this->getServiceFactory()->create('ProductTypeBundle');

            // create new bundles
            foreach ($service->getBundleProducts($duplicatingProduct->get('id')) as $bundle) {
                $service->create($product->get('id'), $bundle['productId'], $bundle['amount']);
            }
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateProductTypePackages(Entity $product, Entity $duplicatingProduct)
    {
        if ($duplicatingProduct->get('type') === 'packageProduct') {
            // create service
            $service = $this->getServiceFactory()->create('ProductTypePackage');

            // find ProductPackage
            $productPackage = $service->getPackageProduct($duplicatingProduct->get('id'));

            // create new productPackage
            if (!is_null($productPackage['id'])) {
                $service->update($product->get('id'), $productPackage);
            }
        }
    }

    /**
     * @param string $productId
     * @param string $channelId
     * @param array  $attributeData
     *
     * @return string
     * @throws BadRequest
     * @throws Forbidden
     */
    protected function createChannelProductAttributeValue(
        string $productId,
        string $channelId,
        array $attributeData
    ): string {
        /** @var ChannelProductAttributeValue $service */
        $service = $this->getServiceFactory()->create('ChannelProductAttributeValue');

        // prepare data
        $data = [
            'productId'   => $productId,
            'channelId'   => $channelId,
            'attributeId' => $attributeData['attributeId']
        ];

        return $service->createValue($data, false);
    }

    /**
     * Return formatted attribute data for get actions
     *
     * @param $data
     *
     * @return array
     */
    protected function formatAttributeData($data)
    {
        // MultiLang fields name
        $multiLangValue = $this->getMultiLangName('value');
        $multiLangTypeValue = $this->getMultiLangName('type_value');

        foreach ($data as $key => $attribute) {
            //Prepare attribute
            $data[$key] = $this->prepareAttributeValue($attribute, $multiLangValue, $multiLangTypeValue);
        }

        return $data;
    }

    /**
     * Prepare attribute data
     *
     * @param array  $attribute
     * @param array  $multiLangValue
     * @param array  $multiLangTypeValue
     * @param string $prefix
     *
     * @return array
     */
    protected function prepareAttributeValue($attribute, $multiLangValue, $multiLangTypeValue, $prefix = '')
    {
        $type = 'type';
        $isRequired = 'isRequired';

        if (!empty($prefix)) {
            $type = $prefix . ucfirst($type);
            $isRequired = $prefix . ucfirst($isRequired);
        }
        $attribute[$isRequired] = (bool)$attribute[$isRequired];
        $value = $multiLangValue[0]['alias'];
        $typeValue = $multiLangTypeValue[0]['alias'];
        switch ($attribute[$type]) {
            case 'int':
                $attribute[$value] = !is_null($attribute[$value]) ? (int)$attribute[$value] : null;
                break;
            case 'bool':
                $attribute[$value] = !is_null($attribute[$value]) ? (bool)$attribute[$value] : null;
                break;
            case 'float':
                $attribute[$value] = !is_null($attribute[$value]) ? (float)$attribute[$value] : null;
                break;
            case 'multiEnum':
            case 'array':
                $attributeValue = [];
                if (!empty($attribute[$value])) {
                    $attributeValue = Json::decode($attribute[$value], true);
                }
                $attribute[$value] = $attributeValue;
                $attribute[$typeValue] = !is_null($attribute[$typeValue]) ? (array)$attribute[$typeValue] : null;
                break;
            case 'enum':
                $attribute[$typeValue] = !is_null($attribute[$typeValue]) ? (array)$attribute[$typeValue] : [];
                break;
            // Serialize MultiLang fields
            case 'multiEnumMultiLang':
            case 'arrayMultiLang':
                foreach ($multiLangValue as $key => $field) {
                    if (!is_null($attribute[$field['alias']])) {
                        $attribute[$field['alias']] = is_string($attribute[$field['alias']])
                            ?
                            json_decode($attribute[$field['alias']])
                            :
                            $attribute[$field['alias']];
                    } else {
                        $attribute[$field['alias']] = [];
                    }

                    $feild = $multiLangTypeValue[$key]['alias'];
                    if (!is_null($attribute[$feild])) {
                        $attribute[$feild] = is_string($attribute[$feild])
                            ?
                            json_decode($attribute[$feild])
                            :
                            $attribute[$feild];
                    } else {
                        $attribute[$feild] = null;
                    }
                }
                break;
            case 'enumMultiLang':
                foreach ($multiLangTypeValue as $field) {
                    if (!is_null($attribute[$field['alias']])) {
                        $attribute[$field['alias']] = is_string($attribute[$field['alias']])
                            ?
                            json_decode($attribute[$field['alias']])
                            :
                            $attribute[$field['alias']];
                    } else {
                        $attribute[$field['alias']] = [];
                    }
                }
                break;
        }

        if (isset($attribute['isCustom'])) {
            $attribute['isCustom'] = (bool)$attribute['isCustom'];
        }
        if (isset($attribute['attributeGroupOrder'])) {
            $attribute['attributeGroupOrder'] = (int)$attribute['attributeGroupOrder'];
        }
        // prepare isMultiChannel
        if (isset($attribute['attributeIsMultiChannel'])) {
            $attribute['attributeIsMultiChannel'] = (bool)$attribute['attributeIsMultiChannel'];
        }

        // prepare attribute group
        if (empty($attribute['attributeGroupId'])) {
            $attribute['attributeGroupId'] = 'no_group';
            $attribute['attributeGroupName'] = 'No group';
            $attribute['attributeGroupOrder'] = 999;
        }

        return $attribute;
    }

    /**
     * Save data to db
     *
     * @param Entity $entity
     * @param array  $data
     *
     * @return Entity
     * @throws Error
     */
    protected function save(Entity $entity, $data)
    {
        $entity->set($data);
        if ($this->storeEntity($entity)) {
            $this->prepareEntityForOutput($entity);

            return $entity;
        }

        throw new Error();
    }

    /**
     * Get active parent category id for category from DB
     *
     * @param $categoryId
     *
     * @return array
     */
    protected function getDBParentCategory(string $categoryId): array
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql
            = "SELECT
                  c.category_parent_id AS categoryId
                FROM category AS c
                  JOIN 
                  category AS c2 on c2.id = c.category_parent_id AND c2.deleted = 0 AND c2.is_active = 1
                WHERE 
                c.deleted = 0 AND c.is_active = 1 AND c.id =" . $pdo->quote($categoryId) . ";";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'categoryId') : [];
    }

    /**
     * Get DB count of item in products data
     *
     * @param string $productId
     *
     * @return int
     */
    protected function getDbCountItemInProducts(string $productId): int
    {
        // prepare data
        $pdo = $this->getEntityManager()->getPDO();
        $where = $this->getAclWhereSql('Product', 'p');

        // prepare SQL
        $sql
            = "SELECT
                  COUNT(p.id) as count
                FROM
                  product AS p
                WHERE
                 p.deleted = 0
                AND p.id IN (SELECT bundle_product_id FROM product_type_bundle
                                                    WHERE product_id = " . $pdo->quote($productId) . " $where)";
        $sth = $pdo->prepare($sql);
        $sth->execute();

        // get DB data
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);

        return (isset($data[0]['count'])) ? (int)$data[0]['count'] : 0;
    }

    /**
     * Get DB count of item in products data
     *
     * @param string  $productId
     * @param Request $request
     *
     * @return array
     */
    protected function getDbItemInProducts(string $productId, Request $request): array
    {
        // prepare data
        $limit = (int)$request->get('maxSize');
        $offset = (int)$request->get('offset');
        $sortOrder = ($request->get('asc') == 'true') ? 'ASC' : 'DESC';
        $sortColumn = (in_array($request->get('sortBy'), ['name', 'type'])) ? $request->get('sortBy') : 'name';
        $where = $this->getAclWhereSql('Product', 'p');

        // prepare PDO
        $pdo = $this->getEntityManager()->getPDO();

        // prepare SQL
        $sql
            = "SELECT
                  p.id   AS id,
                  p.name AS name,
                  p.type AS type
                FROM
                  product AS p
                WHERE
                 p.deleted = 0
                AND p.id IN (SELECT bundle_product_id FROM product_type_bundle
                                                    WHERE product_id = " . $pdo->quote($productId) . " $where)
                ORDER BY p." . $sortColumn . " " . $sortOrder . "
                LIMIT " . $limit . " OFFSET " . $offset;
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * After delete action
     *
     * @param Entity $entity
     *
     * @return void
     */
    protected function afterDelete(Entity $entity): void
    {
        $this->deleteProductTypes([$entity->get('id')]);
    }

    /**
     * After mass delete action
     *
     * @param array $idList
     *
     * @return void
     */
    protected function afterMassRemove(array $idList): void
    {
        $this->deleteProductTypes($idList);
    }

    /**
     * Delete product types
     *
     * @param array $idList
     *
     * @return void
     */
    protected function deleteProductTypes(array $idList): void
    {
        // delete type bundle
        $this->getServiceFactory()->create('ProductTypeBundle')->deleteByProductId($idList);

        // delete type package
        $this->getServiceFactory()->create('ProductTypePackage')->deleteByProductId($idList);
    }

    /**
     * Find linked AssociationMainProduct
     *
     * @param string $id
     * @param array  $params
     *
     * @return array
     * @throws Forbidden
     */
    protected function findLinkedEntitiesAssociatedMainProducts(string $id, array $params): array
    {
        // check acl
        if (!$this->getAcl()->check('Association', 'read')) {
            throw new Forbidden();
        }

        return [
            'list'  => $this->getDBAssociationMainProducts($id, '', $params),
            'total' => $this->getDBTotalAssociationMainProducts($id, '')
        ];

    }

    /**
     * Get AssociationMainProducts from DB
     *
     * @param string $productId
     * @param string $wherePart
     * @param array  $params
     *
     * @return array
     */
    protected function getDBAssociationMainProducts(string $productId, string $wherePart, array $params): array
    {
        // prepare limit
        $limit = '';
        if (!empty($params['maxSize'])) {
            $limit = ' LIMIT ' . (int)$params['maxSize'];
            $limit .= ' OFFSET ' . (empty($params['offset']) ? 0 : (int)$params['offset']);
        }

        //prepare sort
        $sortOrder = ($params['asc'] === true) ? 'ASC' : 'DESC';
        $orderColumn = ['relatedProduct', 'association'];
        $sortColumn = in_array($params['sortBy'], $orderColumn) ? $params['sortBy'] . '.name' : 'relatedProduct.name';

        // prepare query
        $sql
            = "SELECT
                  ap.id,
                  ap.association_id   AS associationId,
                  association.name    AS associationName,
                  p_main.id           AS mainProductId,
                  p_main.name         AS mainProductName,
                  relatedProduct.id   AS relatedProductId,
                  relatedProduct.name AS relatedProductName,
                  pi.image_id         AS relatedProductImageId,
                  pi.image_link       AS relatedProductImageLink
                FROM associated_product AS ap
                  JOIN product AS relatedProduct 
                    ON relatedProduct.id = ap.related_product_id AND relatedProduct.deleted = 0
                  LEFT JOIN product_image_product as pip
                    ON pip.product_id = relatedProduct.id AND pip.deleted = 0 AND pip.id = (
                      SELECT id
                      FROM product_image_product
                      WHERE product_id = pip.product_id
                      ORDER BY sort_order, id
                      LIMIT 1
                    )
                  LEFT JOIN product_image as pi
                    ON pi.id = pip.product_image_id AND pi.deleted = 0
                  JOIN product AS p_main 
                    ON p_main.id = ap.related_product_id AND p_main.deleted = 0
                  JOIN association 
                    ON association.id = ap.association_id AND association.deleted = 0
                WHERE ap.deleted = 0 
                  AND ap.main_product_id = :id "
            . $wherePart
            . "ORDER BY " . $sortColumn . " " . $sortOrder
            . $limit;

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute([':id' => $productId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get total AssociationMainProducts
     *
     * @param string $productId
     * @param string $wherePart
     *
     * @return int
     */
    protected function getDBTotalAssociationMainProducts(string $productId, string $wherePart): int
    {
        // prepare query
        $sql
            = "SELECT
                  COUNT(ap.id)                  
                FROM associated_product AS ap
                  JOIN product AS p_rel 
                    ON p_rel.id = ap.related_product_id AND p_rel.deleted = 0
                  JOIN product AS p_main 
                    ON p_main.id = ap.related_product_id AND p_main.deleted = 0
                  JOIN association 
                    ON association.id = ap.association_id AND association.deleted = 0
                WHERE ap.deleted = 0 AND  ap.main_product_id = :id " . $wherePart;

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute([':id' => $productId]);

        return (int)$sth->fetchColumn();
    }

    /**
     * Get ProductAttributeValue service
     *
     * @return ProductAttributeValue
     */
    protected function getProductAttributeValueService(): ProductAttributeValue
    {
        return $this->getServiceFactory()->create('ProductAttributeValue');
    }

    /**
     * Prepare value by type
     *
     * @param string $type
     * @param string $value
     *
     * @return mixed
     */
    protected function prepareValue(string $type, string $value)
    {
        // prepare result
        $result = null;

        if (!is_null($value)) {
            switch ($type) {
                case 'int':
                    $result = (int)$value;
                    break;
                case 'bool':
                    $result = (bool)$value;
                    break;
                case 'float':
                    $result = (float)$value;
                    break;
                case 'multiEnum':
                    if (!empty($value)) {
                        $result = Json::decode($value, true);
                    }
                    break;
                case 'array':
                    if (!empty($value)) {
                        $result = Json::decode($value, true);
                    }
                    break;
                case 'multiEnumMultiLang':
                    if (!empty($value)) {
                        $result = Json::decode($value, true);
                    }
                    break;
                case 'arrayMultiLang':
                    if (!empty($value)) {
                        $result = Json::decode($value, true);
                    }
                    break;
                default:
                    $result = $value;
                    break;
            }
        }

        return $result;
    }

    /**
     * Before create entity method
     *
     * @param Entity $entity
     * @param        $data
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        if (isset($data->_duplicatingEntityId)) {
            $entity->isDuplicate = true;
        }
    }
}
