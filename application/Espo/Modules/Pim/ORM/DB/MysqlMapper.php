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

namespace Espo\Modules\Pim\ORM\DB;

use Espo\Core\Utils\Util;

/**
 * Class MysqlMapper
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class MysqlMapper extends \Treo\ORM\DB\MysqlMapper
{
    /**
     * @inheritdoc
     */
    protected function composeInsertQuery($table, $fields, $values)
    {
        if ($table == 'product_attribute_value') {
            // prepare data
            $fieldsParts = explode(", ", $fields);
            $valuesParts = explode(", ", $values);

            // set id
            if (!in_array('id', $fieldsParts) && !in_array('`id`', $fieldsParts)) {
                // prepare fields
                $fieldsParts[] = 'id';
                $fields = implode(", ", $fieldsParts);

                // prepare values
                $valuesParts[] = "'" . Util::generateId() . "'";
                $values = implode(", ", $valuesParts);
            }
        }

        return parent::composeInsertQuery($table, $fields, $values);
    }

    /**
     * @inheritdoc
     */
    protected function composeUpdateQuery($table, $set, $where)
    {
        if ($table == 'product_attribute_value') {
            // delete row
            if (strpos($set, 'deleted = 1') !== false || strpos($set, "`deleted` = '1'") !== false) {
                return "DELETE FROM `{$table}` WHERE {$where}";
            }
        }

        return parent::composeUpdateQuery($table, $set, $where);
    }
}
