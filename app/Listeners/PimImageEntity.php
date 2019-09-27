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
use Treo\Entities\Attachment;
use Treo\Listeners\AbstractListener;

/**
 * Class PimImageEntity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class PimImageEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeSave(Event $event)
    {
        // get entity
        $entity = $event->getArgument('entity');

        // create attachment by link
        if ($entity->get('type') == 'Link' && !empty($entity->get('link'))) {
            $attachment = $this->createAttachmentByLink($entity->get('link'));
            $entity->set('imageId', $attachment->get('id'));
            $entity->set('imageName', $attachment->get('name'));
        }

        // set entity name
        $entity->set('name', $entity->get('imageName'));

        // set sort order
        $entity->set('sortOrder', time());
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
        $sm = $this->getContainer()->get('fileStorageManager');

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
}
