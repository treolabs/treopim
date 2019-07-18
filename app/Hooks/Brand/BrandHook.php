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

namespace Pim\Hooks\Brand;

use Espo\Core\Exceptions\BadRequest;
use Pim\Core\Hooks\AbstractHook;
use Pim\Services\Product;
use Espo\ORM\Entity;

/**
 * Brand hook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class BrandHook extends AbstractHook
{

    /**
     * Before save action
     *
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        if (!$this->isCodeValid($entity)) {
            throw new BadRequest(
                $this->translate(
                    'Code is invalid',
                    'exceptions',
                    'Global'
                )
            );
        }
    }

    /**
     * After save action
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        $this->updateProductActivation($entity);
    }

    /**
     * Deactivate Product if Brand deactivated
     *
     * @param Entity $entity
     */
    protected function updateProductActivation(Entity $entity)
    {
        if ($entity->isAttributeChanged('isActive') && !$entity->get('isActive')) {
            // prepare condition for Product filter
            $params = [
                'where' => [
                    [
                        'type'      => 'equals',
                        'attribute' => 'brandId',
                        'value'     => $entity->get('id')
                    ],
                    [
                        'type'      => 'isTrue',
                        'attribute' => 'isActive'
                    ]
                ]
            ];

            $this->getProductService()->massUpdate(['isActive' => false], $params);
        }
    }

    /**
     * Create Product service
     *
     * @return Product
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function getProductService(): Product
    {
        return $this->getServiceFactory()->create('Product');
    }
}
