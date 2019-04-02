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

namespace Espo\Modules\Pim\Metadata;

use Treo\Metadata\AbstractMetadata;

/**
 * Metadata
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Metadata extends AbstractMetadata
{

    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    public function modify(array $data): array
    {
        // create triggers
        $this->createTriggers();

        return $data;
    }

    /**
     * Create triggers if it needs
     *
     * @return bool
     */
    protected function createTriggers(): bool
    {
        // get config
        $config = $this->getContainer()->get('config');

        if (!empty($config->get('PimTriggers')) || empty($config->get('isInstalled'))) {
            return false;
        }

        // prepare sql for creating product attribute values
        $insertSql
            = "UPDATE product_attribute_value SET product_family_id=NEW.product_family_id WHERE attribute_id=NEW.attribute_id AND product_family_id IS NULL AND product_id IN (SELECT id AS product_id FROM product WHERE product_family_id=NEW.product_family_id AND deleted = 0);
               INSERT INTO product_attribute_value (id, product_id, attribute_id, product_family_id)
                SELECT 
                      UUID_SHORT(), id AS product_id, NEW.attribute_id, NEW.product_family_id
                FROM product
                WHERE 
                     product_family_id = NEW.product_family_id
                 AND deleted = 0
                 AND id NOT IN (SELECT product_id FROM product_attribute_value WHERE product_family_id=NEW.product_family_id AND attribute_id=NEW.attribute_id AND deleted=0);";

        // create product attribute values when attribute linked to product family
        $sql
            = "DROP TRIGGER IF EXISTS trigger_after_insert_product_family_attribute_linker;
               CREATE TRIGGER trigger_after_insert_product_family_attribute_linker
               AFTER INSERT ON product_family_attribute_linker
               FOR EACH ROW
                BEGIN
                 $insertSql
                END;";

        // delete product attribute values when attribute unlinked from product family and create product attribute values when attribute relinked again to product family
        $sql
            .= "DROP TRIGGER IF EXISTS trigger_after_update_product_family_attribute_linker;
                CREATE TRIGGER trigger_after_update_product_family_attribute_linker
                 AFTER UPDATE ON product_family_attribute_linker
                 FOR EACH ROW
                  BEGIN
                   IF (NEW.deleted <> OLD.deleted and NEW.deleted=1) THEN
                     DELETE FROM product_attribute_value WHERE product_family_id=OLD.product_family_id AND attribute_id=OLD.attribute_id ;
                   END IF;
                   IF (NEW.deleted <> OLD.deleted and NEW.deleted=0) THEN
                     $insertSql
                   END IF;
                  END;";

        // create in product attribute values when product is creating
        $sql
            .= "DROP TRIGGER IF EXISTS trigger_after_insert_product;
                CREATE TRIGGER trigger_after_insert_product
                 AFTER INSERT ON product
                 FOR EACH ROW
                  BEGIN
                   IF (NEW.product_family_id IS NOT NULL) THEN
                     INSERT INTO product_attribute_value (id, product_id, attribute_id, product_family_id)
                     SELECT
                       UUID_SHORT(), NEW.id, attribute_id, NEW.product_family_id
                     FROM product_family_attribute_linker
                     WHERE
                         product_family_id = NEW.product_family_id
                     AND deleted = 0;
                   END IF;
                  END;";

        // create, update, delete records from product attribute values when product family is changed
        $sql
            .= "DROP TRIGGER IF EXISTS trigger_after_update_product;
                CREATE TRIGGER trigger_after_update_product
                 AFTER UPDATE ON product
                 FOR EACH ROW
                  BEGIN
                   IF (NEW.product_family_id IS NULL) THEN
                    DELETE FROM product_attribute_value WHERE product_id=OLD.id AND product_family_id=OLD.product_family_id;
                   END IF;
                   IF (NEW.product_family_id <> OLD.product_family_id) THEN
                     UPDATE product_attribute_value SET product_family_id=NEW.product_family_id WHERE product_id=OLD.id AND attribute_id IN (SELECT attribute_id FROM product_family_attribute_linker WHERE product_family_id=NEW.product_family_id AND deleted=0);
                     DELETE FROM product_attribute_value WHERE product_id=OLD.id AND product_family_id IS NOT NULL AND attribute_id NOT IN (SELECT attribute_id FROM product_family_attribute_linker WHERE product_family_id=NEW.product_family_id AND deleted=0);
                     INSERT INTO product_attribute_value (id, product_id, attribute_id, product_family_id)
                     SELECT
                      UUID_SHORT(), OLD.id, attribute_id, NEW.product_family_id
                     FROM product_family_attribute_linker
                     WHERE
                         product_family_id = NEW.product_family_id
                     AND deleted = 0
                     AND attribute_id NOT IN (SELECT attribute_id FROM product_attribute_value WHERE product_id=OLD.id AND deleted=0);
                   END IF;
                  END;";

        // execute
        $sth = $this->getContainer()->get('entityManager')->getPDO()->prepare($sql);
        $sth->execute();

        // save to config
        $config->set('PimTriggers', true);
        $config->save();

        return true;
    }
}
