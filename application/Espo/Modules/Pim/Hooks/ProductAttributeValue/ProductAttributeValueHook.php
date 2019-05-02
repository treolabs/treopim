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

namespace Espo\Modules\Pim\Hooks\ProductAttributeValue;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Hooks\Base as BaseHook;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;

/**
 * Class ProductAttributeValueHook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductAttributeValueHook extends BaseHook
{
    /**
     * @var array
     */
    protected $beforeSaveData = [];

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        // exit
        if (!empty($options['skipProductAttributeValueHook'])) {
            return true;
        }

        if (empty($product = $entity->get('product')) || empty($category = $entity->get('attribute'))) {
            throw new BadRequest($this->exception('Product and Attribute cannot be empty'));
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }

        // storing data
        if (!$entity->isNew()) {
            $this->beforeSaveData = $this->getEntityManager()->getEntity('ProductAttributeValue', $entity->get('id'))->toArray();
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeRemove(Entity $entity, $options = [])
    {
        // exit
        if (!empty($options['skipProductAttributeValueHook'])) {
            return true;
        }

        if (!empty($entity->get('productFamily'))) {
            throw new BadRequest($this->exception('Product Family attribute cannot be deleted'));
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        // exit
        if (!empty($options['skipProductAttributeValueHook'])) {
            return true;
        }

        // create note
        $this->createNote($entity);
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        // parent init
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        // prepare count
        $count = 0;

        if ($entity->get('scope') == 'Global') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(
                    [
                        'id!='        => $entity->get('id'),
                        'productId'   => $entity->get('productId'),
                        'attributeId' => $entity->get('attributeId'),
                        'scope'       => 'Global',
                    ]
                )
                ->count();
        }

        if ($entity->get('scope') == 'Channel') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->distinct()
                ->join('channels')
                ->where(
                    [
                        'id!='        => $entity->get('id'),
                        'productId'   => $entity->get('productId'),
                        'attributeId' => $entity->get('attributeId'),
                        'scope'       => 'Channel',
                        'channels.id' => $entity->get('channelsIds'),
                    ]
                )
                ->count();
        }

        return empty($count);
    }

    /**
     * @param Entity $entity
     */
    protected function createNote(Entity $entity)
    {
        $note = $this->getEntityManager()->getEntity('Note');
        $note->set('type', 'Update');
        $note->set('parentId', $entity->get('productId'));
        $note->set('parentType', 'Product');
        $note->set('data', $this->getNoteData($entity));
        $note->set('attributeId', $entity->get('attributeId'));

        $this->getEntityManager()->saveEntity($note);
    }

    /**
     * Get note data
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function getNoteData(Entity $entity): array
    {
        // get attribute
        $attribute = $entity->get('attribute');

        // prepare field name
        $fieldName = $this
                ->getInjection('language')
                ->translate('Attribute', 'custom', 'ProductAttributeValue') . ' ' . $attribute->get('name');

        // prepare result
        $result = [];

        // prepare array types
        $arrayTypes = ['array', 'arrayMultiLang', 'enum', 'enumMultiLang', 'multiEnum', 'multiEnumMultiLang'];

        // for value
        if ($entity->isAttributeChanged('value') || ($entity->isAttributeChanged('data') && $this->beforeSaveData['data']['unit'] != $entity->get('data')->unit)) {
            $result['fields'][] = $fieldName;
            if (in_array($attribute->get('type'), $arrayTypes)) {
                $result['attributes']['was'][$fieldName] = Json::decode($this->beforeSaveData['value'], true);
            } else {
                $result['attributes']['was'][$fieldName] = $this->beforeSaveData['value'];
            }
            $result['attributes']['became'][$fieldName] = $entity->get('value');

            if ($entity->isAttributeChanged('data')) {
                $result['attributes']['was'][$fieldName . 'Unit'] = $this->beforeSaveData['data']->unit;
                $result['attributes']['became'][$fieldName . 'Unit'] = $entity->get('data')->unit;
            }
        }

        // for multilang value
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                // prepare field
                $field = Util::toCamelCase('value_' . strtolower($locale));

                if ($entity->isAttributeChanged($field)) {
                    // prepare field name
                    $localeFieldName = $fieldName . " ($locale)";
                    $result['fields'][] = $localeFieldName;
                    if (in_array($attribute->get('type'), $arrayTypes)) {
                        $result['attributes']['was'][$localeFieldName]
                            = Json::decode($this->beforeSaveData[$field], true);
                    } else {
                        $result['attributes']['was'][$localeFieldName] = $this->beforeSaveData[$field];
                    }
                    $result['attributes']['became'][$localeFieldName] = $entity->get($field);
                }
            }
        }

        return $result;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'ProductAttributeValue');
    }
}
