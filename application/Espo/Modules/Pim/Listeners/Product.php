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

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\ORM\Entity;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;

/**
 * Product listener
 *
 * @author r.ratsun@treolabs.com
 */
class Product extends AbstractPimListener
{

    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionList(array $data): array
    {
        // get where
        $where = $data['request']->get('where', []);

        // prepare where
        $where = $this->prepareForAttributes($where);

        // set where
        $data['request']->setQuery('where', $where);

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function updateAttribute(array $data): array
    {
        if (isset($data['attributeValue']) && isset($data['post']) && isset($data['productId'])) {
            // create note
            $note = $this->getEntityManager()->getEntity('Note');
            $note->set('type', 'Update');
            $note->set('parentId', $data['productId']);
            $note->set('parentType', 'Product');
            $note->set('data', $this->getNoteData($data['attributeValue'], $data['post']));
            $note->set('attributeId', $data['post']['attributeId']);

            $this->getEntityManager()->saveEntity($note);
        }

        return $data;
    }

    /**
     * After create link
     *
     * @param array $data
     *
     * @return array
     */
    public function afterActionCreateLink(array $data): array
    {
        if ($data['params']['link'] == 'attributes') {
            $this->setProductAttributeValueUser($data['data']->ids, (array)$data['params']['id']);
        }

        return $data;
    }

    /**
     * Get note data
     *
     * @param Entity $attributeValue
     * @param array  $post
     *
     * @return array
     */
    protected function getNoteData(Entity $attributeValue, array $post): array
    {
        // get attribute
        $attribute = $this
            ->getEntityManager()
            ->getEntity('Attribute', $attributeValue->get('attributeId'));

        // prepare field name
        $fieldName = $this->getLanguage()->translate('Attribute', 'custom', 'ProductAttributeValue');
        $fieldName .= ' ' . $attribute->get('name');

        // prepare result
        $result = [];

        $arrayTypes = ['array', 'arrayMultiLang', 'enum', 'enumMultiLang', 'multiEnum', 'multiEnumMultiLang'];

        // for value
        if ($post['value'] != $attributeValue->get('value')
            || (isset($post['data']['unit']) && $post['data']['unit'] != $attributeValue->get('data')->unit)) {
            $result['fields'][] = $fieldName;

            if (in_array($attribute->get('type'), $arrayTypes)) {
                $result['attributes']['was'][$fieldName] = Json::decode($attributeValue->get('value'), true);
            } else {
                $result['attributes']['was'][$fieldName] = $attributeValue->get('value');
            }

            $result['attributes']['became'][$fieldName] = $post['value'];

            if (isset($post['data']['unit'])) {
                $result['attributes']['was'][$fieldName . 'Unit'] = $attributeValue->get('data')->unit;
                $result['attributes']['became'][$fieldName . 'Unit'] = $post['data']['unit'];
            }
        }

        // for multilang value
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                // prepare field
                $field = Util::toCamelCase('value_' . strtolower($locale));

                if (isset($post[$field]) && $post[$field] != $attributeValue->get($field)) {
                    // prepare field name
                    $localeFieldName = $fieldName . " ($locale)";

                    $result['fields'][] = $localeFieldName;

                    if (in_array($attribute->get('type'), $arrayTypes)) {
                        $result['attributes']['was'][$localeFieldName]
                            = Json::decode($attributeValue->get($field), true);
                    } else {
                        $result['attributes']['was'][$localeFieldName] = $attributeValue->get($field);
                    }

                    $result['attributes']['became'][$localeFieldName] = $post[$field];
                }
            }
        }

        return $result;
    }

    /**
     * @param array $where
     *
     * @return array
     */
    public function prepareForAttributes(array $data): array
    {
        foreach ($data as $k => $row) {
            // check if exists array by key value
            $isValueArray = !empty($row['value']) && is_array($row['value']);

            if (empty($row['isAttribute']) && $isValueArray) {
                $data[$k]['value'] = $this->prepareForAttributes($row['value']);
            } elseif (!empty($row['isAttribute'])) {
                // prepare attribute where
                switch ($row['type']) {
                    case 'isTrue':
                        $where = [
                            'type'  => 'and',
                            'value' => [
                                [
                                    'type'      => 'equals',
                                    'attribute' => 'attributeId',
                                    'value'     => $row['attribute']
                                ],
                                [
                                    'type'      => 'equals',
                                    'attribute' => 'value',
                                    'value'     => 'TreoBoolIsTrue'
                                ]
                            ]
                        ];

                        break;
                    case 'isFalse':
                        $where = [
                            'type'  => 'and',
                            'value' => [
                                [
                                    'type'      => 'equals',
                                    'attribute' => 'attributeId',
                                    'value'     => $row['attribute']
                                ],
                                [
                                    'type'  => 'or',
                                    'value' => [
                                        [
                                            'type'      => 'isNull',
                                            'attribute' => 'value'
                                        ],
                                        [
                                            'type'      => 'equals',
                                            'attribute' => 'value',
                                            'value'     => 'TreoBoolIsFalse'
                                        ]
                                    ]
                                ],
                            ]
                        ];

                        break;
                    default:
                        $where = [
                            'type'  => 'and',
                            'value' => [
                                [
                                    'type'      => 'equals',
                                    'attribute' => 'attributeId',
                                    'value'     => $row['attribute']
                                ],
                                [
                                    'type'      => $row['type'],
                                    'attribute' => 'value',
                                    'value'     => $row['value']
                                ]
                            ]
                        ];

                        break;
                }

                $productWhere = [
                    'type'      => 'equals',
                    'attribute' => 'id',
                    'value'     => $this->getProductIds([$where])
                ];

                // prepare where clause
                $data[$k] = $productWhere;
            }
        }

        return $data;
    }

    /**
     * Get products filtered by attributes
     *
     * @param array $where
     *
     * @return array
     */
    protected function getProductIds(array $where = []): array
    {
        // prepare result
        $result = ['empty-id-filter'];

        // get data
        $data = $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('ProductAttributeValue')
            ->findEntities(['where' => $where]);

        if ($data['total'] > 0) {
            $result = [];
            foreach ($data['collection'] as $entity) {
                if (!empty($entity->get('product')) && !in_array($entity->get('productId'), $result)) {
                    $result[] = $entity->get('productId');
                }
            }
        }

        return $result;
    }
}
