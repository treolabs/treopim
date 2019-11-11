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

use Espo\Core\Exceptions\Error;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class AssetRelationEntity
 * @package Pim\Listeners
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class AssetRelationController extends AbstractListener
{
    protected $hasMainImage = ['Product', 'Category'];

    /**
     * @param Event $event
     */
    public function afterActionSortOrder(Event $event)
    {
        $entityName = $event->getArgument('params')['entity_name'];
        $entityId = $event->getArgument('params')['entity_id'];

        $this->getService('AssetRelation')->updateMainImage($entityName, $entityId);
    }
}
