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

namespace Pim\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;
use Treo\Entities\Attachment;

/**
 * Class PimImage
 *
 * @author r.ratsun@treolabs.com
 */
class PimImage extends Base
{
    /**
     * @inheritDoc
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        // call parent action
        parent::beforeSave($entity, $options);

        // prepare image by type
        $this->prepareImageByType($entity);

        // set entity name
        if (empty($entity->get('name'))) {
            $entity->set('name', $entity->get('imageName'));
        }

        // set sort order
        if (is_null($entity->get('sortOrder'))) {
            $entity->set('sortOrder', (int)$this->max('sortOrder') + 1);
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest('Such record already exists');
        }

        if (!$entity->isNew() && $entity->isAttributeChanged('sortOrder')) {
            $this->updateSortOrder($entity);
        }
    }

    /**
     * @inheritDoc
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        // call parent
        parent::afterSave($entity, $options);

        // update main image
        $this->updateMainImage($entity);

        // unrelate all channels
        if ($entity->get('scope') == 'Global') {
            $this->unrelate($entity, 'channels', true);
        }

        // save new images for Files type
        if (!empty($entity->newImages)) {
            foreach ($entity->newImages as $k => $image) {
                try {
                    $this->getEntityManager()->saveEntity($image);
                } catch (BadRequest $e) {
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function afterRemove(Entity $entity, array $options = [])
    {
        // call parent
        parent::afterRemove($entity, $options);

        // update main image
        $this->updateMainImage($entity);
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('fileStorageManager');
    }

    /**
     * @param string $link
     *
     * @return Attachment
     * @throws Error
     */
    protected function createAttachmentByLink(string $link): Attachment
    {
        // get contents
        if (empty($contents = @file_get_contents($link))) {
            throw new Error('Wrong image link. Link: ' . $link);
        }

        // create attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', array_pop(explode('/', $link)));
        $attachment->set('field', 'image');
        $attachment->set('role', 'Attachment');

        // get file storage manager
        $sm = $this->getInjection('fileStorageManager');

        // store file
        $sm->putContents($attachment, $contents);

        // get mime type
        $type = mime_content_type($sm->getLocalFilePath($attachment));

        if (!in_array($type, ['image/jpeg', 'image/png', 'image/gif'])) {
            $sm->unlink($attachment);
            throw new Error('Wrong file mime type. Only image allowed. Link:' . $link);
        }

        // set mime type
        $attachment->set('type', $type);

        // save attachment
        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        // prepare where
        $where = [
            'id!='       => $entity->get('id'),
            'categoryId' => $entity->get('categoryId'),
            'productId'  => $entity->get('productId')
        ];
        if ($entity->get('type') == 'Link') {
            $where['link'] = $entity->get('link');
        } else {
            $where['imageId'] = $entity->get('imageId');
        }

        $count = $this
            ->getEntityManager()
            ->getRepository('PimImage')
            ->where($where)
            ->count();

        return empty($count);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function updateMainImage(Entity $entity): bool
    {
        if (!empty($foreign = $entity->get('product'))) {
        } elseif (!empty($foreign = $entity->get('category'))) {
        } else {
            return false;
        }

        // find first image
        $first = $this
            ->select(['imageId'])
            ->where([lcfirst($foreign->getEntityName()) . 'Id' => $foreign->get('id')])
            ->order('sortOrder')
            ->findOne();

        // prepare image id
        $imageId = empty($first) ? null : $first->get('imageId');

        // update main image if it needs
        if ($imageId != $foreign->get('imageId')) {
            // set image
            $foreign->set('imageId', $imageId);

            // set keep attachment param
            $foreign->keepAttachment = true;

            // save
            $this->getEntityManager()->saveEntity($foreign);
        }

        return true;
    }

    /**
     * @param Entity $entity
     *
     * @throws BadRequest
     * @throws Error
     */
    protected function prepareImageByType(Entity $entity)
    {
        // for link type
        if ($entity->get('type') == 'Link' && !empty($entity->get('link'))) {
            $attachment = $this->createAttachmentByLink($entity->get('link'));
            $entity->set('imageId', $attachment->get('id'));
            $entity->set('imageName', $attachment->get('name'));
        }

        // for files type
        if ($entity->get('type') == 'Files' && !empty($entity->get('imagesTypes'))) {
            // add proportye for collecting images
            $entity->newImages = [];

            foreach ($entity->get('imagesTypes') as $id => $mime) {
                // skip if not image
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif',])) {
                    throw new BadRequest('Wrong file mime type. Only images allowed.');
                }

                // prepare image name
                $imageName = $entity->get('imagesNames')->$id;

                if (empty($entity->get('imageId'))) {
                    // set image id for first image
                    $entity->set('imageId', $id);
                    $entity->set('imageName', $imageName);
                } else {
                    // save else images as separate records
                    $newImage = $this->get();
                    $newImage->set(
                        [
                            'scope'       => $entity->get('scope'),
                            'channelsIds' => $entity->get('channelsIds'),
                            'productId'   => $entity->get('productId'),
                            'categoryId'  => $entity->get('categoryId'),
                            'imageId'     => $id,
                            'imageName'   => $imageName,
                        ]
                    );

                    $entity->newImages[] = $newImage;
                }
            }

            $entity->set('imagesIds', null);
            $entity->set('imagesNames', null);
            $entity->set('imagesTypes', null);
        }
    }

    /**
     * @param Entity $entity
     */
    protected function updateSortOrder(Entity $entity): void
    {
        $data = $this
            ->select(['id'])
            ->where(
                [
                    'id!='        => $entity->get('id'),
                    'sortOrder>=' => $entity->get('sortOrder'),
                    'productId'   => $entity->get('productId')
                ]
            )
            ->order('sortOrder')
            ->find()
            ->toArray();

        if (!empty($data)) {
            // create max
            $max = $entity->get('sortOrder');

            // prepare sql
            $sql = '';
            foreach ($data as $row) {
                // increase max
                $max++;

                // prepare id
                $id = $row['id'];

                // prepare sql
                $sql .= "UPDATE pim_image SET sort_order='$max' WHERE id='$id';";
            }

            // execute sql
            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();
        }
    }
}
