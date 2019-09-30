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

use Espo\ORM\Entity;
use Espo\Core\Templates\Repositories\Base;

/**
 * Class Category
 *
 * @author r.ratsun@treolabs.com
 */
class Category extends Base
{
    /**
     * @inheritDoc
     */
    public function relate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        if ($relationName == 'pimImages') {
            // prepare image
            if (empty($foreign->get('productId') && empty($foreign->get('categoryId')))) {
                $image = $foreign;
            } else {
                $image = $this->getEntityManager()->getEntity('PimImage');
            }

            // set data
            $image->set(
                [
                    'name'       => $foreign->get('name'),
                    'productId'  => null,
                    'categoryId' => $entity->get('id'),
                    'imageId'    => $foreign->get('imageId'),
                    'imageName'  => $foreign->get('imageName'),
                    'scope'      => 'Global'
                ]
            );

            // save
            $this->getEntityManager()->saveEntity($image);

            // unrelate all previous channels
            if (!$image->isNew()) {
                $this->getEntityManager()->getRepository('PimImage')->unrelate($image, 'channels', true);
            }

            return true;
        }

        return parent::relate($entity, $relationName, $foreign, $data, $options);
    }
}
