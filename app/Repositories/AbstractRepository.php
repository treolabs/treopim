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

namespace Pim\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class AbstractRepository
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractRepository extends Base
{
    /**
     * @inheritDoc
     */
    public function afterRemove(Entity $entity, array $options = [])
    {
        // call parent
        parent::afterRemove($entity, $options);

        if (in_array($entity->getEntityName(), ['Product', 'Category'])) {
            // get images
            if (!empty($images = $entity->get('pimImages'))) {
                foreach ($images as $image) {
                    $this->getEntityManager()->removeEntity($image);
                }
            }
        }
    }

    /**
     * @param Entity $entity
     * @param mixed  $foreign
     * @param mixed  $data
     * @param array  $options
     */
    public function relatePimImages(Entity $entity, $foreign, $data, array $options = [])
    {
        // prepare image
        $image = $this->getEntityManager()->getRepository('PimImage')->get();

        // set data
        $image->set('name', $foreign->get('name'));
        $image->set('imageId', $foreign->get('imageId'));
        $image->set('imageName', $foreign->get('imageName'));
        $image->set(lcfirst($entity->getEntityName()) . 'Id', $entity->get('id'));

        // save
        $this->getEntityManager()->saveEntity($image);

        return true;
    }
}
