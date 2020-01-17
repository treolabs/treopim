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

namespace Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Pim\Entities\ProductFamilyAttribute;
use Treo\Core\EventManager\Event;

/**
 * Class ProductFamilyEntity
 *
 * @package Pim\Listeners
 * @author  m.kokhanskyi@treolabs.com
 */
class ProductFamilyEntity extends AbstractEntityListener
{
    /**
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeSave(Event $event)
    {
        // get entity
        $entity = $event->getArgument('entity');

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
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeRemove(Event $event)
    {
        // get entity
        $entity = $event->getArgument('entity');

        $this->validRelationsWithProduct($entity->id);
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event): void
    {
        $this->removeProductFamilyAttribute($event);
    }

    /**
     * @param Event $event
     */
    public function afterUnrelate(Event $event)
    {
        if ($event->getArgument('relationName') == 'productFamilyAttributes'
            && !empty($foreign = $event->getArgument('foreign'))
            && !is_string($foreign)) {
            $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->removeCollectionByProductFamilyAttribute($foreign->get('id'));
        }
    }

    /**
     * Validation ProductFamily relations Product
     *
     * @param string $id
     *
     * @throws BadRequest
     */
    protected function validRelationsWithProduct(string $id): void
    {
        if ($this->hasProducts($id)) {
            throw new BadRequest(
                $this->getLanguage()->translate(
                    'Product Family is used in products',
                    'exceptions',
                    'ProductFamily'
                )
            );
        }
    }

    /**
     * Has Products relations ProductFamily
     *
     * @param string $id
     *
     * @return bool
     */
    protected function hasProducts(string $id): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['productFamilyId' => $id])
            ->count();

        return !empty($count);
    }

    /**
     * @param Event $event
     */
    protected function removeProductFamilyAttribute(Event $event): void
    {
        $productFamilyAttributes = $this
            ->getEntityManager()
            ->getRepository('ProductFamilyAttribute')
            ->where(['productFamilyId' => $event->getArgument('entity')->get('id')])
            ->find();
        /** @var ProductFamilyAttribute $productFamilyAttribute */
        foreach ($productFamilyAttributes as $productFamilyAttribute) {
            $this->getEntityManager()->removeEntity($productFamilyAttribute);
        }
    }
}
