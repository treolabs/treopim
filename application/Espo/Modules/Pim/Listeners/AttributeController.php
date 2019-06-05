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

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class AttributeController
 *
 * @author r.ratsun@treolabs.com
 */
class AttributeController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeActionDelete(Event $event)
    {
        // get data
        $data = $event->getArguments();

        if (empty($data['data']->force) && !empty($data['params']['id'])) {
            if ($this->hasProduct($data['params']['id'])) {
                throw new BadRequest(
                    $this->getLanguage()->translate(
                        'Attribute is used in products. Please, update products first',
                        'exceptions',
                        'Attribute'
                    )
                );
            }
        }
    }

    /**
     * @param Event $event
     */
    public function beforeActionMassDelete(Event $event)
    {
        // get data
        $data = $event->getArguments();

        if (empty($data['data']->force)) {
            throw new BadRequest(
                $this->getLanguage()->translate(
                    'Attribute is used in product families. Please, update product families first',
                    'exceptions',
                    'Attribute'
                )
            );
        }
    }

    /**
     * Is attribute used in products
     *
     * @param string $attributeId
     *
     * @return bool
     */
    protected function hasProduct(string $attributeId): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['attributeId' => $attributeId])
            ->count();

        return !empty($count);
    }
}
