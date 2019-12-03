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
 * Migration class for version 3.12.0
 *
 * @author m.kokhanskyi@treolabs.com
 */
class V3Dot12Dot0 extends AbstractMigration
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
     * @var array
     */
    protected $migratedAttachment = [];

    /**
     * @inheritdoc
     * @throws Error
     */
    public function up(): void
    {
        (new Auth($this->getContainer()))->useNoAuth();

        $attachments = $this->getAttachmentsForUp();
        $pimImageChannels = $this->getPimImageChannels();
        //for storing assetId with Channels
        $assetIdsWithChannel = '';
        $repAttachment = $this->getEntityManager()->getRepository('Attachment');
        foreach ($attachments as $key => $attachment) {
            $id = $attachment['id'];
            $foreignName = !empty($attachment['product_id']) ? 'Product' : 'Category';
            $foreignId = !empty($attachment['product_id']) ? $attachment['product_id'] : $attachment['category_id'];

            if (empty($this->migratedAttachment[$id])) {
                $attachmentEntity = $this->getEntityManager()->getEntity('Attachment', $id);
                $attachmentEntity->set('relatedType', $foreignName);
                $pathFile = $repAttachment->getFilePath($attachmentEntity);
                if (empty($pathFile) || !file_exists($pathFile)) {
                    unset($attachments[$key]);
                    continue;
                }
                $this->updateAttachment($id, $attachment, $pathFile);
                //creating asset
                $foreign = !empty($attachment['product_id']) ? 'products' : 'categories';
                try {
                    $idAsset = $this->createAsset($id, $attachment['name'], $foreign, $foreignId);
                } catch (\Exception $e) {
                    $this->setLog($id, $e);
                    continue;
                }
                if (!empty($pimImageChannels[$attachment['pimImage_id']])) {
                    $assetIdsWithChannel .= "'{$idAsset}',";
                }
                $this->migratedAttachment[$id] = $idAsset;
            } else {
                $scope = $attachment['scope'] == 'Channel' ? $attachment['scope'] : null;
                if (!empty($pimImageChannels[$attachment['pimImage_id']])) {
                    $assetIdsWithChannel .= "'{$this->migratedAttachment[$id]}',";
                }
                $this->getEntityManager()
                    ->nativeQuery(
                        "
                    INSERT INTO asset_relation
                    (id,
                     name,
                     entity_name,
                     entity_id,
                     asset_id,
                     sort_order,
                     created_by_id,
                     assigned_user_id,
                     scope)
                VALUES (SUBSTR(MD5('{$attachment['pimImage_id']}_{$foreignId}'), 16),
                        '{$attachment['name']}',
                        '{$foreignName}',
                        '{$foreignId}',
                        '{$this->migratedAttachment[$id]}',
                        '{$attachment['sort_order']}',
                        'system',
                        'system',
                        '{$scope}'
                        )"
                    );
            }
        }

        //remove last symbol(coma)
        $assetIdsWithChannel = substr($assetIdsWithChannel, 0, -1);
        if (!empty($assetIdsWithChannel)) {
            //update scope
            $this->getEntityManager()
                ->nativeQuery(
                    "UPDATE
                                        asset_relation ar
                                            RIGHT JOIN asset a ON a.id = ar.asset_id
                                            RIGHT JOIN pim_image pi ON 
                                                a.file_id = pi.image_id 
                                                AND pi.product_id IS NOT NULL 
                                                AND pi.product_id != ''
                                                AND ar.entity_id = pi.product_id
                                    SET ar.scope = 'Channel'
                                    WHERE ar.scope = 'Global' 
                                        AND pi.scope = 'Channel' 
                                        AND ar.asset_id IN ({$assetIdsWithChannel})"
                );
            $this->getEntityManager()
                ->nativeQuery(
                    "UPDATE
                                        asset_relation ar
                                            RIGHT JOIN asset a ON a.id = ar.asset_id
                                            RIGHT JOIN pim_image pi ON 
                                                    a.file_id = pi.image_id 
                                                    AND pi.category_id IS NOT NULL 
                                                    AND pi.category_id != ''
                                                    AND ar.entity_id = pi.category_id
                                    SET ar.scope = 'Channel'
                                    WHERE  ar.scope = 'Global' 
                                        AND pi.scope = 'Channel' 
                                        AND ar.asset_id IN ({$assetIdsWithChannel})"
                );
            //create link asset_relation_channel
            $this->insertAssetRelationChannel('Product', $assetIdsWithChannel);
            $this->insertAssetRelationChannel('Category', $assetIdsWithChannel);
        }

        //update sort order
        $this->getEntityManager()
            ->nativeQuery("UPDATE asset_relation SET scope = 'Global' WHERE scope IS NULL OR scope = '';");

        $this->updateMainImageUp('Product');
        $this->updateMainImageUp('Category');

        $this->getEntityManager()
            ->nativeQuery(
                '
                        DROP TABLE pim_image;
                        DROP TABLE pim_image_channel;'
            );
    }

    /**
     * @param $id
     * @param $attachment
     * @param $pathFile
     */
    protected function updateAttachment($id, $attachment, $pathFile)
    {
        $dataUpdate = [];
        $dataUpdate['hash_md5'] = hash_file('md5', $pathFile);
        $dataUpdate['related_type'] = 'Asset';
        $dataUpdate['parent_type'] = 'Asset';
        if (empty($attachment['tmp_path'])) {
            $dataUpdate['tmp_path'] = $pathFile;
        }
        $this->updateById('attachment', $dataUpdate, $id);
        $this->executeUpdate($this->sqlUpdate);
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
        (new Auth($this->getContainer()))->useNoAuth();

        $attachments = $this
            ->getEntityManager()
            ->nativeQuery(
                "SELECT att.id, att.name, a.private, att.storage_file_path, a.id as asset_id 
                                FROM asset AS a
                                    RIGHT JOIN asset_relation AS ar 
                                        ON a.id = ar.asset_id 
                                            AND ar.deleted = 0 
                                            AND ar.entity_name IN ('Product', 'Category')
                                    RIGHT JOIN attachment AS att 
                                        ON a.file_id = att.id AND att.deleted = 0
                                WHERE a.type = 'Gallery Image' AND a.deleted = 0 AND att.storage = 'DAMUploadDir'"
            )
            ->fetchAll(PDO::FETCH_ASSOC);
        $assetIds = [];

        foreach ($attachments as $k => $attachment) {
            $oldPath = ($attachment['private'] == '1' ? 'data/dam/private/' : 'data/dam/public/')
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
            $this->insertPimImage('Product', $assetIds);
            $this->insertPimImage('Category', $assetIds);

            //insert pim_image_channel
            $this->insertPimImageChannel('Product', $assetIds);
            $this->insertPimImageChannel('Category', $assetIds);

            $this->updateMainImageDown('Product');
            $this->updateMainImageDown('Category');

            $this
                ->getEntityManager()
                ->nativeQuery("DELETE FROM asset WHERE id IN ({$assetIds});");

            $this
                ->getEntityManager()
                ->nativeQuery(
                    "DELETE 
                                    FROM asset_relation_channel 
                                    WHERE asset_relation_id 
                                            IN (SELECT id FROM asset_relation WHERE asset_id IN ({$assetIds}))"
                );
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
     * @param string $entityName
     * @param string $assetIds
     */
    protected function insertPimImage(string $entityName, string $assetIds)
    {
        if ($entityName == 'Product') {
            $select = " ar.entity_id AS product_id,
                        null AS category_id";
        } elseif ($entityName == 'Category') {
            $select = " null AS product_id,
                        ar.entity_id AS category_id";
        } else {
            return;
        }
        $sql = "                
                INSERT INTO pim_image
                (id, name, image_id, deleted, sort_order, scope, assigned_user_id, product_id, category_id)
                SELECT
                    SUBSTR(MD5(CONCAT(ar.id, RAND())), 16) as id,
                    a.name,
                    a.file_id AS image_id,
                    a.deleted,
                    CASE
                       WHEN ar.sort_order IS NOT NULL THEN ar.sort_order
                       ELSE (SELECT @n := @n + CASE 
                                                WHEN max(ar1.sort_order) is not null 
                                                THEN max(ar1.sort_order) 
                                                ELSE 1 END
                             FROM asset_relation AS ar1,
                                  (SELECT @n := 1) s
                             WHERE ar1.entity_id = ar.entity_id)
                       END AS sort_order,
                    ar.scope,
                    ar.assigned_user_id,
                    {$select}
                FROM asset AS a
                         RIGHT JOIN asset_relation AS ar
                                    ON a.id = ar.asset_id
                                        AND ar.deleted = 0
                                        AND ar.entity_name = '{$entityName}'
                         RIGHT JOIN attachment AS att ON a.file_id = att.id AND att.deleted = 0
                WHERE a.type = 'Gallery Image'
                    AND a.deleted = 0
                  AND a.id IN ({$assetIds});";

        $this->getEntityManager()->nativeQuery($sql);
    }

    /**
     * @param string $entityName
     */
    protected function updateMainImageDown(string $entityName)
    {
        if ($entityName == 'Product') {
            $where = ' AND pi.product_id IS NOT NULL AND pi.product_id != \'\'';
            $wherePimImage = ' AND pim_image.product_id IS NOT NULL AND pim_image.product_id != \'\'';
            $fieldLink = 'product_id';
        } elseif ($entityName == 'Category') {
            $where = ' AND pi.category_id IS NOT NULL AND pi.category_id != \'\'';
            $wherePimImage = ' AND pim_image.category_id IS NOT NULL AND pim_image.category_id != \'\'';
            $fieldLink = 'category_id';
        } else {
            return;
        }

        $table = lcfirst($entityName);
        $sql = "UPDATE {$table} p
                RIGHT JOIN (SELECT pim_image.$fieldLink, min(pim_image.sort_order) as sort
                        FROM pim_image
                        WHERE pim_image.deleted = 0 {$wherePimImage}
                        GROUP BY pim_image.$fieldLink
                    ) as sort ON sort.$fieldLink = p.id
                LEFT JOIN pim_image pi ON pi.$fieldLink = sort.$fieldLink
                              AND pi.sort_order = sort.sort
                              {$where}
                SET p.image_id = pi.image_id
                WHERE p.deleted = 0;";

        $this->getEntityManager()->nativeQuery($sql);
    }

    /**
     * @param string $entityName
     */
    protected function updateMainImageUp(string $entityName)
    {
        if ($entityName == 'Product') {
            $where = ' AND pi.product_id IS NOT NULL AND pi.product_id != \'\'';
        } elseif ($entityName == 'Category') {
            $where = ' AND pi.category_id IS NOT NULL AND pi.category_id != \'\'';
        } else {
            return;
        }

        $table = lcfirst($entityName);

        $this->getEntityManager()
            ->nativeQuery(
                "
                UPDATE asset_relation ar
                    RIGHT JOIN asset a ON a.id = ar.asset_id
                    RIGHT JOIN pim_image pi ON a.file_id = pi.image_id {$where}
                SET ar.sort_order = pi.sort_order
                WHERE ar.deleted = 0
                  AND ar.entity_name = '{$entityName}';
                  
                UPDATE {$table} p
                       LEFT JOIN (SELECT ar.entity_id, min(ar.sort_order) as sort
                                   FROM asset_relation ar
                                   WHERE ar.scope = 'Global'
                                     AND ar.entity_name = '{$entityName}'
                                     AND ar.deleted = 0
                                   GROUP BY ar.entity_id
                            ) as sort ON sort.entity_id = p.id
                            LEFT JOIN asset_relation ar ON ar.entity_id = sort.entity_id AND ar.sort_order = sort.sort
                            LEFT JOIN asset a ON ar.asset_id = a.id
                        SET p.image_id = a.file_id, ar.role = '[\"Main\"]'
                        WHERE p.deleted = 0;
                  "
            );
    }

    /**
     * @param string $entityName
     * @param string $assetIdsWithChannel
     */
    protected function insertAssetRelationChannel(string $entityName, string $assetIdsWithChannel)
    {
        $where = '';
        if ($entityName == 'Product') {
            $where = ' pi.product_id IS NOT NULL AND pi.product_id != \'\'';
        } elseif ($entityName == 'Category') {
            $where = ' pi.category_id IS NOT NULL AND pi.category_id != \'\'';
        } else {
            return;
        }

        $this->getEntityManager()
            ->nativeQuery(
                "
             INSERT INTO asset_relation_channel (channel_id, asset_relation_id)
                SELECT  pic.channel_id, ar.id
                FROM pim_image AS pi
                    RIGHT JOIN pim_image_channel AS pic ON pi.id = pic.pim_image_id AND pic.deleted = 0
                    LEFT JOIN asset ON asset.file_id = pi.image_id AND asset.deleted = 0
                    LEFT JOIN asset_relation AS ar
                       ON ar.entity_name = '{$entityName}' AND ar.asset_id = asset.id
                          AND ar.deleted = 0 AND ar.scope = 'Channel'
                WHERE {$where}
                  AND pi.deleted = 0
                  AND pi.scope = 'Channel'
                  AND ar.asset_id IN ({$assetIdsWithChannel});"
            );
    }

    /**
     * @param string $entityName
     * @param string $assetIds
     */
    protected function insertPimImageChannel(string $entityName, string $assetIds)
    {
        $where = '';
        if ($entityName == 'Product') {
            $where = ' pi.product_id IS NOT NULL AND pi.product_id != \'\'';
        } elseif ($entityName == 'Category') {
            $where = ' pi.category_id IS NOT NULL AND pi.category_id != \'\'';
        } else {
            return;
        }
        $this->getEntityManager()
            ->nativeQuery(
                "INSERT INTO pim_image_channel (channel_id, pim_image_id)
                                    SELECT arc.channel_id, pi.id AS pim_image_id
                                    FROM asset AS a
                                             RIGHT JOIN asset_relation ar
                                                        ON ar.asset_id = a.id
                                                            AND ar.deleted = 0
                                                            AND ar.scope = 'Channel'
                                                            AND ar.entity_name = '{$entityName}'
                                             RIGHT JOIN asset_relation_channel arc
                                                        ON ar.id = arc.asset_relation_id
                                                            AND arc.deleted = 0
                                             LEFT JOIN pim_image pi
                                                       ON pi.image_id = a.file_id
                                                           AND pi.deleted = 0
                                                           AND pi.scope = 'Channel'
                                    WHERE {$where} 
                                        AND a.deleted = 0 
                                        AND a.type = 'Gallery Image' 
                                        AND a.id IN ({$assetIds});"
            );
    }

    /**
     * @param string $fileId
     * @param string $fileName
     * @param string $foreign
     * @param string $foreignId
     *
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
     * @param array  $values
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
     *
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
                $collection = $this->getEntityManager()->getEntity('Collection');
                $collection->set('isActive', true);
                $collection->set('name', 'PimCollection');
                $collection->set('code', 'pimcollection');
                $this->collectionId = $this->getEntityManager()->saveEntity($collection);
            }
        }

        return $this->collectionId;
    }

    /**
     * @param $name
     *
     * @return Record
     */
    protected function getService($name): Record
    {
        return $this->getContainer()->get("serviceFactory")->create($name);
    }

    /**
     * @param $type
     *
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

    protected function getAttachmentsForUp(): array
    {
        return $this
            ->getEntityManager()
            ->nativeQuery(
                'SELECT a.id,
                                       a.storage_file_path,
                                       a.storage,
                                       a.name,
                                       a.tmp_path,
                                       pi.product_id,
                                       pi.category_id,
                                       pi.id as pimImage_id,
                                       pi.scope,
                                       pi.sort_order
                                FROM attachment a
                                         RIGHT JOIN pim_image AS pi ON pi.image_id = a.id AND pi.deleted = 0 
                                WHERE a.deleted = 0'
            )
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    protected function getPimImageChannels(): array
    {
        return $this
            ->getEntityManager()
            ->nativeQuery('SELECT pim_image_id, channel_id FROM pim_image_channel WHERE deleted = 0')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
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

    /**
     * @param string     $id
     * @param \Exception $e
     */
    protected function setLog(string $id, \Exception $e): void
    {
        $GLOBALS['log']->error('Error migration pimImage to Asset. AttachmentId: ' . $id . ';' . $e->getMessage());
    }
}
