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

namespace Pim\Import\Handlers;

use Espo\ORM\Entity;
use Import\Handlers\AbstractHandler;

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
    protected $attributes = [];

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @param array $fileData
     * @param array $data
     *
     * @return bool
     */
    public function run(array $fileData, array $data): bool
    {
        if (!empty($result = $this->prepareRows($fileData, $data))) {
            $importResultId = (string)$data['data']['importResultId'];
            $delimiter = $data['data']['delimiter'];

            $service = $this->getServiceFactory()->create('Product');

            foreach ($result as $input) {
                $entity = null;

                // prepare id
                $id = $input->_id;
                unset($input->_id);

                // prepare file row
                $fileRow = (string)$input->_fileRow;
                unset($input->_fileRow);

                // prepare action
                $action = (empty($id)) ? 'create' : 'update';

                try {
                    $this->getEntityManager()->getPDO()->beginTransaction();

                    if (empty($id)) {
                        $entity = $service->createEntity($input);
                    } else {
                        $entity = $service->updateEntity($id, $input);
                    }

                    foreach ($this->categories as $value) {
                        $this->importCategories($entity, $value, $delimiter);
                    }

                    foreach ($this->attributes as $value) {
                        $this->importAttribute($entity, $value, $delimiter);
                    }

                    $this->getEntityManager()->getPDO()->commit();

                } catch (\Throwable $e) {
                    $this->getEntityManager()->getPDO()->rollBack();
                    $this->log('Product', $importResultId, 'error', $fileRow, $e->getMessage());
                }
                if (!is_null($entity)) {
                    $this->log('Product', $importResultId, $action, $fileRow, $entity->get('id'));
                }
            }
        }

        return true;
    }

    /**
     * @param array $fileData
     * @param array $data
     *
     * @return array
     */
    protected function prepareRows(array $fileData, array $data): array
    {
        $result = [];

        $idField = isset($data['data']['idField']) ? $data['data']['idField'] : null;

        if (!empty($idRow = $this->getIdRow($data['data']['configuration'], $idField))) {
            $exists = $this->getExists('Product', $idRow['name'], array_column($fileData, $idRow['column']));
        }

        $fileRow = (int)$data['offset'];

        foreach ($fileData as $row) {
            $fileRow++;

            $inputRow = new \stdClass();
            $inputRow->_fileRow = $fileRow;
            $inputRow->_id = (isset($exists[$row[$idRow['column']]])) ? $exists[$row[$idRow['column']]] : null;

            foreach ($data['data']['configuration'] as $key => $item) {
                if (isset($item['attributeId'])) {
                    $this->attributes[] = [
                        'item' => $item,
                        'row' => $row
                    ];

                    continue;
                } elseif ($item['name'] == 'productCategories') {
                    $this->categories[] = [
                        'item' => $item,
                        'row' => $row
                    ];

                    continue;
                } else {
                    $this->convertItem($inputRow, 'Product', $item, $row, $data['data']['delimiter']);
                }
            }

            $result[] = $inputRow;
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
        $conf = $data['item'];
        $row = $data['row'];

        foreach ($product->get('productAttributeValues') as $item) {
            if ($item->get('attributeId') == $conf['attributeId'] && $item->get('scope') == $conf['scope']) {
                if ($conf['scope'] == 'Global') {
                    $inputRow->id = $item->get('id');
                } elseif ($conf['scope'] == 'Channel') {
                    $channels = array_column($item->get('channels')->toArray(), 'id');

                    if (empty($diff = array_diff($conf['channelsIds'], $channels))) {
                        $inputRow->id = $item->get('id');
                    } elseif (count($diff) != count($conf['channelsIds'])) {
                        if (empty($item->get('productFamilyAttributeId'))) {
                            $inputRow->channelsIds = array_diff($channels, $conf['channelsIds']);
                            $service->updateEntity($item->get('id'), $inputRow);
                        } else {
                            return;
                        }
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

            $service->createEntity($inputRow);
        } else {
            $id = $inputRow->id;
            unset($inputRow->id);

            $service->updateEntity($id, $inputRow);
        }
    }

    /**
     * @param Entity $product
     * @param array $data
     * @param string $delimiter
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

            if (empty($id = $this->getProductCategory($product, $exist, $conf['scope']))) {
                $inputRow->categoryId = $exist;
                $inputRow->productId = $product->get('id');
                $inputRow->scope = $conf['scope'];

                if ($conf['scope'] == 'Channel') {
                    $inputRow->channelsIds = $channelsIds;
                }

                $service->createEntity($inputRow);
            } elseif ($conf['scope'] == 'Channel') {
                $inputRow->channelsIds = $channelsIds;
                $service->updateEntity($id, $inputRow);
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
    protected function getProductCategory(Entity $product, string $categoryId, string $scope): ?string
    {
        $result = null;

        foreach ($product->get('productCategories') as $item) {
            if ($item->get('categoryId') == $categoryId && $item->get('scope') == $scope) {
                $result = $item->get('id');
                break;
            }
        }

        return $result;
    }
}