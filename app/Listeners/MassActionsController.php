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

use Espo\Core\Exceptions\BadRequest;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class StreamController
 *
 * @author m.kokhanskyi@treolabs.com
 */
class MassActionsController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeActionMassDelete(Event $event)
    {
        // get data
        $data = $event->getArguments();

        $isAtrtribute = !empty($data['params']['scope']) && $data['params']['scope'] == 'Attribute';
        if ($isAtrtribute && is_array($data['data']->ids)) {
            $this->validationAttribute($data['data']->ids);
        }
    }

    /**
     * @param array $ids
     *
     * @throws BadRequest
     */
    protected function validationAttributes(array $ids): void
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['attributeId' => $ids])
            ->count();

        if (!empty($count)) {
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
