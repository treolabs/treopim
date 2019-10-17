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

namespace Pim\SelectManagers;

use Pim\Core\SelectManagers\AbstractSelectManager;
use Treo\Core\Utils\Util;

/**
 * ProductAttributeValue select manager
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductAttributeValue extends AbstractSelectManager
{
    /**
     * @inheritdoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        $arrayWhereTypes = ['arrayAnyOf', 'arrayNoneOf', 'arrayIsEmpty', 'arrayIsNotEmpty'];

        if (isset($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $k => $v) {
                if (is_array($v['value']) && !empty($v['value'])) {
                    foreach ($v['value'] as $key => $value) {
                        if (isset($value['type']) && in_array($value['type'], $arrayWhereTypes)) {
                            $params['where'][$k]['value'][$key] = $this->prepareWhere($value);
                        }
                    }
                }
            }
        }

        // get select params
        $selectParams = parent::getSelectParams($params, $withAcl, $checkWherePermission);

        // prepare product types
        $types = implode("','", array_keys($this->getMetadata()->get('pim.productType', [])));

        // add filtering by product types
        $selectParams['customWhere'] .= " AND product_attribute_value.product_id IN (SELECT id FROM product WHERE type IN ('$types') AND deleted=0)";

        return $selectParams;
    }

    /**
     * @inheritDoc
     */
    public function applyAdditional(array &$result, array $params)
    {
        if ($this->isSubQuery) {
            return false;
        }

        // prepare additional select columns
        $additionalSelectColumns = [
            'typeValue' => 'attribute.type_value',
            'attributeGroupId' => 'ag1.id',
            'attributeGroupName' => 'ag1.name'
        ];

        // prepare for multiLang fields
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                $field = Util::toCamelCase('typeValue_' . strtolower($locale));
                $dbField = 'attribute.type_value_' . strtolower($locale);

                $additionalSelectColumns[$field] = $dbField;
            }
        }

        $result['customJoin'] .= " LEFT JOIN attribute_group AS ag1 ON ag1.id=attribute.attribute_group_id AND ag1.deleted=0";

        foreach ($additionalSelectColumns as $alias => $sql) {
            $result['additionalSelectColumns'][$sql] = $alias;
        }
    }

    /**
     * Prepare where for array attributes
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareWhere(array $data): array
    {
        $where = [];

        switch ($data['type']) {
            case 'arrayAnyOf':
            case 'arrayNoneOf':
                $where = [
                    'type'  => $data['type'] == 'arrayAnyOf' ? 'or' : 'and',
                    'value' => []
                ];

                foreach ($data['value'] as $value) {
                    $where['value'][] = [
                        'type'      => $data['type'] == 'arrayAnyOf' ? 'contains' : 'notContains',
                        'attribute' => 'value',
                        'value'     => "\"$value\""
                    ];
                }
                break;
            case 'arrayIsEmpty':
                $where = [
                    'type'  => 'or',
                    'value' => [
                        [
                            'type'      => 'isNull',
                            'attribute' => 'value'
                        ],
                        [
                            'type'      => 'equals',
                            'attribute' => 'value',
                            'value'     => '[]'
                        ]
                    ]
                ];
                break;
            case 'arrayIsNotEmpty':
                $where = [
                    'type'  => 'and',
                    'value' => [
                        [
                            'type'      => 'isNotNull',
                            'attribute' => 'value'
                        ],
                        [
                            'type'      => 'notEquals',
                            'attribute' => 'value',
                            'value'     => '[]'
                        ]
                    ]
                ];
                break;
        }

        return $where;
    }

    /**
     * @param array $result
     */
    protected function boolFilterLinkedWithAttributeGroup(array &$result)
    {
        $data = (array)$this->getSelectCondition('linkedWithAttributeGroup');

        if (isset($data['productId'])) {
            $attributes = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->select(['id'])
                ->distinct()
                ->join('attribute')
                ->where(
                    [
                        'productId' => $data['productId'],
                        'attribute.attributeGroupId'
                                    => ($data['attributeGroupId'] != '') ? $data['attributeGroupId'] : null
                    ]
                )
                ->find()
                ->toArray();

            $result['whereClause'][] = [
                'id' => array_column($attributes, 'id')
            ];
        }
    }
}
