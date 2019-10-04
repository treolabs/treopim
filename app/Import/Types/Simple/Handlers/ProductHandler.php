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

/**
 * Class Product
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductHandler extends AbstractHandler
{
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

                $attributes = $categories = [];

                foreach ($data['data']['configuration'] as $item) {
                    if (isset($item['attributeId'])) {
                        $attributes[] = [
                            'item' => $item,
                            'row' => $row
                        ];

                        continue;
                    } elseif ($item['name'] == 'productCategories') {
                        $categories[] = [
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

                    $this->restore[] = ['action' => 'created', 'entity' => $entityType, 'data' => $entity->get('id')];
                } else {
                    $entity = $this->updateEntity($service, (string)$id, $input);

                    $this->restore[] = ['action' => 'updated', 'entity' => $entityType, 'data' => [$id => $restore]];
                }

                foreach ($categories as $value) {
                    $this->importCategories($entity, $value, $delimiter);
                }

                foreach ($attributes as $value) {
                    $this->importAttribute($entity, $value, $delimiter);
                }

                $this->getEntityManager()->getPDO()->commit();
            } catch (\Throwable $e) {
                // roll back transaction
                $this->getEntityManager()->getPDO()->rollBack();

                // push log
                $this->log($entityType, $importResultId, 'error', (string)$fileRow, $e->getMessage());
            }
            if (!is_null($entity)) {
                // prepare action
                $action = empty($id) ? 'create' : 'update';

                // push log
                $this->log($entityType, $importResultId, $action, (string)$fileRow, (string)$entity->get('id'));
            }
        }

        // save data for restore
        $this->saveRestoreData($importResultId);

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
        $service = $this->getServiceFactory()->create('ProductAttributeValue');

        $inputRow = new \stdClass();
        $restoreRow = new \stdClass();

        $conf = $data['item'];
        $row = $data['row'];

        foreach ($product->get('productAttributeValues') as $item) {
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
                        $restoreRow->channelsIds = array_column($item->get('channels')->toArray(), 'id');
                    }
                }
            }
        }

        // convert attribute value
        $this->convertItem($inputRow, 'ProductAttributeValue', $conf, $row, $delimiter);
        $inputRow->value = $inputRow->{$conf['name']};
        unset($inputRow->{$conf['name']});

        if (!isset($inputRow->id)) {
            $inputRow->productId = $product->get('id');
            $inputRow->attributeId = $conf['attributeId'];
            $inputRow->scope = $conf['scope'];

            if ($conf['scope'] == 'Channel') {
                $inputRow->channelsIds = $conf['channelsIds'];
            }

            $entity = $service->createEntity($inputRow);

            $this->restore[] = ['action' => 'created', 'entity' => 'ProductAttributeValue', 'data' => $entity->get('id')];
        } else {
            $id = $inputRow->id;
            unset($inputRow->id);

            $entity = $this->updateEntity($service, $id, $inputRow);

            $this->restore[] = ['action' => 'updated', 'entity' => 'ProductAttributeValue', 'data' => [$id => $restoreRow]];
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
        $service = $this->getServiceFactory()->create('ProductCategory');

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

                $this->restore[] = ['action' => 'created', 'entity' => 'ProductCategory', 'data' => $entity->get('id')];
            } elseif ($conf['scope'] == 'Channel') {
                $id = (string)$category->get('id');
                $inputRow->channelsIds = $channelsIds;
                $restoreRow->channelsIds = array_column($category->get('channels')->toArray(), 'id');

                $this->updateEntity($service, $id, $inputRow);

                $this->restore[] = ['action' => 'updated', 'entity' => 'ProductCategory', 'data' => [$id => $restoreRow]];
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