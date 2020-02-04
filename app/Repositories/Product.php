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

namespace Pim\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;
use Espo\Core\Utils\Util;
use Espo\Core\Templates\Repositories\Base;

/**
 * Class Product
 *
 * @author r.ratsun@treolabs.com
 */
class Product extends Base
{
    /**
     * @return array
     */
    public function getInputLanguageList(): array
    {
        return $this->getConfig()->get('inputLanguageList', []);
    }

    /**
     * @param string $productId
     *
     * @return array
     */
    public function getCategoriesIdsThatCanBeRelatedWithProduct(string $productId): array
    {
        // get trees
        $trees = $this
            ->getEntityManager()
            ->nativeQuery(
                "SELECT category_id FROM catalog_category WHERE catalog_id=(SELECT catalog_id FROM product WHERE id=:product_id AND deleted=0) ANd deleted=0",
                ['product_id' => $productId]
            )
            ->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($trees)) {
            return [];
        }

        $whereTree = [];
        foreach ($trees as $tree) {
            $whereTree[] = "(c.category_route LIKE '%|$tree|%' OR c.id='$tree')";
        }
        $whereTree = implode(' OR ', $whereTree);

        return $this
            ->getEntityManager()
            ->nativeQuery(
                "SELECT DISTINCT c.id
                 FROM category c
                   LEFT JOIN category c1 ON c1.category_parent_id=c.id AND c1.deleted=0
                 WHERE c.deleted=0
                   AND c1.id IS NULL
                   AND c.id NOT IN (SELECT category_id FROM product_category_linker WHERE product_id=:product_id AND deleted=0)
                   AND ($whereTree)",
                ['product_id' => $productId]
            )
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @inheritdoc
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        // save attributes
        $this->saveAttributes($entity);

        // parent action
        parent::afterSave($entity, $options);
    }

    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    protected function beforeRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        /** @var string $foreignId */
        $foreignId = is_string($foreign) ? $foreign : (string)$foreign->get('id');

        if ($relationName == 'categories' && !in_array($foreignId, $this->getCategoriesIdsThatCanBeRelatedWithProduct((string)$entity->get('id')))) {
            throw new BadRequest("Such category can't be related with current product");
        }

        parent::beforeRelate($entity, $relationName, $foreign, $data, $options);
    }

    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    protected function beforeMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
        if ($relationName == 'categories') {
            throw new BadRequest('Action is unavailable');
        }

        parent::beforeMassRelate($entity, $relationName, $params, $options);
    }

    /**
     * @inheritDoc
     */
    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        if ($relationName == 'channels') {
            $this->unrelateCategoryByChannel($entity, is_string($foreign) ? $foreign : (string)$foreign->get('id'));
        }

        parent::afterUnrelate($entity, $relationName, $foreign, $options);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     * @throws Error
     */
    protected function saveAttributes(Entity $product): bool
    {
        if (!empty($product->productAttribute)) {
            $data = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(
                    [
                        'productId'   => $product->get('id'),
                        'attributeId' => array_keys($product->productAttribute),
                        'scope'       => 'Global'
                    ]
                )
                ->find();

            // prepare exists
            $exists = [];
            if (count($data) > 0) {
                foreach ($data as $v) {
                    $exists[$v->get('attributeId')] = $v;
                }
            }

            foreach ($product->productAttribute as $attributeId => $values) {
                if (isset($exists[$attributeId])) {
                    $entity = $exists[$attributeId];
                } else {
                    $entity = $this->getEntityManager()->getEntity('ProductAttributeValue');
                    $entity->set('productId', $product->get('id'));
                    $entity->set('attributeId', $attributeId);
                    $entity->set('scope', 'Global');
                }

                foreach ($values['locales'] as $locale => $value) {
                    if ($locale == 'default') {
                        $entity->set('value', $value);
                    } else {
                        // prepare locale
                        $locale = Util::toCamelCase(strtolower($locale), '_', true);
                        $entity->set("value$locale", $value);
                    }
                }

                if (isset($values['data']) && !empty($values['data'])) {
                    foreach ($values['data'] as $field => $item) {
                        $entity->set($field, $item);
                    }
                }

                $this->getEntityManager()->saveEntity($entity);
            }
        }

        return true;
    }

    /**
     * @param Entity $product
     * @param string $channelId
     */
    protected function unrelateCategoryByChannel(Entity $product, string $channelId): void
    {
        $this
            ->getEntityManager()
            ->nativeQuery(
                "UPDATE product_category_linker SET deleted=1 WHERE product_id='{$product->get('id')}' AND deleted=0 AND category_id IN (SELECT category_id FROM category_channel_linker WHERE channel_id=:channel_id AND deleted=0)",
                ['channel_id' => $channelId]
            );
    }
}
