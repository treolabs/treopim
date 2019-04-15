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
     * @inheritdoc
     */
    protected function duplicateLinks(Entity $entity, Entity $duplicatingEntity)
    {
        if (!empty($products = $duplicatingEntity->get('products'))) {
            // get service
            $productService = $this->getInjection('serviceFactory')->create('Product');

            foreach ($products as $product) {
                // prepare data
                $data = $productService->getDuplicateAttributes($product->get('id'));
                $data->catalogId = $entity->get('id');

                // create entity
                $productService->createEntity($data);
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
    }
}
