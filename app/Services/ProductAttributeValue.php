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

namespace Pim\Services;

use Espo\ORM\Entity;
use Espo\Core\Utils\Json;
use Treo\Core\Utils\Util;

/**
 * ProductAttributeValue service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductAttributeValue extends AbstractService
{
    /**
     * @var array
     */
    protected $mandatorySelectAttributeList = ['locale', 'attributeType'];

    /**
     * @inheritdoc
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $entity->set('isCustom', $this->isCustom($entity));

        $this->convertValue($entity);
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
     */
    protected function convertValue(Entity $entity)
    {
        switch ($entity->get('attributeType')) {
            case 'array':
                $entity->set('value', Json::decode($entity->get('value'), true));
                break;
            case 'bool':
                $entity->set('value', (bool)$entity->get('value'));
                foreach ($this->getInputLanguageList() as $multiLangField) {
                    $entity->set($multiLangField, (bool)$entity->get($multiLangField));
                }
                break;
            case 'int':
                $entity->set('value', (int)$entity->get('value'));
                break;
            case 'unit':
            case 'float':
                $entity->set('value', (float)$entity->get('value'));
                break;
            case 'multiEnum':
                $entity->set('value', Json::decode($entity->get('value'), true));
                foreach ($this->getInputLanguageList() as $multiLangField) {
                    $entity->set($multiLangField, Json::decode($entity->get($multiLangField), true));
                }
                break;
        }
    }

    /**
     * @return array
     */
    protected function getInputLanguageList(): array
    {
        // prepare result
        $result = [];

        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                $result[$locale] = Util::toCamelCase('value_' . strtolower($locale));
            }
        }

        return $result;
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
}
