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
