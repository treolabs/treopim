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

namespace Espo\Modules\Pim\Services;

/**
 * Class ProductFamily
 *
 * @author r.ratsun@treolabs.com
 */
class ProductFamily extends \Espo\Core\Templates\Services\Base
{
    /**
     * Get count not empty product family attributes
     *
     * @param string $productFamilyId
     * @param string $attributeId
     *
     * @return int
     */
    public function getLinkedProductAttributesCount(string $productFamilyId, string $attributeId): int
    {
        // prepare result
        $count = 0;

        // if not empty productFamilyId and attributeId
        if (!empty($productFamilyId) && !empty($attributeId)) {
            // get count products
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(
                    [
                        'productFamilyId' => $productFamilyId,
                        'attributeId'     => $attributeId,
                        'value!='         => ['null', '', 0, '0', '[]']
                    ]
                )
                ->count();
        }

        return $count;
    }
}
