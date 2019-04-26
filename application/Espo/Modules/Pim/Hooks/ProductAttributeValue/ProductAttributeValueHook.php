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

namespace Espo\Modules\Pim\Hooks\ProductAttributeValue;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Hooks\Base as BaseHook;
use Espo\ORM\Entity;

/**
 * Class ProductAttributeValueHook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductAttributeValueHook extends BaseHook
{
    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        if (empty($product = $entity->get('product')) || empty($category = $entity->get('attribute'))) {
            throw new BadRequest($this->exception('Product and Attribute cannot be empty'));
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        // parent init
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        // prepare count
        $count = 0;

        if ($entity->get('scope') == 'Global') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(
                    [
                        'id!='        => $entity->get('id'),
                        'productId'   => $entity->get('productId'),
                        'attributeId' => $entity->get('attributeId'),
                        'scope'       => 'Global',
                    ]
                )
                ->count();
        }

        if ($entity->get('scope') == 'Channel') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->distinct()
                ->join('channels')
                ->where(
                    [
                        'id!='        => $entity->get('id'),
                        'productId'   => $entity->get('productId'),
                        'attributeId' => $entity->get('attributeId'),
                        'scope'       => 'Channel',
                        'channels.id' => $entity->get('channelsIds'),
                    ]
                )
                ->count();
        }

        return empty($count);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'ProductAttributeValue');
    }
}
