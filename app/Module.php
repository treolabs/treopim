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

namespace Pim;

use Espo\Core\Utils\Json;
use Treo\Core\ModuleManager\AbstractModule;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\Util;

/**
 * Class Module
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 5120;
    }

    /**
     * @inheritDoc
     */
    public function loadMetadata(\stdClass &$data)
    {
        parent::loadMetadata($data);

        // prepare result
        $result = Json::decode(Json::encode($data), true);

        $result['clientDefs']['Attribute']['dynamicLogic']['fields']['typeValue']['visible']['conditionGroup'] = [
            [
                'type'      => 'in',
                'attribute' => 'type',
                'value'     => [
                    'enum',
                    'multiEnum'
                ]
            ]
        ];

        /** @var Config $config */
        $config = $this->container->get('config');

        if ($config->get('isMultilangActive', false)) {
            foreach ($config->get('inputLanguageList', []) as $locale) {
                // prepare key
                $key = ucfirst(Util::toCamelCase(strtolower($locale)));

                $result['clientDefs']['Attribute']['dynamicLogic']['fields']['name' . $key]['visible']['conditionGroup'] = [
                    [
                        'type'      => 'isTrue',
                        'attribute' => 'isMultilang'
                    ]
                ];

                $result['clientDefs']['Attribute']['dynamicLogic']['fields']['typeValue' . $key]['visible']['conditionGroup'] = [
                    [
                        'type'      => 'in',
                        'attribute' => 'type',
                        'value'     => ['enum', 'multiEnum']
                    ],
                    [
                        'type'      => 'isTrue',
                        'attribute' => 'isMultilang'
                    ]
                ];
            }
        }

        // set data
        $data = Json::decode(Json::encode($result));
    }
}
