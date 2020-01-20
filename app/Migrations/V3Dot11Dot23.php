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
 * Migration class for version 3.11.23
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class V3Dot11Dot23 extends Base
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->exec("ALTER TABLE `channel` ADD locales MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->exec("ALTER TABLE `channel` DROP locales");
    }

    /**
     * @param string $sql
     *
     * @return void
     */
    private function exec(string $sql): void
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\PDOException $e) {
            $GLOBALS['log']->error('Migration of PIM (3.11.23): ' . $sql . ' | ' . $e->getMessage());
        }
    }
}
