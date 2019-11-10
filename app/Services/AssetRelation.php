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

namespace Pim\Services;

use Dam\Services\AssetRelation as AssetRelationDam;
use Espo\Core\Exceptions\Error;
use PDO;

/**
 * Class AssetRelation
 * @package Pim\Services
 *
 * @author m.kokhanskyi <m.kokhanskyi@treolabs.com>
 */
class AssetRelation extends AssetRelationDam
{
    protected $hasMainImage = ['Product', 'Category'];

    /**
     * @param string $entityName
     * @param string $entityId
     */
    public function updateMainImage(string $entityName, string $entityId): void
    {
        if (in_array($entityName, $this->hasMainImage)) {
            $foreign = $this->getEntityManager()->getEntity($entityName, $entityId);
            // find first image
            $imageId = $this->getEntityManager()
                ->nativeQuery(
                    "SELECT a.file_id
                            FROM asset a
                                RIGHT JOIN asset_relation ar ON ar.asset_id = a.id AND ar.deleted = 0
                            WHERE 
                            a.deleted = 0 
                            AND type = 'Gallery Image'
                            AND ar.entity_name = '{$entityName}' 
                            AND ar.entity_id = '{$entityId}'
                            ORDER BY ar.sort_order, ar.created_at")
                ->fetch(PDO::FETCH_COLUMN);
            //prepare image
            $imageId = !empty($imageId) ? $imageId : null;
            // update main image if it needs
            if (!empty($foreign) && $imageId != $foreign->get('imageId')) {
                $foreign->set('imageId', $imageId);
                $foreign->keepAttachment = true;
                $this->getEntityManager()->saveEntity($foreign, ['skipAfterSave' => true]);
            }
        }
    }

    /**
     * @param $assetId
     * @return string|null
     * @throws Error
     */
    protected function getAttachmentId($assetId): ?string
    {
        $asset = $this->getEntityManager()->getEntity('Asset', $assetId);
        return !empty($asset) ? $asset->get('fileId') : null;
    }
}
