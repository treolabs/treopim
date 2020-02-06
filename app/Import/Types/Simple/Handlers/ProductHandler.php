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

namespace Pim\Import\Types\Simple\Handlers;

use Dam\Entities\Asset;
use Dam\Entities\AssetRelation;
use DamCommon\Utils\AssetRelation as UtilAssetRelation;
use Espo\Core\Exceptions\Conflict;
use Espo\ORM\Entity;
use Espo\Services\Record;
use Import\Types\Simple\Handlers\AbstractHandler;
use StdClass;
use Treo\Core\Exceptions\NoChange;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\Util;
use Treo\Entities\Attachment;

/**
 * Class Product
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $assetRelations = [];

    /** @var UtilAssetRelation */
    protected $utilAssetRelation;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var bool
     */
    protected $saved = false;

    /**
     * @param array $fileData
     * @param array $data
     *
     * @return bool
     */
    public function run(array $fileData, array $data): bool
    {
        // prepare entity type
        $entityType = (string)$data['data']['entity'];

        // prepare import result id
        $importResultId = (string)$data['data']['importResultId'];

        // prepare field value delimiter
        $delimiter = $data['data']['delimiter'];

        // create service
        $service = $this->getServiceFactory()->create($entityType);

        // prepare id field
        $idField = isset($data['data']['idField']) ? $data['data']['idField'] : "";

        // find ID row
        $idRow = $this->getIdRow($data['data']['configuration'], $idField);

        // find exists if it needs
        $exists = [];
        if (in_array($data['action'], ['update', 'create_update']) && !empty($idRow)) {
            $exists = $this->getExists($entityType, $idRow['name'], array_column($fileData, $idRow['column']));
        }

        // prepare file row
        $fileRow = (int)$data['offset'];

        foreach ($fileData as $row) {
            $fileRow++;

            // prepare id
            if ($data['action'] == 'create') {
                $id = null;
            } elseif ($data['action'] == 'update') {
                if (isset($exists[$row[$idRow['column']]])) {
                    $id = $exists[$row[$idRow['column']]];
                } else {
                    // skip row if such item does not exist
                    continue 1;
                }
            } elseif ($data['action'] == 'create_update') {
                $id = (isset($exists[$row[$idRow['column']]])) ? $exists[$row[$idRow['column']]] : null;
            }

            // prepare entity
            $entity = !empty($id) ? $this->getEntityManager()->getEntity($entityType, $id) : null;

            // prepare row
            $input = new stdClass();
            $restore = new stdClass();

            try {
                // begin transaction
                $this->getEntityManager()->getPDO()->beginTransaction();

                $additionalFields = [];

                foreach ($data['data']['configuration'] as $item) {
                    if ($item['name'] == 'id') {
                        continue;
                    }

                    if (isset($item['attributeId']) || isset($item['assetRelation']) || $item['name'] == 'productCategories') {
                        $additionalFields[] = [
                            'item' => $item,
                            'row' => $row
                        ];

                        continue;
                    } else {
                        $this->convertItem($input, $entityType, $item, $row, $delimiter);
                    }

                    if (!empty($entity)) {
                        $this->prepareValue($restore, $entity, $item);
                    }
                }

                if (empty($id)) {
                    $entity = $service->createEntity($input);

                    $this->saveRestoreRow('created', $entityType, $entity->get('id'));

                    $this->saved = true;
                } else {
                    $entity = $this->updateEntity($service, (string)$id, $input);

                    if ($entity->isSaved()) {
                        $this->saveRestoreRow('updated', $entityType, [$id => $restore]);
                        $this->saved = true;
                    }
                }

                // prepare product images if needed
                if (!empty($entity) && !empty(array_column($data['data']['configuration'], 'assetRelation'))) {
                    $this->utilAssetRelation = new UtilAssetRelation();
                    $this->utilAssetRelation->setContainer($this->container);

                    $this->assetRelations = $this
                        ->utilAssetRelation
                        ->getAssetsRelationsByProduct(['asset.file_id', 'asset.type'], (string)$entity->get('id'))
                        ->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_ASSOC);
                }

                // prepare product attributes
                $this->attributes = $entity->get('productAttributeValues');

                foreach ($additionalFields as $value) {
                    if ($value['item']['name'] == 'productCategories') {
                        // import categories
                        $this->importCategories($entity, $value, $delimiter);
                    } elseif (isset($value['item']['attributeId'])) {
                        // import attributes
                        $this->importAttribute($entity, $value, $delimiter);
                    } elseif (isset($value['item']['assetRelation'])) {
                        // import product images
                        $this->importAssets($entity, $value);
                    }
                }

                if (!is_null($entity) && $this->saved) {
                    // prepare action
                    $action = empty($id) ? 'create' : 'update';

                    // push log
                    $this->log($entityType, $importResultId, $action, (string)$fileRow, (string)$entity->get('id'));
                }

                $this->saved = false;

                $this->getEntityManager()->getPDO()->commit();
            } catch (\Throwable $e) {
                // roll back transaction
                $this->getEntityManager()->getPDO()->rollBack();

                // push log
                $this->log($entityType, $importResultId, 'error', (string)$fileRow, $e->getMessage());
            }
        }

        return true;
    }

    /**
     * @param Record $service
     * @param string $id
     * @param stdClass $data
     */
    protected function updateEntity(Record $service, string $id, stdClass $data): ?Entity
    {
        try {
            $result = $service->updateEntity($id, $data);
        } catch (NoChange $e) {
            $result = $service->readEntity($id);
        }

        return $result;
    }

    /**
     * @param Entity $product
     * @param array $data
     * @param string $delimiter
     */
    protected function importAttribute(Entity $product, array $data, string $delimiter)
    {
        $attribute = null;
        $entityType = 'ProductAttributeValue';
        $service = $this->getServiceFactory()->create($entityType);

        $inputRow = new stdClass();
        $restoreRow = new stdClass();

        $conf = $data['item'];
        $conf['name'] = 'value';
        // check for multiLang
        if (isset($conf['locale']) && !is_null($conf['locale'])) {
            if ($this->getConfig()->get('isMultilangActive')) {
                $conf['name'] .= Util::toCamelCase(strtolower($conf['locale']), '_', true);
            }
        }
        $row = $data['row'];

        foreach ($this->attributes as $item) {
            if ($item->get('attributeId') == $conf['attributeId'] && $item->get('scope') == $conf['scope']) {
                if ($conf['scope'] == 'Global') {
                    $inputRow->id = $item->get('id');
                    $this->prepareValue($restoreRow, $item, $conf);
                } elseif ($conf['scope'] == 'Channel') {
                    $channels = array_column($item->get('channels')->toArray(), 'id');

                    if (empty($diff = array_diff($conf['channelsIds'], $channels))
                        && empty($diff = array_diff($channels, $conf['channelsIds']))) {
                        $inputRow->id = $item->get('id');
                        $this->prepareValue($restoreRow, $item, $conf);
                    }
                }
            }
        }

        // prepare attribute
        if (!isset($this->attributes[$conf['attributeId']])) {
            $attribute = $this->getEntityManager()->getEntity('Attribute', $conf['attributeId']);
            $this->attributes[$conf['attributeId']] = $attribute;
        } else {
            $attribute = $this->attributes[$conf['attributeId']];
        }
        $conf['attribute'] = $attribute;

        // convert attribute value
        $this->convertItem($inputRow, $entityType, $conf, $row, $delimiter);

        if (!isset($inputRow->id)) {
            $inputRow->productId = $product->get('id');
            $inputRow->attributeId = $conf['attributeId'];
            $inputRow->scope = $conf['scope'];

            if ($conf['scope'] == 'Channel') {
                $inputRow->channelsIds = $conf['channelsIds'];
            }

            $entity = $service->createEntity($inputRow);
            $this->attributes[] = $entity;

            $this->saveRestoreRow('created', $entityType, $entity->get('id'));

            $this->saved = true;
        } else {
            $id = $inputRow->id;
            unset($inputRow->id);

            $entity = $this->updateEntity($service, $id, $inputRow);

            if ($entity->isSaved()) {
                $this->saveRestoreRow('updated', $entityType, [$id => $restoreRow]);
                $this->saved = true;
            }
        }
    }

    /**
     * @param Entity $product
     * @param array $data
     * @param string $delimiter
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function importCategories(Entity $product, array $data, string $delimiter)
    {
        $entityType = 'ProductCategory';
        $service = $this->getServiceFactory()->create($entityType);

        $conf = $data['item'];
        $row = $data['row'];

        $channelsIds = isset($conf['channelsIds']) ? $conf['channelsIds'] : null;
        $field = $conf['field'];

        if (empty($categories = $row[$conf['column']])) {
            $categories = $conf['default'];
            $field = 'id';
            $delimiter = ',';
        }

        $exists = $this->getExists('Category', $field, explode($delimiter, $categories));

        foreach ($exists as $exist) {
            $inputRow = new stdClass();
            $restoreRow = new stdClass();

            if (empty($category = $this->getProductCategory($product, $exist, $conf['scope']))) {
                $inputRow->categoryId = $exist;
                $inputRow->productId = $product->get('id');
                $inputRow->scope = $conf['scope'];

                if ($conf['scope'] == 'Channel') {
                    $inputRow->channelsIds = $channelsIds;
                }

                $entity = $service->createEntity($inputRow);

                $this->saveRestoreRow('created', $entityType, $entity->get('id'));

                $this->saved = true;
            } elseif ($conf['scope'] == 'Channel') {
                $id = (string)$category->get('id');
                $inputRow->channelsIds = $channelsIds;
                $restoreRow->channelsIds = array_column($category->get('channels')->toArray(), 'id');

                $entity = $this->updateEntity($service, $id, $inputRow);

                if ($entity->isSaved()) {
                    $this->saveRestoreRow('updated', $entityType, [$id => $restoreRow]);
                    $this->saved = true;
                }
            }
        }
    }


    /**
     * @param Entity $product
     * @param string $categoryId
     * @param string $scope
     *
     * @return Entity|null
     */
    protected function getProductCategory(Entity $product, string $categoryId, string $scope): ?Entity
    {
        $result = null;

        foreach ($product->get('productCategories') as $item) {
            if ($item->get('categoryId') == $categoryId && $item->get('scope') == $scope) {
                $result = $item;
                break;
            }
        }

        return $result;
    }

    /**
     * @param Entity $product
     * @param array $data
     *
     * @throws \Espo\Core\Exceptions\Error
     * @throws Conflict
     */
    protected function importAssets(Entity $product, array $data): void
    {
        // prepare image entity type
        $entityType = 'Asset';

        // prepare data
        $conf = $data['item'];
        $row = $data['row'];
        $isNewAsset = false;
        // prepare input row
        $input = new stdClass();
        // prepare where
        if (!empty($row[$conf['column']])) {
            $value = $row[$conf['column']];
            if (!empty($asset = $this->utilAssetRelation->getAsset($value, ['type' => 'Gallery Image']))) {
                $input->asset = $asset;
                $isNewAsset = true;
            } else {
                $input->asset = $this->createAsset($value);
            }
        } elseif (!empty($conf['default'])) {
            return;
            $field = 'imageId';
            $value = $conf['default'];
        } else {
            return;
        }

        // prepare scope
        $input->scope = $conf['scope'];
        if ($conf['scope'] === 'Channel') {
            $input->channelsIds = $conf['channelsIds'];
        }

        // check exist asset
        $exist =
            !empty($this->assetRelations[$input->asset->get('fileId')])
                ? $this->assetRelations[$input->asset->get('fileId')]
                : false;

        if (empty($exist)) {
            // convert image
            $this->convertItem($input, $entityType, $conf, $row, '');

            $assetRelation = $this->createAssetRelation($input->asset, $product, $input);

            // save restore row
            $this->saveRestoreRow('created', $assetRelation->getEntityName(), $assetRelation->get('id'));
            if ($isNewAsset) {
                $this->saveRestoreRow('created', $input->asset->getEntityName(), $input->asset->get('id'));
            }

            $this->saved = true;
        } else {
            // prepare service
            $service = $this->getServiceFactory()->create($entityType);
            // prepare restore row
            $restore = new stdClass();
            $restore->scope = $exist->get('scope');
            $restore->channelsIds = array_column($exist->get('channels')->toArray(), 'id');

            // update entity
            $entity = $this->updateEntity($service, $exist->get('id'), $input);

            if ($entity->isSaved()) {
                // save restore row
                $this->saveRestoreRow('updated', $entityType, [$exist->get('id') => $restore]);
                $this->saved = true;
            }
        }
    }

    /**
     * @param string $link
     * @return Asset
     */
    protected function createAsset(string $link): Asset
    {
        if (empty($contents = @file_get_contents($link))) {
            throw new Error('Wrong asset link. Link: ' . $link);
        }

        // create attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', array_pop(explode('/', $link)));
        $attachment->set('role', 'Attachment');
        $attachment->set('field', 'file');
        $attachment->set('relatedType', 'Asset');

        $sm = $this->container->get('fileStorageManager');
        $sm->putContents($attachment, $contents);
        // get file storage manager
        $type = mime_content_type($sm->getLocalFilePath($attachment));
        // set mime type
        $attachment->set('type', $type);
        $this->getEntityManager()->saveEntity($attachment);

        $assetInput = new StdClass();
        $assetInput->type = 'Gallery Image';
        $assetInput->privat = true;
        $assetInput->fileId = $attachment->get('id');
        $assetInput->fileName = $attachment->get('name');
        $assetInput->name = explode('.', $attachment->get('name'))[0];
        $assetInput->nameOfFile = $assetInput->name;
        $assetInput->code = md5((string)microtime());
        $assetInput->collectionId = '5e022260913785fe8';

        foreach ($this->getInputLanguageList() as $lang) {
            $nameField = 'name' . $lang;
            $assetInput->{$nameField} = $assetInput->name;
        }

        return $this->getServiceFactory()->create('Asset')->createEntity($assetInput);
    }

    /**
     * @param Asset $asset
     * @param Entity $related
     * @param array $fields
     * @return AssetRelation
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function createAssetRelation(Asset $asset, Entity $related, StdClass $fields): AssetRelation
    {
        $entity = $this->getEntityManager()->getEntity('AssetRelation');

        foreach ($fields as $field => $value) {
            if ($entity->hasField($field)) {
                $entity->set($field, $value);
            }
        }
        $entity->set([
            'name' => $asset->get('name') . ' / ' . $asset->get('size'),
            'entityName' => $related->getEntityName(),
            'entityId' => $related->id,
            'assetId' => $asset->id,
            'assetType' => $asset->type
        ]);

        $this->getEntityManager()->saveEntity($entity);

        return $entity;
    }

    /**
     * @inheritDoc
     */
    protected function getType(string $entityType, array $item): ?string
    {
        $result = null;

        if (isset($item['attributeId']) && isset($item['type'])) {
            $result = $item['type'];
        } else {
            $result = parent::getType($entityType, $item);
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getInputLanguageList(): array
    {
        $result = [];

        /** @var Config $config */
        $config = $this->container->get('config');

        if ($config->get('isMultilangActive', false)) {
            foreach ($config->get('inputLanguageList', []) as $locale) {
                $result[$locale] = ucfirst(Util::toCamelCase(strtolower($locale)));
            }
        }

        return $result;
    }
}