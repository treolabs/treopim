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

/**
 * ImageData service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ImageData extends AbstractService
{

    /**
     * Set image data by cron
     *
     * @param array $data
     *
     * @return bool
     */
    public function cron(array $data): bool
    {
        // prepare result
        $result = false;

        if (isset($data['entityName']) && isset($data['entityId'])) {
            // get image entity
            $entity = $this->getEntityManager()->getEntity($data['entityName'], $data['entityId']);

            if (!empty($entity)) {
                // prepare data
                $data = [];
                switch ($entity->get('type')) {
                    case 'File':
                        // set alt image
                        if (empty($entity->get('name'))) {
                            $data['name'] = $entity->get('imageName');
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
                            $data['name'] = pathinfo($imageLink, PATHINFO_FILENAME);
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

                // set image sizes
                $data['size'] = round($imageBytes / pow(2, 20), 2);
                $data['width'] = $imageSize[0];
                $data['height'] = $imageSize[1];
                $data['imageType'] = $imageType;
                $data['state'] = 'processed';

                // update data image
                $entity->setFetched('type', $entity->get('type'));
                $entity->set($data);

                $this->getEntityManager()->saveEntity($entity, ['isImageDataSaved' => true]);
            }

            // prepare result
            $result = true;
        }

        return $result;
    }
}
