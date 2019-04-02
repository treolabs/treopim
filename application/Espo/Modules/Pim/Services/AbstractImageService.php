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

namespace Espo\Modules\Pim\Services;

use Espo\Core\Templates\Services\Base;
use Espo\Core\Exceptions;
use Espo\Core\Templates\Entities\Base as BaseEntity;
use stdClass;

/**
 * AbstractImage service class
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractImageService extends Base
{

    /**
     * Create entity
     *
     * @param stdClass $data
     *
     * @return BaseEntity
     * @throws Exceptions\Error
     */
    public function createEntity($data)
    {
        if ($data->type === 'File' && !$this->isValidImageName($data->imageName)) {
            $types = implode(', ', $this->getAllowedImageTypes());
            throw new Exceptions\Error("Wrong file type. {$types} allowed.");
        } elseif ($data->type === 'Link' && !$this->isValidImageLink($data->imageLink)) {
            throw new Exceptions\Error('Wrong image link.');
        }

        return parent::createEntity($data);
    }

    /**
     * Is valid image name ?
     *
     * @param string $name
     *
     * @return boolean
     */
    protected function isValidImageName($name)
    {
        // parse image name
        $type = strtoupper(end(explode('.', $name)));

        return in_array($type, $this->getAllowedImageTypes());
    }

    /**
     * Get allowed image file type
     *
     * @return array
     */
    protected function getAllowedImageTypes()
    {
        return ['GIF', 'JPEG', 'PNG', 'JPG'];
    }

    /**
     * Is valid image url?
     *
     * @param  string $link
     *
     * @return bool
     */
    protected function isValidImageLink($link)
    {
        return (bool)exif_imagetype($link);
    }
}
