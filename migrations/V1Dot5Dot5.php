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

use Treo\Core\Migration\AbstractMigration;

/**
 * Class V1Dot5Dot5
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class V1Dot5Dot5 extends AbstractMigration
{

    /**
     * @var array
     */
    protected $updateData = [];

    /**
     * Up to current
     */
    public function up(): void
    {
        $channels = $this->getEntityManager()->getRepository('Channel')->find();

        // Prepare code for channels
        foreach ($channels as $channel) {
            $this->prepareCode($channel->get('id'), $channel->get('name'));
        }

        // prepare update query
        $update = "UPDATE channel SET `code` = '%s' WHERE id = '%s';";
        $sql = '';
        foreach ($this->updateData as $code => $id) {
            $sql .= sprintf($update, $code, $id);
        }

        // execute query
        if (!empty($sql)) {
            /** @var \PDOStatement $sth */
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();
        }
    }

    /**
     *  Set code to channels
     *
     * @param string $id
     * @param string $code
     * @param int    $number
     */
    protected function prepareCode(string $id, string $code, $number = 0)
    {
        $newCode = $number === 0 ? $code : $code . ' ' . $number;

        if (empty($this->updateData[$newCode])) {
            $this->updateData[$newCode] = $id;
        } else {
            $this->prepareCode($id, $code, ++$number);
        }
    }
}
