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

namespace Pim\Listeners;

use Espo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ProductController
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeActionList(Event $event)
    {
        // get where
        $where = $event->getArgument('request')->get('where', []);

        // prepare where
        $where = $this
            ->getContainer()
            ->get('eventManager')
            ->dispatch('ProductController', 'prepareForProductType', new Event(['where' => $where]))
            ->getArgument('where');

        $where = $this
            ->getContainer()
            ->get('eventManager')
            ->dispatch('ProductController', 'prepareForAttributes', new Event(['where' => $where]))
            ->getArgument('where');

        // set where
        $event->getArgument('request')->setQuery('where', $where);
    }

    /**
     * @param Event $event
     */
    public function afterActionListLinked(Event $event)
    {
        // get data
        $data = $event->getArguments();

        if ($data['params']['link'] == 'productAttributeValues' && !empty($data['result']['list'])) {
            $attributes = $this
                ->getEntityManager()
                ->getRepository('Attribute')
                ->where(['id' => array_column($data['result']['list'], 'attributeId')])
                ->find();

            if (count($attributes) > 0) {
                foreach ($attributes as $attribute) {
                    foreach ($data['result']['list'] as $key => $item) {
                        if ($item->attributeId == $attribute->get('id')) {
                            // add type value to result
                            $data['result']['list'][$key]->typeValue = $attribute->get('typeValue');

                            // add attribute group
                            $data['result']['list'][$key]->attributeGroupId = $attribute->get('attributeGroupId');
                            $data['result']['list'][$key]->attributeGroupName = $attribute->get('attributeGroupName');

                            // add sort order
                            $data['result']['list'][$key]->sortOrder = $attribute->get('sortOrder');

                            // for multiLang fields
                            if ($this->getConfig()->get('isMultilangActive')) {
                                foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                                    $multiLangField = Util::toCamelCase('typeValue_' . strtolower($locale));
                                    $data['result']['list'][$key]->$multiLangField = $attribute->get($multiLangField);
                                }
                            }
                        }
                    }
                }
            }
            $event->setArgument('result', $data['result']);
        }
    }

    /**
     * @param Event $event
     */
    public function prepareForProductType(Event $event)
    {
        $where = $event->getArgument('where');

        // prepare types
        $types = $this
            ->getContainer()
            ->get('metadata')
            ->get('pim.productType');

        // prepare where
        $where[] = [
            'type'      => 'in',
            'attribute' => 'type',
            'value'     => array_keys($types)
        ];

        $event->setArgument('where', $where);
    }

    /**
     * @param Event $event
     */
    public function prepareForAttributes(Event $event)
    {
        $data = $event->getArgument('where');

        $event->setArgument('where', $this->prepareAttributesWhere($data));
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

    /**
     * @param array $data
     *
     * @return array
     */
    protected function prepareAttributesWhere(array $data): array
    {
        foreach ($data as $k => $row) {
            // check if exists array by key value
            $isValueArray = !empty($row['value']) && is_array($row['value']);
            if (empty($row['isAttribute']) && $isValueArray) {
                $data[$k]['value'] = $this->prepareAttributesWhere($row['value']);
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
}
