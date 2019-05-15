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

namespace Espo\Modules\Pim\Migration;

use Treo\Core\Migration\AbstractMigration;
use Espo\Core\Utils\Util;

/**
 * Migration class for version 3.1.0
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot1Dot0 extends AbstractMigration
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        // drop triggers
        $this->dropTriggers();

        // migrate channel attribute values
        $this->migrateChannelAttributeValues();

        // migrate product family attributes
        $this->migrateProductFamilyAttributes();

        // switch isRequired params
        $this->switchIsRequiredParams();
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

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();
    }

    /**
     * Migrate channel attribute values
     */
    protected function migrateChannelAttributeValues(): void
    {
        $sql
            = "SELECT cpav.*, p.id as product_id, a.id as attribute_id   
                FROM channel_product_attribute_value AS cpav 
                JOIN channel AS c ON c.id=cpav.channel_id 
                JOIN product_attribute_value AS pav ON pav.id=cpav.product_attribute_id
                JOIN product AS p ON p.id=pav.product_id
                JOIN attribute AS a ON a.id=pav.attribute_id  
                WHERE cpav.deleted=0 AND c.deleted=0 AND pav.deleted=0 AND p.deleted=0 AND a.deleted=0";

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            // delete previous
            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare("DELETE FROM product_attribute_value WHERE scope='Channel'");
            $sth->execute();

            foreach ($data as $row) {
                $entity = $this->getEntityManager()->getEntity('ProductAttributeValue');
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

                $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);

                // relate
                $this
                    ->getEntityManager()
                    ->getRepository('ProductAttributeValue')
                    ->relate($entity, 'channels', $this->getEntityManager()->getEntity('Channel', $row['channel_id']));
            }
        }
    }

    /**
     * Migrate product family attribute
     */
    protected function migrateProductFamilyAttributes(): void
    {
        $sql
            = "SELECT pfal.*   
                FROM product_family_attribute_linker AS pfal 
                JOIN product_family AS pf ON pf.id=pfal.product_family_id 
                JOIN attribute AS a ON a.id=pfal.attribute_id 
                WHERE pfal.deleted=0 AND pf.deleted=0 AND a.deleted=0";

        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            // delete previous
            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare("DELETE FROM product_family_attribute WHERE 1");
            $sth->execute();

            foreach ($data as $row) {
                $entity = $this->getEntityManager()->getEntity('ProductFamilyAttribute');
                $entity->set('productFamilyId', $row['product_family_id']);
                $entity->set('attributeId', $row['attribute_id']);
                $entity->set('isRequired', $row['is_required']);
                $entity->set('scope', 'Global');
                $entity->set('createdById', 'system');
                $entity->set('createdAt', date("Y-m-d H:i:s"));

                $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
            }
        }
    }

    /**
     * Switch isRequired params
     *
     * @return bool
     */
    protected function switchIsRequiredParams(): bool
    {
        $productFamilyAttributes = $this
            ->getEntityManager()
            ->getRepository('ProductFamilyAttribute')
            ->where(['isRequired' => true])
            ->find();

        // exit
        if (count($productFamilyAttributes) == 0) {
            return true;
        }

        foreach ($productFamilyAttributes as $productFamilyAttribute) {
            $productAttributeValues = $productFamilyAttribute->get('productAttributeValues');
            if (count($productAttributeValues) > 0) {
                foreach ($productAttributeValues as $productAttributeValue) {
                    $productAttributeValue->set('isRequired', true);
                    $this->getEntityManager()->saveEntity($productAttributeValue, ['skipAll' => true]);
                }
            }
        }

        return true;
    }
}
