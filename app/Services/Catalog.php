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

use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

/**
 * Catalog service
 *
 * @author r.ratsun@treolabs.com
 */
class Catalog extends Base
{
    /**
     * @inheritdoc
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        // get products count
        $productsCount = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['catalogId' => $entity->get('id')])
            ->count();

        // set products count to entity
        if (!empty($productsCount)) {
            $entity->set('productsCount', $productsCount);
        }
    }

    /**
     * @param Entity $entity
     * @param Entity $duplicatingEntity
     */
    protected function duplicateProducts(Entity $entity, Entity $duplicatingEntity)
    {
        if (!empty($products = $duplicatingEntity->get('products'))) {
            // get language
            $language = $this->getInjection('language');

            foreach ($products as $product) {
                if (!in_array($product->get('type'), array_keys($this->getMetadata()->get('pim.productType', [])))) {
                    continue 1;
                }

                // prepare name
                $name = sprintf(
                    $language->translate("Create product '%s' for catalog '%s'", "queueManager", "Catalog"),
                    $product->get('name'),
                    $entity->get('name')
                );

                // prepare data
                $data = [
                    'productId' => $product->get('id'),
                    'catalogId' => $entity->get('id')
                ];

                $this
                    ->getInjection('queueManager')
                    ->push($name, 'QueueManagerDuplicateProduct', $data);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('serviceFactory');
        $this->addDependency('queueManager');
        $this->addDependency('language');
    }
}
