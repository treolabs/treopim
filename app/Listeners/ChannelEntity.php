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
use Espo\Core\Exceptions;
use Pim\Entities\Channel;
use Treo\Core\EventManager\Event;

/**
 * Class ChannelEntity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ChannelEntity extends AbstractEntityListener
{
    /**
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeSave(Event $event)
    {
        if (!$this->isCodeValid($event->getArgument('entity'))) {
            throw new Exceptions\BadRequest(
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
     */
    public function afterUnrelate(Event $event)
    {
        //set default value in isActive for channel after deleted link
        if(is_object($foreign = $event->getArgument('foreign'))
                && isset($foreign->getRelations()['channels']['additionalColumns']['isActive'])) {
            $dataEntity = new \StdClass();
            $dataEntity->entityName = $foreign->getEntityName();
            $dataEntity->entityId = $foreign->get('id');
            $dataEntity->value = (int)!empty($foreign->getRelations()['channels']['additionalColumns']['isActive']['default']);

            $this
                ->getService('Channel')
                ->setIsActiveEntity($event->getArgument('foreign')->get('id'), $dataEntity, true);
        }
    }
}
