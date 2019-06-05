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

namespace Espo\Modules\Pim\Services;

use Espo\ORM\Entity;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;

/**
 * ProductAttributeValue service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductAttributeValue extends AbstractService
{
    /**
     * @inheritdoc
     */
    public function getSelectAttributeList($params)
    {
        // prepare select attributes list
        $attributeList = parent::getSelectAttributeList($params);

        $attributeList[] = 'productFamilyAttributeId';
        $attributeList[] = 'attributeType';

        return $attributeList;
    }

    /**
     * @inheritdoc
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $entity->set('isCustom', $this->isCustom($entity));
        $entity->set('attributeType', (!empty($entity->get('attribute'))) ? $entity->get('attribute')->get('type') : null);

        $this->prepareArrayType($entity);
    }

    /**
     * @inheritdoc
     */
    public function updateEntity($id, $data)
    {
        // prepare data
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data->$k = Json::encode($v);
            }
        }

        return parent::updateEntity($id, $data);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    private function isCustom(Entity $entity): bool
    {
        // prepare is custom field
        $isCustom = true;

        if (!empty($productFamilyAttribute = $entity->get('productFamilyAttribute'))
            && !empty($productFamilyAttribute->get('productFamily'))) {
            $isCustom = false;
        }

        return $isCustom;
    }

    /**
     * Convert array attributes value to needed format
     *
     * @param Entity $entity
     */
    protected function prepareArrayType(Entity $entity)
    {
        $type = $entity->get('attributeType');

        if (in_array($type, ['array', 'arrayMultiLang', 'multiEnum', 'multiEnumMultiLang'])) {
            $entity->set('value', Json::decode($entity->get('value'), true));

            // for multiLang fields
            if ($this->getConfig()->get('isMultilangActive')
                && in_array($type, ['arrayMultiLang', 'multiEnumMultiLang'])) {
                foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                    $multiLangField =  Util::toCamelCase('value_' . strtolower($locale));
                    $entity->set($multiLangField, Json::decode($entity->get($multiLangField), true));
                }
            }
        }
    }
}
