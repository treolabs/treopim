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

namespace Pim\Migrations;

use Dam\Core\FileStorage\DAMUploadDir;
use Dam\Entities\Collection;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\File\Manager;
use Espo\Services\Record;
use PDO;
use stdClass;
use Treo\Core\FilePathBuilder;
use Treo\Core\FileStorage\Storages\UploadDir;
use Treo\Core\Migration\AbstractMigration;
use Treo\Core\Utils\Auth;

/**
 * Migration class for version 3.11.13
 *
 * @author m.kokhanskyi@treolabs.com
 */
class V3Dot11Dot13 extends AbstractMigration
{
    /**
     * Max execute queries at a time
     */
    const MAX_QUERY = 3000;
    /**
     * @var array
     */
    protected $sqlUpdate = [];
    /**
     * @var string|null
     */
    protected $collectionId = null;

    /**
     * @inheritdoc
     * @throws Error
     * @throws Forbidden
     */
    public function up(): void
    {
        (new Auth($this->getContainer()))->useNoAuth();

        $attachments = $this
            ->getEntityManager()
            ->nativeQuery('SELECT a.id,
                                       a.storage_file_path,
                                       a.storage,
                                       a.name,
                                       a.tmp_path,
                                       pi.product_id,
                                       pi.category_id,
                                       pi.id as pimImage_id,
                                       pi.sort_order
                                FROM attachment a
                                         RIGHT JOIN pim_image AS pi ON pi.image_id = a.id AND pi.deleted = 0 
                                WHERE a.deleted = 0 AND a.storage = \'UploadDir\'')
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($attachments as $k => $attachment) {
            $pathFile = UploadDir::BASE_PATH . $attachment['storage_file_path'] . '/' . $attachment['name'];
            if (!file_exists($pathFile)) {
                unset($attachments[$k]);
                continue;
            }
            $dataUpdate = [];
            $dataUpdate['hash_md5'] = hash_file('md5', $pathFile);
            $dataUpdate['related_type'] = 'Asset';
            if (empty($attachment['tmp_path'])) {
                $dataUpdate['tmp_path'] = $pathFile;
            }
            $this->updateById('attachment', $dataUpdate, $attachment['id']);
        }

        $this->executeUpdate($this->sqlUpdate);

        $pimImageChannels = $this
            ->getEntityManager()
            ->nativeQuery('SELECT pim_image_id, channel_id FROM pim_image_channel WHERE deleted = 0')
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        //for storing assetId with Channels
        $assetIdsWithChannel = '';
        //creating asset
        foreach ($attachments as $attachment) {
            $foreign = !empty($attachment['product_id']) ? 'products' : 'categories';
            $foreignId = !empty($attachment['product_id']) ? $attachment['product_id'] : $attachment['category_id'];
            $idAsset = $this->createAsset($attachment['id'], $attachment['name'], $foreign, $foreignId);
            if (!empty($pimImageChannels[$attachment['pimImage_id']])) {
                $assetIdsWithChannel .= "'{$idAsset}',";
            }
        }
        //remove last symbol(coma)
        $assetIdsWithChannel = substr($assetIdsWithChannel, 0, -1);
        if (!empty($assetIdsWithChannel)) {
            //update scope
            $this->getEntityManager()
                ->nativeQuery("UPDATE asset_relation SET scope = 'Channel' WHERE asset_id IN ({$assetIdsWithChannel})");
            //create link asset_relation_channel
            $this->getEntityManager()
                ->nativeQuery(" 
                INSERT INTO asset_relation_channel (channel_id, asset_relation_id)
                SELECT pic.channel_id, ar.id FROM pim_image AS pi
                    RIGHT JOIN attachment AS a ON a.id = pi.image_id AND pi.deleted = 0
                    RIGHT JOIN pim_image_channel AS pic ON pi.id = pic.pim_image_id AND pic.deleted = 0
                    LEFT JOIN asset ON asset.file_id = a.id AND asset.deleted = 0
                    LEFT JOIN asset_relation AS ar ON ar.asset_id = asset.id AND ar.deleted = 0
                WHERE pi.deleted = 0 AND pi.scope = 'Channel' AND ar.asset_id IN ({$assetIdsWithChannel})");
        }
        //update sort order
        $this->getEntityManager()
            ->nativeQuery("
                    UPDATE asset_relation ar
                        RIGHT JOIN asset a ON ar.asset_id = a.id AND a.deleted = 0
                        RIGHT JOIN attachment AS att ON att.id = a.file_id AND a.deleted = 0
                        RIGHT JOIN pim_image pi ON pi.image_id = att.id AND pi.deleted = 0
                    SET ar.sort_order = pi.sort_order
                    WHERE ar.deleted = 0 
                        AND ar.entity_name IN ('Product', 'Category')");

        $this->getEntityManager()
            ->nativeQuery('
                        DROP TABLE pim_image; 
                        DROP TABLE pim_image_channel;');
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
        (new Auth($this->getContainer()))->useNoAuth();

        $attachments = $this
            ->getEntityManager()
            ->nativeQuery("SELECT att.id, att.name, a.private, att.storage_file_path, a.id as asset_id 
                                FROM asset AS a
                                    RIGHT JOIN asset_relation AS ar 
                                        ON a.id = ar.asset_id 
                                            AND ar.deleted = 0 
                                            AND ar.entity_name IN ('Product', 'Category')
                                    RIGHT JOIN attachment AS att 
                                        ON a.file_id = att.id AND att.deleted = 0
                                WHERE a.type = 'Gallery Image' AND a.deleted = 0 AND att.storage = 'DAMUploadDir'")
            ->fetchAll(PDO::FETCH_ASSOC);
        $assetIds = [];

        foreach ($attachments as $k => $attachment) {
            $oldPath = ($attachment['private'] == '1' ? DAMUploadDir::PRIVATE_PATH : DAMUploadDir::PUBLIC_PATH)
                . "master/" . $attachment['storage_file_path'] . "/" . $attachment['name'];

            if (file_exists($oldPath)) {
                $attachmentUpdate = [];
                $storagePath = $this->getFilePath(FilePathBuilder::UPLOAD);
                $newPath = UploadDir::BASE_PATH . $storagePath . "/" . $attachment['name'];

                if ($this->getFileManager()->move($oldPath, $newPath, false)) {
                    $attachmentUpdate['storage_file_path'] = $storagePath;
                    $attachmentUpdate['storage'] = 'UploadDir';
                    $attachmentUpdate['related_type'] = 'PimImage';
                    $attachmentUpdate['related_id'] = null;
                    $this->updateById('attachment', $attachmentUpdate, $attachment['id']);
                    $assetIds[] = $attachment['asset_id'];
                }
            }
            if (empty($attachmentUpdate)) {
                unset($attachments[$k]);
            }
        }
        $this->executeUpdate($this->sqlUpdate);

        if (!empty($assetIds)) {
            $assetIds = "'" . implode("','", $assetIds) . "'";
            //insert pimImages
            $this->getEntityManager()
                ->nativeQuery("
                            INSERT INTO pim_image 
                            (id, name, image_id, deleted, sort_order, scope, assigned_user_id, product_id, category_id)
                            SELECT 
                                SUBSTR(MD5(a.id), 16) as id,
                                 a.name, 
                                 a.file_id AS image_id, 
                                 a.deleted, ar.sort_order, 
                                 ar.scope, 
                                 ar.assigned_user_id,
                                   CASE
                                       WHEN ar.entity_name = 'Product' THEN ar.entity_id
                                       ELSE NULL
                                   END AS product_id,
                                   CASE
                                       WHEN ar.entity_name = 'Category' THEN ar.entity_id
                                       ELSE NULL
                                   END AS category_id
                            FROM asset AS a
                                     RIGHT JOIN asset_relation AS ar
                                                ON a.id = ar.asset_id 
                                                    AND ar.deleted = 0 
                                                    AND ar.entity_name IN ('Product', 'Category')
                                     RIGHT JOIN attachment AS att ON a.file_id = att.id AND att.deleted = 0
                            WHERE a.type = 'Gallery Image'
                              AND a.deleted = 0 
                              AND a.id IN ({$assetIds})");
            //insert pim_image_channel
            $this->getEntityManager()
                ->nativeQuery("INSERT INTO pim_image_channel (channel_id, pim_image_id)
                                    SELECT arc.channel_id, pi.id AS pim_image_id
                                    FROM asset AS a
                                             RIGHT JOIN attachment AS att ON att.id = a.file_id AND a.deleted = 0
                                             RIGHT JOIN asset_relation ar 
                                                ON ar.asset_id = a.id 
                                                    AND ar.deleted = 0 
                                                    AND ar.scope = 'Channel' 
                                                    AND ar.entity_name IN ('Product', 'Category')
                                             RIGHT JOIN asset_relation_channel arc 
                                                ON ar.id = arc.asset_relation_id 
                                                    AND arc.deleted = 0
                                             LEFT JOIN pim_image pi 
                                                ON pi.image_id = att.id 
                                                    AND pi.deleted = 0 
                                                    AND pi.scope = 'Channel'
                                    WHERE a.deleted = 0 AND a.id IN ({$assetIds});");

            $this
                ->getEntityManager()
                ->nativeQuery("DELETE FROM asset WHERE id IN ({$assetIds});");
            $this
                ->getEntityManager()
                ->nativeQuery("DELETE FROM asset_relation WHERE asset_id IN ({$assetIds})");

            $renditions = $this
                ->getEntityManager()
                ->nativeQuery("SELECT id FROM rendition WHERE asset_id IN ({$assetIds})")
                ->fetchAll(PDO::FETCH_COLUMN);

            $renditions = "'" . implode("','", $renditions) . "'";

            $this
                ->getEntityManager()
                ->nativeQuery("DELETE FROM rendition WHERE asset_id IN ({$assetIds})");

            $sql = "DELETE FROM asset_meta_data WHERE asset_id IN ({$assetIds})";
            if (!empty($renditions)) {
                $sql .= " OR rendition_id IN ({$renditions})";
            }
            $this
                ->getEntityManager()
                ->nativeQuery($sql);
        }
    }

    /**
     * @param string $fileId
     * @param string $fileName
     * @param string $foreign
     * @param string $foreignId
     * @return string
     * @throws Error
     * @throws Forbidden
     */
    protected function createAsset(string $fileId, string $fileName, string $foreign, string $foreignId): string
    {
        $asset = new StdClass();
        $asset->type = 'Gallery Image';
        $asset->privat = true;
        $asset->fileId = $fileId;
        $asset->fileName = $fileName;
        $asset->name = explode('.', $fileName)[0];
        $asset->nameOfFile = $asset->name;
        $asset->code = md5((string)microtime());
        $asset->collectionId = $this->getCollectionAsset();
        $asset->{$foreign . 'Ids'} = [$foreignId];
        $assetEntity = $this->getService('Asset')->createEntity($asset);

        return $assetEntity->get('id');
    }

    /**
     * @param string $table
     * @param array $values
     * @param string $id
     */
    protected function updateById(string $table, array $values, string $id): void
    {
        $setValues = [];
        foreach ($values as $field => $value) {
            $setValues[] = "{$field} = '{$value}'";
        }
        if (!empty($setValues) && !empty($id)) {
            $this->sqlUpdate[] = 'UPDATE ' . $table . ' SET ' . implode(',', $setValues) . " WHERE id = '{$id}'";
        }
        if (count($this->sqlUpdate) >= self::MAX_QUERY) {
            $this->executeUpdate($this->sqlUpdate);
        }
    }

    /**
     * Execute Sql-Update for Attachments
     * @param array $queries
     */
    protected function executeUpdate(array $queries): void
    {
        if (!empty($queries)) {
            $this->getEntityManager()->nativeQuery(implode(';', $queries));
        }
        $this->sqlUpdate = [];
    }

    /**
     * @return string
     * @throws Error
     */
    protected function getCollectionAsset(): string
    {
        if (empty($this->collectionId)) {
            $this->collectionId = $this->findCollection();
            if (empty($this->collectionId)) {
                /** @var Collection $collection */
                $collection = $this->getEntityManager()->getEntity('Collection');
                $collection->set('isActive', true);
                $collection->set('name', 'PimCollection');
                $collection->set('code', 'pimcollection' . time());
                $this->collectionId = $this->getEntityManager()->saveEntity($collection);
            }
        }

        return $this->collectionId;
    }

    /**
     * @param $name
     * @return Record
     */
    protected function getService($name): Record
    {
        return $this->getContainer()->get("serviceFactory")->create($name);
    }

    /**
     * @param $type
     * @return string
     */
    protected function getFilePath($type): string
    {
        return $this->getContainer()->get('filePathBuilder')->createPath($type);
    }

    /**
     * Get file manager
     *
     * @return Manager
     */
    protected function getFileManager()
    {
        return $this->getContainer()->get('fileManager');
    }

    /**
     * @return string|null
     */
    protected function findCollection(): ?string
    {
        /** @var Collection $collection */
        $collection = $this
            ->getEntityManager()
            ->getRepository('Collection')
            ->select(['id'])
            ->where(['code' => 'pimcollection'])
            ->findOne();

        return !empty($collection) ? $collection->get('id') : null;
    }
}
