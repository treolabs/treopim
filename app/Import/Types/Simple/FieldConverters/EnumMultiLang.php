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

namespace Pim\Import\Types\Simple\FieldConverters;

use Espo\Core\Exceptions\Error;
use Treo\Core\Utils\Util;

/**
 * Class EnumMultiLang
 *
 * @author r.zablodskiy@treolabs.com
 */
class EnumMultiLang extends \Import\Types\Simple\FieldConverters\AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        $value = (is_null($config['column']) || $row[$config['column']] == '') ? $config['default'] : $row[$config['column']];
        $inputRow->{$config['name']} = $value;

        if (isset($config['attributeId'])) {
            $attribute = $config['attribute'];

            $typeValue = $attribute->get('typeValue');
            $key = array_search($value, $typeValue);

            if ($key !== false) {
                foreach ($this->container->get('config')->get('inputLanguageList', []) as $locale) {
                    $locale = ucfirst(Util::toCamelCase(strtolower($locale)));

                    $inputRow->{$config['name'] . $locale} = $attribute->get('typeValue' . $locale)[$key];
                }
            } else {
                throw new Error("Not found any values for attribute '{$attribute->get('name')}'");
            }
        }
    }
}