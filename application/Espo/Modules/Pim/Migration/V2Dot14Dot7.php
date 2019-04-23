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

namespace Espo\Modules\Pim\Migration;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;

/**
 * Migration class for version 2.14.7
 *
 * @author r.zablodskiy@treolabs.com
 */
class V2Dot14Dot7 extends \Treo\Core\Migration\AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->distinct()
            ->join('attribute')
            ->where([
                'attribute.type' => ['array', 'multiEnum', 'arrayMultiLang', 'multiEnumMultiLang']
            ])
            ->find();

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                // prepare value field
                $fields = ['value'];

                // prepare multi lang fields
                if ($this->getConfig()->get('isMultilangActive')) {
                    foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                        $fields[] = Util::toCamelCase('value_' . strtolower($locale));
                    }
                }

                foreach ($fields as $field) {
                    if (!empty($value = $attribute->get($field))) {
                        // update attribute values
                        $attribute->set(
                            $field,
                            Json::encode(Json::decode($value), \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES)
                        );
                    }
                }

                $this->getEntityManager()->saveEntity($attribute);
            }
        }
    }
}
