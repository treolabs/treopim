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

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;

/**
 * Class AbstractImageListener
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractImageListener extends AbstractEntityListener
{
    /**
     * @var string
     */
    protected $entityName = null;

    /**
     * @param $entity
     *
     * @return string
     */
    abstract protected function getCondition(Entity $entity);

    /**
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeSave(Event $event)
    {
        // get entity
        $entity = $event->getArgument('entity');

        $this->clearUnusedFields($entity);

        // is asset code valid?
        if (!$this->isAssetCodeValid($entity)) {
            throw new BadRequest(
                $this->translate(
                    'Code is invalid',
                    'exceptions',
                    'Global'
                )
            );
        }
    }

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get entity
        $entity = $event->getArgument('entity');

        if (empty($entity->isImageDataSaved)) {
            $this->setImageData($entity);
        }
    }

    /**
     * Set data for image
     *
     * @param Entity $entity
     */
    protected function setImageData(Entity $entity)
    {
        switch ($entity->get('type')) {
            case 'File':
                // set alt image
                if (empty($entity->get('name'))) {
                    $entity->set('name', $entity->get('imageName'));
                }

                // prepare image data
                $image = $entity->get('image');
                $filePath = $this->getEntityManager()->getRepository('Attachment')->getFilePath($image);

                // get image sizes
                $imageBytes = $image->get('size');
                $imageSize = getimagesize($filePath);

                // get image type
                $imageType = $image->get('type');

                // set fetched value to avoid looping
                $entity->setFetched('imageId', $entity->get('imageId'));
                break;
            case 'Link':
                $imageLink = $entity->get('imageLink');
                // set alt image
                if (empty($entity->get('name'))) {
                    $entity->set('name', pathinfo($imageLink, PATHINFO_FILENAME));
                }
                // get image sizes
                $imageBytes = get_headers($imageLink, 1)['Content-Length'];
                $imageSize = getimagesize($imageLink);

                // get image type
                $imageType = image_type_to_mime_type(exif_imagetype($imageLink));

                // set fetched value to avoid looping
                $entity->setFetched('imageLink', $imageLink);
                break;
        }

        // update data image
        $entity->setFetched('type', $entity->get('type'));
        $entity->set('size', round($imageBytes / pow(2, 20), 2));
        $entity->set('width', $imageSize[0]);
        $entity->set('height', $imageSize[1]);
        $entity->set('imageType', $imageType);

        // set flag
        $entity->isImageDataSaved = true;

        $this->getEntityManager()->saveEntity($entity);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isAssetCodeValid(Entity $entity): bool
    {
        // prepare result
        $result = false;

        if (preg_match(self::$codePattern, $entity->get('name'))) {
            $result = $this->isUnique($entity, 'name');
        }

        return $result;
    }

    /**
     * Return Oldest image
     *
     * @param $entity
     *
     * @return mixed
     */
    protected function getOldestImage(Entity $entity)
    {
        return $this->getEntityManager()
            ->getRepository($entity->getEntityType())
            ->where($this->getCondition($entity))
            ->order('createdAt', 'ASC')
            ->findOne();
    }

    /**
     * Save entity
     *
     * @param Entity $entity
     */
    protected function saveEntity(Entity $entity)
    {
        $this->getEntityManager()->saveEntity($entity, []);
    }

    /**
     * Clean unused fields
     *
     * @param Entity $entity
     */
    protected function clearUnusedFields(Entity $entity)
    {
        if ($entity->isNew()) {
            switch ($entity->get('type')) {
                case 'Link':
                    $image = $entity->get('image');
                    if ($image instanceof Entity) {
                        $this->getEntityManager()->removeEntity($image);
                        $entity->set(['imageId' => null]);
                    }
                    break;
                case 'File':
                    $entity->set(['imageLink' => null]);
                    break;
            }
        }
    }
}
