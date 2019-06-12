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

/**
 * Migration class for version 2.12.1
 *
 * @author r.ratsun@treolabs.com
 */
class V2Dot12Dot1 extends \Treo\Core\Migration\AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $this->getConfig()->set('PimTriggers', false);
        $this->getConfig()->save();
    }
}
