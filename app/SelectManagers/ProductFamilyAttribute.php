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

namespace Pim\SelectManagers;

use Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class ProductFamilyAttribute
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductFamilyAttribute extends AbstractSelectManager
{
    /**
     * @param array $result
     */
    protected function boolFilterLinkedWithAttributeGroup(array &$result)
    {
        $data = (array)$this->getSelectCondition('linkedWithAttributeGroup');

        if (isset($data['productFamilyId'])) {
            // prepare data
            $ids = [$data['productFamilyId']];
            $attributeGroupId = ($data['attributeGroupId'] != '') ? $data['attributeGroupId'] : null;

            $result['whereClause'][] = [
                'id' => $this->getEntityManager()->getRepository('ProductFamily')->getLinkedWithAttributeGroup($ids, $attributeGroupId)
            ];
        }
    }
}
