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

namespace Espo\Modules\Pim\Repositories;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;

/**
 * Class Attribute
 *
 * @author r.ratsun@treolabs.com
 */
class Attribute extends \Espo\Core\Templates\Repositories\Base
{
    /**
     * @inheritdoc
     */
    public function findRelated(Entity $entity, $relationName, array $params = [])
    {
        if ($relationName == 'productFamilies' && !empty($params['select'])) {
            $params['select'] = $this->prepareSelect($params['select']);
        }

        return parent::findRelated($entity, $relationName, $params);
    }

    /**
     * Prepare select for 'productFamilies' returning
     *
     * @param array $data
     *
     * @return array
     */
    public function prepareSelect(array $data): array
    {
        foreach ($data as $k => $v) {
            switch ($v) {
                case 'isRequired':
                    $data[$k] = ['product_family_attribute_linker.is_required', 'isRequired'];
                    break;
                case 'isMultiChannel':
                    $data[$k] = ['product_family_attribute_linker.is_multi_channel', 'isMultiChannel'];
                    break;
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @inheritdoc
     */
    protected function beforeUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        if ($relationName == 'products') {
            // prepare data
            $attributeId = (string)$entity->get('id');
            $productId = (is_string($foreign)) ? $foreign : (string)$foreign->get('id');

            if ($this->isProductFamilyAttribute($attributeId, $productId)) {
                throw new Error($this->exception("You can not unlink product family attribute"));
            }
        }
    }

    /**
     * @param string $attributeId
     * @param string $productId
     *
     * @return bool
     */
    protected function isProductFamilyAttribute(string $attributeId, string $productId): bool
    {
        $value = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->select(['id'])
            ->where(['attributeId' => $attributeId, 'productId' => $productId, 'productFamilyId !=' => null])
            ->findOne();

        return !empty($value);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, "exceptions", "Attribute");
    }
}
