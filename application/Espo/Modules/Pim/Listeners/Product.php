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

use Espo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * Class Product
 *
 * @author r.zablodskiy@treolabs.com
 */
class Product extends AbstractListener
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function afterActionListLinked(array $data): array
    {
        if ($data['params']['link'] == 'productAttributeValues' && !empty($data['result']['list'])) {
            $attributes = $this
                ->getEntityManager()
                ->getRepository('Attribute')
                ->where([
                    'id' => array_column($data['result']['list'], 'attributeId')
                ])
                ->find();

            if (count($attributes) > 0) {
                foreach ($attributes as $attribute) {
                    foreach ($data['result']['list'] as $key => $item) {
                        if ($item->attributeId == $attribute->get('id')) {
                            $data['result']['list'][$key]->typeValue = $attribute->get('typeValue');
                            $data['result']['list'][$key]->attributeGroupId = $attribute->get('attributeGroupId');
                            $data['result']['list'][$key]->attributeGroupName = $attribute->get('attributeGroupName');

                            // for multiLang fields
                            if ($this->getConfig()->get('isMultilangActive')) {
                                foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                                    $multiLangField =  Util::toCamelCase('typeValue_' . strtolower($locale));
                                    $data['result']['list'][$key]->$multiLangField = $attribute->get($multiLangField);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
}
