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
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class ProductFamily
 *
 * @author r.ratsun@treolabs.com
 */
class ProductFamily extends Base
{
    /**
     * @inheritDoc
     *
     * @throws BadRequest
     * @throws Error
     */
    public function unrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        if ($relationName == 'productFamilyAttributes') {
            if (is_string($foreign)) {
                $foreign = $this->getEntityManager()->getEntity('ProductFamilyAttribute', $foreign);
            } elseif (!$foreign instanceof Entity) {
                throw new BadRequest("'Remove all relations' action is blocked for such relation");
            }

            if (!empty($foreign->get('attribute')->get('locale'))) {
                throw new BadRequest("Locale attribute can't be unlinked");
            }

            $this
                ->getEntityManager()
                ->removeEntity($foreign, ['skipAttributeValueDeleting' => 1]);

            return true;
        }

        return parent::unrelate($entity, $relationName, $foreign, $options);
    }

    /**
     * @param string $id
     * @param string $scope
     *
     * @return array
     */
    public function getLinkedAttributesIds(string $id, string $scope = 'Global'): array
    {
        $data = $this
            ->getEntityManager()
            ->getRepository('ProductFamilyAttribute')
            ->select(['attributeId'])
            ->where(['productFamilyId' => $id, 'scope' => $scope])
            ->find()
            ->toArray();

        return array_column($data, 'attributeId');
    }

    /**
     * @param array       $productFamiliesIds
     * @param string|null $attributeGroupId
     *
     * @return array
     */
    public function getLinkedWithAttributeGroup(array $productFamiliesIds, ?string $attributeGroupId): array
    {
        $data = $this
            ->getEntityManager()
            ->getRepository('ProductFamilyAttribute')
            ->select(['id'])
            ->distinct()
            ->join('attribute')
            ->where(
                [
                    'productFamilyId'            => $productFamiliesIds,
                    'attribute.attributeGroupId' => ($attributeGroupId != '') ? $attributeGroupId : null
                ]
            )
            ->find()
            ->toArray();

        return array_column($data, 'id');
    }

    /**
     * @inheritdoc
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        // unlink products
        if (!empty($products = $entity->get('products'))) {
            foreach ($products as $product) {
                $product->set('productFamilyId', null);
                $this->getEntityManager()->saveEntity($product);
            }
        }
    }
}
