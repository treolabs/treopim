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

use Dam\Entities\Asset;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class AssetRelationEntity
 * @package Pim\Listeners
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class AssetRelationEntity extends AbstractListener
{
    protected $hasMainImage = ['Product', 'Category'];

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        $assetRelation = $event->getArgument('entity');
        if (!empty($assetRelation) && $assetRelation->isNew()) {
            $this->getService('AssetRelation')
                ->updateMainImage($assetRelation->get('entityName'), $assetRelation->get('entityId'));
        }
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        $assetRelation = $event->getArgument('entity');
        if (!empty($assetRelation) && $assetRelation->isNew()) {
            $this->getService('AssetRelation')
                ->updateMainImage($assetRelation->get('entityName'), $assetRelation->get('entityId'));
        }
    }
}
