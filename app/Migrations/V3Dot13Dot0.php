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

namespace Pim\Migrations;

use Treo\Core\Migration\Base;

/**
 * Migration class for version 3.13.0
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot13Dot0 extends Base
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        $this->exec("ALTER TABLE attribute ADD locale VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("ALTER TABLE attribute ADD parent_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("ALTER TABLE attribute DROP is_system");
        $this->exec("CREATE INDEX IDX_PARENT_ID ON attribute (parent_id)");
        $this->exec("ALTER TABLE `product_attribute_value` DROP name");
        $this->exec("DROP INDEX IDX_NAME ON `product_attribute_value`");
        $this->exec("ALTER TABLE `product_attribute_value` ADD attribute_type VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE_TYPE ON `product_attribute_value` (attribute_type, deleted)");
        $this->exec("CREATE INDEX IDX_PRODUCT ON `product_attribute_value` (product_id, deleted)");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE ON `product_attribute_value` (attribute_id, deleted)");
        $this->exec("CREATE INDEX IDX_SCOPE ON `product_attribute_value` (scope, deleted)");
    }

    /**
     * @param string $sql
     */
    protected function exec(string $sql): void
    {
        echo $sql . PHP_EOL;
        try {
            $this->getPDO()->exec($sql);
        } catch (\PDOException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}
