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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\Exceptions\Error;
use Treo\Listeners\AbstractListener;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;

/**
 * Class ProductAttributeValue
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductAttributeValue extends AbstractListener
{
    /**
     * @param array $data
     *
     * @return array
     *
     * @throws Error
     */
    public function afterActionRead(array $data): array
    {
        if (isset($data['result']->attributeId)) {
            $data['result'] = $this->prepareArrayType($data['result']);

            $attribute = $this->getEntityManager()->getEntity('Attribute', $data['result']->attributeId);

            if (!empty($attribute)) {
                $data['result']->typeValue = $attribute->get('typeValue');

                // for multiLang fields
                if ($this->getConfig()->get('isMultilangActive')) {
                    foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                        $multiLangField =  Util::toCamelCase('typeValue_' . strtolower($locale));
                        $data['result']->$multiLangField = $attribute->get($multiLangField);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param \stdClass $result
     *
     * @return \stdClass
     */
    protected function prepareArrayType(\stdClass $result): \stdClass
    {
        if (in_array($result->attributeType, ['array', 'arrayMultiLang', 'multiEnum', 'multiEnumMultiLang'])) {
            $result->value = Json::decode($result->value, true);

            // for multiLang fields
            if ($this->getConfig()->get('isMultilangActive')) {
                foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                    $multiLangField =  Util::toCamelCase('value_' . strtolower($locale));
                    $result->$multiLangField = Json::decode($result->$multiLangField, true);
                }
            }
        }

        return $result;
    }
}
