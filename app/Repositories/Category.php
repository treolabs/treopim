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
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class Category
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Category extends Base
{
//    public function update

    /**
     * @param string $productId
     *
     * @return array
     */
    public function getProductsIdsThatCanBeRelatedWithCategory(string $categoryId): array
    {
        /** @var Entity $category */
        $category = $this->get($categoryId);

        /** @var string $treeId */
        $treeId = empty($category->get('categoryRoute')) ? $categoryId : explode("|", $category->get('categoryRoute'))[1];

        return $this
            ->getEntityManager()
            ->nativeQuery(
                "SELECT DISTINCT p.id
                 FROM catalog_category cc
                   LEFT JOIN product p ON p.catalog_id=cc.catalog_id AND p.deleted=0
                 WHERE cc.deleted=0
                   AND cc.category_id=:treeId
                   AND p.id NOT IN (SELECT product_id FROM product_category_linker WHERE category_id=:id AND deleted=0)",
                ['id' => $categoryId, 'treeId' => $treeId]
            )
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param string $categoryId
     *
     * @return bool
     */
    public function hasChild(string $categoryId): bool
    {
        return !empty($this->select(['id'])->where(['categoryParentId' => $categoryId])->findOne());
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

        if ($relationName == 'products') {
            if ($this->hasChild((string)$entity->get('id'))) {
                throw new BadRequest("Any product can't be related to category if category has child category");
            }

            if (!in_array($foreignId, $this->getProductsIdsThatCanBeRelatedWithCategory((string)$entity->get('id')))) {
                throw new BadRequest("Such product can't be related with current category");
            }
        }

        parent::beforeRelate($entity, $relationName, $foreign, $data, $options);
    }

    /**
     * @inheritDoc
     */
    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        parent::afterRelate($entity, $relationName, $foreign, $data, $options);
    }

    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    protected function beforeMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
        if ($relationName == 'products') {
            throw new BadRequest('Action is unavailable');
        }

        parent::beforeMassRelate($entity, $relationName, $params, $options);
    }
}
