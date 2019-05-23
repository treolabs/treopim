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

namespace Treo\Migrations\Pim;

use Espo\Core\Utils\Util;

/**
 * Migration class for version 3.1.0
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot1Dot0 extends V3Dot0Dot1
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        $this->dropTriggers();
        $this->channelAttributeValueUp();
        $this->productFamilyAttributesUp();
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
        $this->channelAttributeValueDown();
        $this->productFamilyAttributesDown();
    }

    /**
     * Drop triggers
     */
    protected function dropTriggers()
    {
        $sql = "DROP TRIGGER IF EXISTS trigger_after_insert_product_family_attribute_linker;";
        $sql .= "DROP TRIGGER IF EXISTS trigger_after_update_product_family_attribute_linker;";
        $sql .= "DROP TRIGGER IF EXISTS trigger_after_insert_product;";
        $sql .= "DROP TRIGGER IF EXISTS trigger_after_update_product;";

        $this->execute($sql);
    }

    /**
     * Migrate attribute value up
     */
    protected function channelAttributeValueUp()
    {
        $sql
            = "SELECT cpav.*, p.id as product_id, a.id as attribute_id   
                FROM channel_product_attribute_value AS cpav 
                JOIN channel AS c ON c.id=cpav.channel_id 
                JOIN product_attribute_value AS pav ON pav.id=cpav.product_attribute_id
                JOIN product AS p ON p.id=pav.product_id
                JOIN attribute AS a ON a.id=pav.attribute_id  
                WHERE cpav.deleted=0 AND c.deleted=0 AND pav.deleted=0 AND p.deleted=0 AND a.deleted=0";

        if (!empty($data = $this->fetchAll($sql))) {
            // prepare repository
            $repository = $this->getEntityManager()->getRepository('ProductAttributeValue');
            foreach ($data as $row) {
                $entity = $repository->get();
                $entity->set('productId', $row['product_id']);
                $entity->set('attributeId', $row['attribute_id']);
                $entity->set('scope', 'Channel');
                $entity->set('createdById', 'system');
                $entity->set('createdAt', date("Y-m-d H:i:s"));
                $entity->set('value', $row['value']);
                foreach ($row as $key => $value) {
                    if (strpos($key, 'value_') !== false) {
                        $entity->set(Util::toCamelCase($key), $value);
                    }
                }
                $this->getEntityManager()->saveEntity($entity, ['skipAll' => true, 'skipProductAttributeValueHook' => true]);

                // relate
                $repository->relate($entity, 'channels', $row['channel_id']);
            }
        }
    }

    /**
     * Migrate attribute value down
     */
    protected function channelAttributeValueDown()
    {
        $this->execute("DELETE FROM product_attribute_value WHERE scope='Channel'");
        $this->execute("DELETE FROM product_attribute_value_channel WHERE 1");
    }

    /**
     * Migrate product family attribute up
     */
    protected function productFamilyAttributesUp(): void
    {
        $sql
            = "SELECT pfal.*   
                FROM product_family_attribute_linker AS pfal 
                JOIN product_family AS pf ON pf.id=pfal.product_family_id 
                JOIN attribute AS a ON a.id=pfal.attribute_id 
                WHERE pfal.deleted=0 AND pf.deleted=0 AND a.deleted=0";

        if (!empty($data = $this->fetchAll($sql))) {
            foreach ($data as $row) {
                $entity = $this->getEntityManager()->getEntity('ProductFamilyAttribute');
                $entity->set('productFamilyId', $row['product_family_id']);
                $entity->set('attributeId', $row['attribute_id']);
                $entity->set('isRequired', $row['is_required']);
                $entity->set('scope', 'Global');
                $entity->set('createdById', 'system');
                $entity->set('createdAt', date("Y-m-d H:i:s"));

                $this->getEntityManager()->saveEntity($entity, ['skipAll' => true, 'skipValidation' => true]);

                // prepare update sql
                $sql
                    = "UPDATE product_attribute_value
                       SET product_family_attribute_id='" . $entity->get('id') . "', is_required=" . $row['is_required'] . "
                       WHERE scope='Global' 
                         AND attribute_id='" . $entity->get('attributeId') . "'
                         AND product_id IN (SELECT id FROM product WHERE product_family_id='" . $entity->get('productFamilyId') . "')";

                $this->execute($sql);
            }
        }
    }

    /**
     * Migrate product family attribute down
     */
    protected function productFamilyAttributesDown(): void
    {
        $this->execute("DELETE FROM product_family_attribute WHERE 1");
        $this->execute("UPDATE product_attribute_value SET product_family_attribute_id=NULL WHERE 1");
    }
}
