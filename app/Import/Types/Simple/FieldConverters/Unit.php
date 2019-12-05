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

namespace Pim\Import\Types\Simple\FieldConverters;
use Espo\ORM\Entity;
use Import\Types\Simple\FieldConverters\Unit as DefaultUnit;

/**
 * Class Unit
 *
 * @author r.zablodskiy@treolabs.com
 */
class Unit extends DefaultUnit
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        parent::convert($inputRow, $entityType, $config, $row, $delimiter);

        if (isset($config['attributeId'])) {
            // prepare input row for attribute
            $inputRow->data = (object)['unit' => $inputRow->{$config['name'] . 'Unit'}];
            unset($inputRow->{$config['name'] . 'Unit'});
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        parent::prepareValue($restore, $entity, $item);

        if (isset($config['attributeId'])) {
            // prepare restore row for attribute
            $restore->data = (object)['unit' => $restore->{$item['name'].'Unit'}];
            unset($restore->{$item['name'].'Unit'});
        }
    }
}
