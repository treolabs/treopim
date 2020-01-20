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
use Treo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * Class AssetEntity
 * @package Pim\Listeners
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class AssetEntity extends AbstractListener
{
    /** @var array */
    protected $hasMainImage = ['Product', 'Category'];

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event): void
    {
        $fileId = $event->getArgument('entity')->get('fileId');
        foreach ($this->hasMainImage as $entity) {
            $table = Util::toCamelCase($entity);
            $this
                ->getEntityManager()
                ->nativeQuery('UPDATE '. $table .' SET image_id = null WHERE image_id = :id', ['id' => $fileId]);
        }
    }
}
