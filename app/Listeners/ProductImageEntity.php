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

namespace Pim\Listeners;

use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;

/**
 * Class ProductImageEntity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductImageEntity extends AbstractImageListener
{
    /**
     * @var string
     */
    protected $entityName = 'ProductImage';

    /**
     * Return condition for query
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function getCondition(Entity $entity)
    {
        return ['productId' => $entity->get('productId')];
    }

    /**
     * @param Event $event
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    public function afterRelate(Event $event)
    {
        if ($event->getArgument('relationName') == 'products') {
            $this
                ->getEntityManager()
                ->getEntity('Product', $event->getArgument('relationName'));
            $this
                ->createService('ProductImage')
                ->sortingImage($event->getArgument('foreign'));
        }
    }
}
