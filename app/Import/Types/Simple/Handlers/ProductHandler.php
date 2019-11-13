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

use Espo\ORM\Entity;
use Espo\Services\Record;
use Import\Types\Simple\Handlers\AbstractHandler;
use Treo\Core\Exceptions\NoChange;
use Treo\Core\Utils\Util;

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
    protected $images = [];

    /**
     * @var array
     */
    protected $attributes = [];

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
            $input = new \stdClass();
            $restore = new \stdClass();

            try {
                // begin transaction
                $this->getEntityManager()->getPDO()->beginTransaction();

                $additionalFields = [];

                foreach ($data['data']['configuration'] as $item) {
                    if ($item['name'] == 'id') {
                        continue;
                    }

                    if (isset($item['attributeId']) || isset($item['pimImage']) || $item['name'] == 'productCategories') {
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
                } else {
                    $entity = $this->updateEntity($service, (string)$id, $input);

                    $this->saveRestoreRow('updated', $entityType, [$id => $restore]);
                }

                // prepare product images if needed
                if (!empty($entity) && !empty(array_column($data['data']['configuration'], 'pimImage'))) {
                    $this->images = $entity->get('pimImages');
                    $this->attributes = $entity->get('productAttributeValues');
                }

                foreach ($additionalFields as $value) {
                    if ($value['item']['name'] == 'productCategories') {
                        // import categories
                        $this->importCategories($entity, $value, $delimiter);
                    } elseif (isset($value['item']['attributeId'])) {
                        // import attributes
                        $this->importAttribute($entity, $value, $delimiter);
                    } elseif (isset($value['item']['pimImage'])) {
                        // import product images
                        $this->importImages($entity, $value);
                    }
                }

                if (!is_null($entity)) {
                    // prepare action
                    $action = empty($id) ? 'create' : 'update';

                    // push log
                    $this->log($entityType, $importResultId, $action, (string)$fileRow, (string)$entity->get('id'));
                }

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
     * @param \stdClass $data
     */
    protected function updateEntity(Record $service, string $id, \stdClass $data): ?Entity
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
        $entityType = 'ProductAttributeValue';
        $service = $this->getServiceFactory()->create($entityType);

        $inputRow = new \stdClass();
        $restoreRow = new \stdClass();

        $conf = $data['item'];
        $row = $data['row'];

        foreach ($this->attributes as $item) {
            if ($item->get('attributeId') == $conf['attributeId'] && $item->get('scope') == $conf['scope']) {
                if ($conf['scope'] == 'Global') {
                    $inputRow->id = $item->get('id');
                    $restoreRow->value = $item->get('value');
                } elseif ($conf['scope'] == 'Channel') {
                    $channels = array_column($item->get('channels')->toArray(), 'id');

                    if (empty($diff = array_diff($conf['channelsIds'], $channels))
                        && empty($diff = array_diff($channels, $conf['channelsIds']))) {
                        $inputRow->id = $item->get('id');
                        $restoreRow->value = $item->get('value');
                    }
                }
            }
        }

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
        } else {
            $id = $inputRow->id;
            unset($inputRow->id);

            $entity = $this->updateEntity($service, $id, $inputRow);

            $this->saveRestoreRow('updated', $entityType, [$id => $restoreRow]);
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
            $inputRow = new \stdClass();
            $restoreRow = new \stdClass();

            if (empty($category = $this->getProductCategory($product, $exist, $conf['scope']))) {
                $inputRow->categoryId = $exist;
                $inputRow->productId = $product->get('id');
                $inputRow->scope = $conf['scope'];

                if ($conf['scope'] == 'Channel') {
                    $inputRow->channelsIds = $channelsIds;
                }

                $entity = $service->createEntity($inputRow);

                $this->saveRestoreRow('created', $entityType, $entity->get('id'));
            } elseif ($conf['scope'] == 'Channel') {
                $id = (string)$category->get('id');
                $inputRow->channelsIds = $channelsIds;
                $restoreRow->channelsIds = array_column($category->get('channels')->toArray(), 'id');

                $this->updateEntity($service, $id, $inputRow);

                $this->saveRestoreRow('updated', $entityType, [$id => $restoreRow]);
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
     */
    protected function importImages(Entity $product, array $data)
    {
        // prepare image entity type
        $entityType = 'PimImage';

        // prepare data
        $conf = $data['item'];
        $row = $data['row'];

        // prepare input row
        $input = new \stdClass();
        $input->scope = $conf['scope'];
        if ($conf['scope'] == 'Channel') {
            $input->channelsIds = $conf['channelsIds'];
        }

        // prepare where
        if (isset($row[$conf['column']]) && !empty($row[$conf['column']])) {
            $field = 'link';
            $value = $row[$conf['column']];
            $input->link = $row[$conf['column']];
        } else {
            $field = 'imageId';
            $value = $conf['default'];
        }

        // check exist product image
        $exist = null;
        if (!empty($this->images)) {
            foreach ($this->images as $image) {
                if ($image->get($field) == $value) {
                    $exist = $image;
                    break;
                }
            }
        }

        // prepare service
        $service = $this->getServiceFactory()->create($entityType);

        if (empty($exist)) {
            // convert image
            $this->convertItem($input, $entityType, $conf, $row, '');

            // get attachment
            $attachment = $this->getEntityManager()->getEntity('Attachment', $input->{$conf['name'] . 'Id'});

            // prepare input row
            $input->productId = $product->get('id');
            $input->name = $attachment->get('name');

            // create entity
            $entity = $service->createEntity($input);

            // save restore row
            $this->saveRestoreRow('created', $entityType, $entity->get('id'));
        } else {
            // prepare restore row
            $restore = new \stdClass();
            $restore->scope = $exist->get('scope');
            $restore->channelsIds = array_column($exist->get('channels')->toArray(), 'id');

            // update entity
            $entity = $this->updateEntity($service, $exist->get('id'), $input);

            // save restore row
            $this->saveRestoreRow('updated', $entityType, [$exist->get('id') => $restore]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function convertItem(\stdClass $inputRow, string $entityType, array $item, array $row, string $delimiter)
    {
        $field = $item['name'];

        // prepare attribute data
        if (isset($item['attributeId']) && !empty($item['attributeId'])) {
            $field = 'value';
            $item['name'] = $field;
        }

        // check for multiLang fields
        if (isset($item['locale']) && !empty($item['locale'])) {
            $field = Util::toCamelCase($field . '_' . $item['locale']);
        }

        $item['name'] = $field;

        parent::convertItem($inputRow, $entityType, $item, $row, $delimiter);
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
}