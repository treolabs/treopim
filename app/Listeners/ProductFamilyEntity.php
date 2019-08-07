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

use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;
use Espo\Core\Exceptions\BadRequest;

/**
 * Class ProductFamilyEntity
 *
 * @package Pim\Listeners
 * @author m.kokhanskyi@treolabs.com
 */
class ProductFamilyEntity extends AbstractListener
{
    /**
     * Before action delete
     *
     * @param Event $event
     */
    public function beforeRemove(Event $event)
    {
        $id = $event->getArgument('entity')->id;

        $this->validRelationsWithProduct($id);
    }

    /**
     * Validation ProductFamily relations Product
     *
     * @param string $id
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
}
