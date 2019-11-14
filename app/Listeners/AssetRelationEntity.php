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
use Dam\Entities\AssetRelation;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
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
     * @throws BadRequest
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        $assetRelation = $event->getArgument('entity');
        $asset = $this->getEntityManager()->getEntity('Asset', $assetRelation->get('assetId'));

        $this->validation($assetRelation, $asset);
    }

    /**
     * @param Event $event
     * @throws Error
     */
    public function afterSave(Event $event)
    {
        $assetRelation = $event->getArgument('entity');
        $asset = $this->getEntityManager()->getEntity('Asset', $assetRelation->get('assetId'));
        if ($this->isMainGlobalRole($assetRelation, $asset)) {
            $this->clearingRoleForType($assetRelation, $asset->get('type'), 'Main');
            if ($asset->get('type') == 'Gallery Image') {
                $this->updateMainImage($assetRelation, $asset);
            }
        }
    }

    /**
     * @param Event $event
     * @throws Error
     */
    public function afterRemove(Event $event)
    {
        $assetRelation = $event->getArgument('entity');
        $asset = $this->getEntityManager()->getEntity('Asset', $assetRelation->get('assetId'));
        if ($this->isMainGlobalRole($assetRelation, $asset) && $asset->get('type') == 'Gallery Image') {
            $this->updateMainImage($assetRelation, null);
        }
    }

    /**
     * @param AssetRelation $relation
     * @param Asset $asset
     * @return bool
     * @throws BadRequest
     */
    protected function validation(AssetRelation $relation, Asset $asset): bool
    {
        $type = (string)$asset->get('type');
        $channelsId  = array_column($relation->get('channels')->toArray(), 'id');
        if ($this->isMainRole($relation) && $relation->get('scope') == 'Channel' && !empty($channelsId)) {
            //checking for the existence of channels with a role Main
            $channelsCount = count($this->getAssetRelations($relation, $type, 'Main', 'Channel', $channelsId));
            if (!empty($channelsCount)) {
                throw new BadRequest('The asset of the main role is already defined for the selected channels (all or several of them).');
            }
        }
        return true;
    }

    /**
     * @param AssetRelation $assetRelation
     * @param Asset $asset
     * @throws Error
     */
    protected function updateMainImage(AssetRelation $assetRelation, ?Asset $asset): void
    {
        if (in_array($assetRelation->get('entityName'), $this->hasMainImage)) {
            $foreign = $this->getEntityManager()
                ->getEntity($assetRelation->get('entityName'), $assetRelation->get('entityId'));
            //prepare image
            $imageId = !empty($asset) ? $asset->get('fileId') : null;
            // update main image if it needs
            if (!empty($foreign) && $imageId != $foreign->get('imageId')) {
                $foreign->set('imageId', $imageId);
                $foreign->keepAttachment = true;
                $this->getEntityManager()->saveEntity($foreign, ['skipAfterSave' => true]);
            }
        }
    }

    /**
     * @param AssetRelation $assetRelation
     * @param string $type
     * @param string $role
     */
    protected function clearingRoleForType(AssetRelation $assetRelation, string $type, string $role): void
    {
        $assetRelations = $this->getAssetRelations($assetRelation, $type, $role, 'Global');
        $sqlUpdate = '';
        foreach ($assetRelations as $relation) {
            $roles = json_decode($relation['role'], true);
            if (is_array($roles) && !is_bool($keyRole = array_search($role, $roles))) {
                unset($roles[$keyRole]);
                $roles = json_encode($roles);
                $sqlUpdate .= "UPDATE asset_relation SET role = '{$roles}' WHERE id = '{$relation['id']}';";
            }
        }
        if (!empty($sqlUpdate)) {
            $this->getEntityManager()->nativeQuery($sqlUpdate);
        }
    }

    /**
     * @param AssetRelation $relation
     * @param string $type
     * @param string $role
     * @param string $scope
     * @param array|null $channelsId
     * @return array
     */
    protected function getAssetRelations(AssetRelation $relation, string $type, string $role, string $scope, array $channelsId = null): array
    {
        $sql = "SELECT ar.id, ar.role
                    FROM asset_relation ar
                         LEFT JOIN asset a ON ar.asset_id = a.id
                         LEFT JOIN asset_relation_channel arc ON arc.asset_relation_id = ar.id AND arc.deleted = 0
                    WHERE ar.entity_id = '{$relation->get('entityId')}'
                        AND ar.entity_name = '{$relation->get('entityName')}'
                        AND ar.role LIKE '%\"{$role}\"%'
                        AND a.type = '{$type}'
                        AND ar.scope = '{$scope}'
                        AND ar.id <> '{$relation->get('id')}'
                        AND ar.deleted = '0'";
        if (!empty($channelsId)) {
            $channelsId = "'" . implode("','", $channelsId) . "'";
            $sql .= " AND arc.channel_id IN ({$channelsId})";
        }
        return $this
            ->getEntityManager()
            ->nativeQuery($sql)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param AssetRelation $assetRelation
     * @param $asset
     * @return bool
     */
    protected function isMainGlobalRole(AssetRelation $assetRelation, $asset): bool
    {
        return
            !empty($asset)
            && $assetRelation->get('scope') == 'Global'
            && $this->isMainRole($assetRelation);
    }

    /**
     * @param AssetRelation $assetRelation
     * @return bool
     */
    protected function isMainRole(AssetRelation $assetRelation): bool
    {
        return in_array('Main', (array)$assetRelation->get('role'));
    }
}
