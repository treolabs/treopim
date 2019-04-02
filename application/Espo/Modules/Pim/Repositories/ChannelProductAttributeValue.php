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

use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;

/**
 * Class ChannelProductAttributeValue
 *
 * @author r.ratsun@treolabs.com
 */
class ChannelProductAttributeValue extends \Espo\Core\Templates\Repositories\Base
{
    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('language');
    }

    /**
     * @inheritdoc
     */
    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);

        if ($this->isMultiChannel($entity)) {
            throw new Error($this->exception("Attribute is Multi-Channel for Product Family"));
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isMultiChannel(Entity $entity): bool
    {
        // prepare data
        $productAttribute = $entity->get('productAttribute');

        $sql
            = "SELECT
                 count(id) as total
               FROM
                 product_family_attribute_linker as pfal
               WHERE
                    deleted=0 
                AND is_multi_channel=1
                AND attribute_id = '" . $productAttribute->get('attributeId') . "'
                AND product_family_id = '" . $productAttribute->get('productFamilyId') . "'";

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        $data = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($data['total']);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'ChannelProductAttributeValue');
    }
}
