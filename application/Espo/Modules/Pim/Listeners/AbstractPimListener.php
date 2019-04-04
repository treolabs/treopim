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

namespace Espo\Modules\Pim\Listeners;

use Treo\Listeners\AbstractListener;
use PDO;

/**
 * AbstractPimListener class
 *
 * @author r.zablodskiy@treolabs.com
 */
class AbstractPimListener extends AbstractListener
{
    /**
     * Remove productFamilyAttribute
     *
     * @param string $productFamilyId
     * @param string $attributeId
     */
    protected function removeProductAttributeValue(string $productFamilyId, string $attributeId): void
    {
        $productsIds = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->select(['id'])
            ->where(['productFamilyId' => $productFamilyId])
            ->find()
            ->toArray();

        if (count($productsIds) > 0) {
            // prepare product ids
            $productsIds = implode("','", array_column($productsIds, 'id'));

            // prepare sql
            $sql
                = "DELETE 
               FROM product_attribute_value 
               WHERE 
                     product_id IN ('{$productsIds}')
                 AND attribute_id='{$attributeId}'
                 AND product_family_id IS NOT NULL";

            // execute
            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();
        }
    }

    /**
     * Set productAttributeValue assignedUser and ownerUser from attribute
     *
     * @param array $attributeIds
     * @param array $productIds
     */
    protected function setProductAttributeValueUser(array $attributeIds, array $productIds)
    {
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where([
                'productId' => $productIds,
                'attributeId' => $attributeIds
            ])
            ->find();

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                // set productAttributeValue assignedUser and ownerUser
                $attribute->set('assignedUserId', $attribute->get('attribute')->get('assignedUserId'));
                $attribute->set('ownerUserId', $attribute->get('attribute')->get('ownerUserId'));

                // save productAttributeValue
                $this->getEntityManager()->saveEntity($attribute);
            }
        }
    }
}
