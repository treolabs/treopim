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

namespace Espo\Modules\Pim\EntryPoints;

use Espo\Core\Exceptions\Forbidden;
use Espo\EntryPoints\Image;

/**
 * Class ProductImage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductImage extends Image
{

    public static $authRequired = false;

    /**
     * Show image
     *
     * @param      $id
     * @param      $size
     * @param bool $disableAccessCheck
     *
     * @throws \Espo\Core\Exceptions\Error
     * @throws \Espo\Core\Exceptions\Forbidden
     * @throws \Espo\Core\Exceptions\NotFound
     */
    protected function show($id, $size, $disableAccessCheck = false)
    {
        if ($this->isProductImage($id)) {
            parent::show($id, $size, $disableAccessCheck);
        } else {
            throw new Forbidden();
        }
    }

    /**
     * Check is ProductImage
     *
     * @param string $id
     *
     * @return bool
     */
    protected function isProductImage(string $id): bool
    {
        $entities = $this
            ->getEntityManager()
            ->getRepository('Attachment')
            ->where(['id' => $id, 'relatedType' => 'ProductImage'])
            ->find();

        return (bool)$entities->count();
    }
}
