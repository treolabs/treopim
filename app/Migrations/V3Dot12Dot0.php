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

use DamCommon\Services\MigrationPimImage;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\EntityManager;
use Espo\Core\Utils\File\Manager;
use PDO;
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

    public const PATH_ENTITY_DEFS_PIM_IMAGE = 'custom/Espo/Custom/Resources/metadata/entityDefs/PimImage.json';
    public const PATH_SCOPE_PIM_IMAGE = 'custom/Espo/Custom/Resources/metadata/scopes/PimImage.json';

    /**
     * @inheritdoc
     * @throws Error
     */
    public function up(): void
    {
        (new Auth($this->getContainer()))->useNoAuth();
        $config = $this->getContainer()->get('config');
        if ($config->get('pimAndDamInstalled') !== true) {
            $this->changeCleanup();
            //set flag about installed Pim and Image
            $config->set('pimAndDamInstalled', false);
        }

        if (!$this->getContainer()->get('metadata')->isModuleInstalled('Dam')) {
            $this->createCustomPimImage();
            $this->sendNotification();
        } elseif (!empty($this->getContainer()->get('metadata')->get('entityDefs.Product.links.assets'))) {
            //migration pimImage
            $migrationPimImage = new MigrationPimImage();
            $migrationPimImage->setContainer($this->getContainer());
            $migrationPimImage->run();

            //set flag about installed Pim and Image
            $config->set('pimAndDamInstalled', true);

            $this->removeCustomPimImage();
        }
        $config->save();
    }

    /**
     * @throws \Exception
     */
    public function changeCleanup()
    {
        $jobs = $this
            ->getEntityManager()
            ->getRepository('Job')
            ->where(['name' => 'Cleanup', 'status' => 'Pending'])
            ->find();

        foreach ($jobs as $job) {
            $time = new \DateTime($job->get('executeTime'));
            $time->modify('+30 day');
            $job->set('executeTime', $time->format('Y-m-d H:i:s'));
            $this->getEntityManager()->saveEntity($job);
        }
    }

    /**
     * Install module Dam
     */
    protected function sendNotification(): void
    {
        $em = $this
            ->getContainer()
            ->get('entityManager');
        $users = $em->getRepository('User')->getAdminUsers();
        if (!empty($users)) {
            foreach ($users as $user) {
                $message = 'In the new <a href="https://treopim.com">TreoPIM </a> version, the PimImage entity is replaced with the <a href="https://treodam.com/">TreoDAM module</a>. 
                So to continue work with the images, please, install the latest version of the <a href="https://treodam.com/">TreoDAM module</a>.';
                // create notification
                $notification = $em->getEntity('Notification');
                $notification->set('type', 'Message');
                $notification->set('message', $message);
                $notification->set('userId', $user['id']);
                // save notification
                $em->saveEntity($notification);
            }
        }
    }

    protected function createCustomPimImage(): void
    {
        if(!file_exists(self::PATH_SCOPE_PIM_IMAGE)) {
            /** @var EntityManager $entityManagerUtil */
            $entityManagerUtil = $this
                ->getContainer()
                ->get('entityManagerUtil');

            $params = [
                'hasAssignedUser' => true,
                'hasTeam' => true,
                'entity' => false,
                'customizable' => false
            ];

            $entityManagerUtil->create('PimImage', 'Base', $params);
            sleep(2);
            if (file_exists(self::PATH_ENTITY_DEFS_PIM_IMAGE)) {
                file_put_contents(self::PATH_ENTITY_DEFS_PIM_IMAGE, self::ENTITY_DEFS_PIM_IMAGE);
            }
            if (file_exists(self::PATH_SCOPE_PIM_IMAGE)) {
                file_put_contents(self::PATH_SCOPE_PIM_IMAGE, self::ENTITY_SCOPE);
            }
            if (empty($this->getContainer()->get('metadata')->get(['entityDefs', 'Channel', 'fields', 'pimImages']))) {
                $this->getContainer()->get('fieldManager')->create('Channel', 'pimImages', $this->getChannelFieldDefs());
            }
        }
    }

    protected function removeCustomPimImage(): void
    {
        if(file_exists(self::PATH_SCOPE_PIM_IMAGE)) {
            /** @var EntityManager $entityManagerUtil */
            $entityManagerUtil = $this
                ->getContainer()
                ->get('entityManagerUtil');

            $entityManagerUtil->delete('PimImage');
        }

        if (!empty($this->getContainer()->get('metadata')->get(['entityDefs', 'Channel', 'fields', 'pimImages']))) {
            $this->getContainer()
                ->get('fieldManager')
                ->delete('Channel', 'pimImages');
        }
    }

    /**
     * @inheritdoc
     * @throws Error
     */
    public function down(): void
    {
        if ($this->getContainer()->get('config')->get('pimAndDamInstalled') !== true) {
            return;
        }

        (new Auth($this->getContainer()))->useNoAuth();

        if (!empty($this->getContainer()->get('metadata')->get(['entityDefs', 'Channel', 'fields', 'pimImages']))) {
            $this->getContainer()
                ->get('fieldManager')
                ->update('Channel', 'pimImages', $this->getChannelFieldDefs(false));
        }

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
            $oldPath = ($attachment['private'] === '1' ? 'data/dam/private/' : 'data/dam/public/')
                . 'master/' . $attachment['storage_file_path'] . '/' . $attachment['name'];

            if (file_exists($oldPath)) {
                $attachmentUpdate = [];
                $storagePath = $this->getFilePath(FilePathBuilder::UPLOAD);
                $newPath = UploadDir::BASE_PATH . $storagePath . '/' . $attachment['name'];

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

            if (!empty($this->getEntityManager()->nativeQuery("SHOW TABLES LIKE 'asset_meta_data'")->fetchColumn(0))) {
                $sql = "DELETE FROM asset_meta_data WHERE asset_id IN ({$assetIds})";
                $this
                    ->getEntityManager()
                    ->nativeQuery($sql);
            }

            //set flag about installed Pim and Image

            $this->getContainer()->get('config')->remove('pimAndDamInstalled');
            $this->getContainer()->get('config')->save();
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
     * @param string $assetIds
     */
    protected function insertPimImageChannel(string $entityName, string $assetIds): void
    {
        if ($entityName === 'Product') {
            $where = ' pi.product_id IS NOT NULL AND pi.product_id != \'\'';
            $relationAsset = 'product_id';
        } elseif ($entityName === 'Category') {
            $where = ' pi.category_id IS NOT NULL AND pi.category_id != \'\'';
            $relationAsset = 'category_id';
        } else {
            return;
        }

        $this->getEntityManager()
            ->nativeQuery(
                "INSERT INTO pim_image_channel (channel_id, pim_image_id)
                                    SELECT arc.channel_id, pi.id AS pim_image_id
                                    FROM asset AS a
                                             RIGHT JOIN asset_relation ar ON
                                                                ar.asset_id = a.id
                                                            AND ar.deleted = 0
                                                            AND ar.scope = 'Channel'
                                                            AND ar.entity_name = '{$entityName}'
                                             RIGHT JOIN asset_relation_channel arc
                                                        ON ar.id = arc.asset_relation_id
                                                            AND arc.deleted = 0
                                             LEFT JOIN pim_image pi ON       
                                                                pi.image_id = a.file_id
                                                           AND  pi.deleted = 0
                                                           AND  pi.scope = 'Channel'
                                                           AND  pi.{$relationAsset} = ar.entity_id
                                    WHERE {$where} 
                                        AND a.deleted = 0 
                                        AND a.type = 'Gallery Image' 
                                        AND a.id IN ({$assetIds});"
            );
    }

    /**
     * @param string $table
     * @param array $values
     * @param string $id
     */
    protected function updateById(string $table, array $values, string $id): void
    {
        $setValues = [];
        $params = [];
        foreach ($values as $field => $value) {
            $setValues[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $setValues) . " WHERE id = '{$id}'";
        $this->getEntityManager()->nativeQuery($sql, $params);
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

    /**
     * @param bool $layoutDisabled
     * @return array
     */
    public function getChannelFieldDefs($layoutDisabled = true): array
    {
        $defs['type'] = 'linkMultiple';
        $defs['linkMultiple'] = true;
        $defs['layoutMassUpdateDisabled'] = true;
        $defs['importDisabled'] = true;
        $defs['layoutDetailDisabled'] = true;
        $defs['noLoad'] = true;

        $defs['layoutRelationshipsDisabled'] = $layoutDisabled;
        $defs['layoutListDisabled'] = $layoutDisabled;
        $defs['layoutFiltersDisabled'] = $layoutDisabled;
        $defs['layoutDetailSmallDisabled'] = $layoutDisabled;
        $defs['layoutListSmallDisabled'] = $layoutDisabled;

        $defs['linkDefs']['type'] = 'hasMany';
        $defs['linkDefs']['relationName'] = 'pimImageChannel';
        $defs['linkDefs']['foreign'] = 'channels';
        $defs['linkDefs']['entity'] = 'PimImage';

        return $defs;
    }

    public const ENTITY_DEFS_PIM_IMAGE = '{
      "fields": {
        "name": {
          "type": "varchar",
          "required": false,
          "trim": true,
          "layoutDetailDisabled": true,
          "layoutDetailSmallDisabled": true
        },
        "category": {
          "type": "link"
        },
        "product": {
          "type": "link"
        },
        "type": {
          "type": "enum",
          "notStorable": true,
          "required": false,
          "fontSize": 1,
          "options": [
            "File",
            "Files",
            "Link"
          ],
          "default": "File",
          "layoutListDisabled": true,
          "layoutListSmallDisabled": true,
          "layoutFiltersDisabled": true,
          "layoutMassUpdateDisabled": true
        },
        "image": {
          "type": "image",
          "required": false,
          "previewSize": "small"
        },
        "images": {
          "type": "attachmentMultiple",
          "required": false,
          "previewSize": "small",
          "sourceList": [],
          "layoutListDisabled": true,
          "layoutMassUpdateDisabled": true
        },
        "link": {
          "type": "varchar",
          "required": false,
          "trim": true,
          "layoutListDisabled": true,
          "layoutListSmallDisabled": true,
          "layoutFiltersDisabled": true,
          "layoutMassUpdateDisabled": true
        },
        "sortOrder": {
          "type": "int",
          "required": false,
          "default": null,
          "disableFormatting": false
        },
        "scope": {
          "type": "enum",
          "required": true,
          "fontSize": 1,
          "options": [
            "Global",
            "Channel"
          ],
          "default": "Global",
          "layoutListSmallDisabled": true
        },
        "channels": {
          "type": "linkMultiple",
          "layoutListSmallDisabled": true,
          "layoutMassUpdateDisabled": true,
          "noLoad": false
        },
        "createdAt": {
          "type": "datetime",
          "readOnly": true
        },
        "modifiedAt": {
          "type": "datetime",
          "readOnly": true
        },
        "createdBy": {
          "type": "link",
          "readOnly": true,
          "view": "views/fields/user"
        },
        "modifiedBy": {
          "type": "link",
          "readOnly": true,
          "view": "views/fields/user"
        },
        "assignedUser": {
          "type": "link",
          "required": true,
          "view": "views/fields/assigned-user"
        },
        "teams": {
          "type": "linkMultiple",
          "view": "views/fields/teams"
        }
      },
      "links": {
        "category": {
          "type": "belongsTo",
          "foreign": "pimImages",
          "entity": "Category"
        },
        "product": {
          "type": "belongsTo",
          "foreign": "pimImages",
          "entity": "Product"
        },
        "image": {
          "type": "belongsTo",
          "entity": "Attachment",
          "skipOrmDefs": true
        },
        "channels": {
          "type": "hasMany",
          "relationName": "pimImageChannel",
          "foreign": "pimImages",
          "entity": "Channel",
          "disableMassRelation": true
        },
        "images": {
          "type": "hasChildren",
          "entity": "Attachment",
          "foreign": "parent",
          "layoutRelationshipsDisabled": true,
          "relationName": "attachments"
        },
        "createdBy": {
          "type": "belongsTo",
          "entity": "User"
        },
        "modifiedBy": {
          "type": "belongsTo",
          "entity": "User"
        },
        "assignedUser": {
          "type": "belongsTo",
          "entity": "User"
        },
        "teams": {
          "type": "hasMany",
          "entity": "Team",
          "relationName": "EntityTeam",
          "layoutRelationshipsDisabled": true
        }
      },
      "collection": {
        "sortBy": "sortOrder",
        "asc": true
      },
      "indexes": {
        "name": {
          "columns": [
            "name",
            "deleted"
          ]
        },
        "assignedUser": {
          "columns": [
            "assignedUserId",
            "deleted"
          ]
        }
      }
    }';

    public const ENTITY_SCOPE = '{
      "entity": true,
      "layouts": true,
      "tab": true,
      "acl": true,
      "aclPortal": true,
      "aclPortalLevelList": [ "all", "account","contact","own","no"],
      "customizable": false,
      "importable": true,
      "notifications": true,
      "stream": false,
      "disabled": false,
      "type": "Base",
      "module": "Pim",
      "object": true,
      "hasAssignedUser": false,
      "hasTeam": false,
      "hasOwner": false,
      "isCustom": true
    }';
}
