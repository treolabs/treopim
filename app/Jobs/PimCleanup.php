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

namespace Pim\Jobs;

use Espo\Core\Jobs\Base;

/**
 * Class PimCleanup
 *
 * @author r.ratsun@treolabs.com
 */
class PimCleanup extends Base
{
    /**
     * Run job
     *
     * @return bool
     */
    public function run()
    {
        // association
        $this->execute("DELETE FROM association WHERE deleted=1");

        // attribute
        $ids = $this->fetchIds("SELECT id FROM attribute WHERE deleted=1");
        $this->execute("UPDATE product_attribute_value SET deleted=1 WHERE attribute_id IN ('$ids')");
        $this->execute("DELETE FROM attribute WHERE deleted=1");

        // attribute_group
        $this->execute("DELETE FROM attribute_group WHERE deleted=1");

        // brand
        $this->execute("DELETE FROM brand WHERE deleted=1");

        // catalog
        $this->execute("DELETE FROM catalog WHERE deleted=1");

        // category
        $this->execute("DELETE FROM category WHERE deleted=1");

        // channel
        $this->execute("DELETE FROM channel WHERE deleted=1");

        // country
        $this->execute("DELETE FROM country WHERE deleted=1");

        // measuring_unit
        $this->execute("DELETE FROM measuring_unit WHERE deleted=1");

        // packaging
        $this->execute("DELETE FROM packaging WHERE deleted=1");

        // product
        $ids = $this->fetchIds("SELECT id FROM product WHERE deleted=1");
        $this->execute("UPDATE product_attribute_value SET deleted=1 WHERE product_id IN ('$ids')");
        $this->execute("DELETE FROM product WHERE deleted=1");

        // product_attribute_value
        $ids = $this->fetchIds("SELECT id FROM product_attribute_value WHERE deleted=1 AND scope='Channel'");
        $this->execute("UPDATE product_attribute_value_channel SET deleted=1 WHERE product_attribute_value_id IN ('$ids')");
        $this->execute("DELETE FROM product_attribute_value WHERE deleted=1");

        // product_attribute_value_channel
        $this->execute("DELETE FROM product_attribute_value_channel WHERE deleted=1");

        // product_category
        $this->execute("DELETE FROM product_category WHERE deleted=1");
        $ids = $this->fetchIds(
            "SELECT pc.id FROM product_category pc LEFT JOIN product p ON p.id=pc.product_id LEFT JOIN category ca ON ca.id=pc.category_id WHERE p.id IS NULL OR ca.id IS NULL"
        );
        $this->execute("DELETE FROM product_category WHERE id IN ('$ids')");

        // product_category_channel
        $this->execute("DELETE FROM product_category_channel WHERE deleted=1");
        $ids = $this->fetchIds(
            "SELECT pcc.id FROM product_category_channel pcc LEFT JOIN product_category pc ON pc.id=pcc.product_category_id LEFT JOIN channel ch ON ch.id=pcc.channel_id WHERE pc.id IS NULL OR ch.id IS NULL"
        );
        $this->execute("DELETE FROM product_category_channel WHERE id IN ('$ids')");

        // product_family
        $this->execute("DELETE FROM product_family WHERE deleted=1");

        // product_family_attribute
        $this->execute("DELETE FROM product_family_attribute WHERE deleted=1");
        $ids = $this->fetchIds(
            "SELECT pfa.id FROM product_family_attribute pfa LEFT JOIN product_family pf ON pf.id=pfa.product_family_id LEFT JOIN attribute a ON a.id=pfa.attribute_id WHERE a.id IS NULL OR pf.id IS NULL"
        );
        $this->execute("DELETE FROM product_family_attribute WHERE id IN ('$ids')");

        // product_family_attribute_channel
        $this->execute("DELETE FROM product_family_attribute_channel WHERE deleted=1");
        $ids = $this->fetchIds(
            "SELECT pfac.id FROM product_family_attribute_channel pfac LEFT JOIN channel ch ON ch.id=pfac.channel_id LEFT JOIN product_family_attribute pfa ON pfa.id=pfac.product_family_attribute_id WHERE pfa.id IS NULL OR ch.id IS NULL"
        );
        $this->execute("DELETE FROM product_family_attribute_channel WHERE id IN ('$ids')");

        // associated_product
        $this->execute("DELETE FROM associated_product WHERE deleted=1");
        $ids = $this->fetchIds(
            "SELECT ap.id FROM associated_product ap LEFT JOIN product p1 ON p1.id=ap.main_product_id LEFT JOIN product p2 ON p2.id=ap.related_product_id LEFT JOIN association a ON a.id=ap.association_id WHERE a.id IS NULL OR p1.id IS NULL OR p2.id IS NULL"
        );
        $this->execute("DELETE FROM associated_product WHERE id IN ('$ids')");

        // product_serie
        $this->execute("DELETE FROM product_serie WHERE deleted=1");

        // tax
        $this->execute("DELETE FROM tax WHERE deleted=1");

        return true;
    }

    /**
     * @param string $sql
     *
     * @return \PDOStatement|null
     */
    protected function execute(string $sql): ?\PDOStatement
    {
        try {
            $statement = $this->getEntityManager()->nativeQuery($sql);
        } catch (\PDOException $e) {
            $GLOBALS['log']->error('PimCleanup: ' . $e->getMessage());
        }

        return (isset($statement)) ? $statement : null;
    }

    /**
     * @param string $sql
     *
     * @return string
     */
    protected function fetchIds(string $sql): string
    {
        // execute
        $statement = $this->execute($sql);

        return (!is_null($statement)) ? implode("','", array_column($statement->fetchAll(\PDO::FETCH_ASSOC), 'id')) : 'no-such-id';
    }
}
