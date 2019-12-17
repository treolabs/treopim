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

namespace Pim\EntryPoints;

use Espo\Core\Exceptions\NotFound;
use Treo\Entities\Attachment;
use Treo\EntryPoints\Image as Base;

/**
 * Class Image
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Image extends Base
{
    /**
     * @inheritDoc
     * @throws NotFound
     */
    protected function checkAttachment(Attachment $attachment): bool
    {
        if (in_array($attachment->get('relatedType'), ['Asset'])) {
            $entity = $this
                ->getEntityManager()
                ->getRepository('Asset')
                ->where(['fileId' => $attachment->get('id')])
                ->findOne();
            if (empty($entity)) {
                throw new NotFound();
            }
        } else {
            $entity = $attachment;
        }

        return $this->getAcl()->checkEntity($entity);
    }
}
