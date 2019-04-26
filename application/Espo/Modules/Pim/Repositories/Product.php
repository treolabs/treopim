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

namespace Espo\Modules\Pim\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class Product
 *
 * @author r.ratsun@treolabs.com
 */
class Product extends Base
{
    /**
     * @return array
     */
    public function getInputLanguageList(): array
    {
        return $this->getConfig()->get('inputLanguageList', []);
    }

    /**
     * @inheritdoc
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        // save attributes
        $this->saveAttributes($entity);

        // parent action
        parent::afterSave($entity, $options);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function saveAttributes(Entity $entity): bool
    {
        // exit
        if (empty($entity->productAttribute) && empty($entity->productChannelAttribute)) {
            return true;
        }

        echo '<pre>';
        print_r('123');
        die();

        $productAttributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['productId' => $entity->get('id')])
            ->find();

        if (!empty($entity->productAttribute)) {

        }


        return true;
    }
}
