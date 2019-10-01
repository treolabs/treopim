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

        if ($entity->get('type') == 'Link' && !empty($entity->get('link'))) {
            $attachment = $this->createAttachmentByLink($entity->get('link'));
            $entity->set('imageId', $attachment->get('id'));
            $entity->set('imageName', $attachment->get('name'));
        }

        // set entity name
        $entity->set('name', $entity->get('imageName'));

        // set sort order
        $entity->set('sortOrder', time());

        if (!$this->isUnique($entity)) {
            throw new BadRequest('Such record already exists');
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
        $count = $this
            ->getEntityManager()
            ->getRepository('PimImage')
            ->where(
                [
                    'categoryId' => $entity->get('categoryId'),
                    'productId'  => $entity->get('productId'),
                    'imageId'    => $entity->get('imageId')
                ]
            )
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

            // save
            $this->getEntityManager()->saveEntity($foreign);
        }

        return true;
    }
}
