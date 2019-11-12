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
 * Class PimImage
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class PimImage extends AbstractSelectManager
{
    /**
     * @inheritDoc
     */
    public function applyAdditional(array &$result, array $params)
    {
        // prepare product types
        $types = implode("','", array_keys($this->getMetadata()->get('pim.productType', [])));

        // add filtering by product types
        $result['customWhere'] .= " AND (pim_image.category_id IS NOT NULL OR (pim_image.product_id IS NOT NULL AND pim_image.product_id IN (SELECT id FROM product WHERE type IN ('$types') AND deleted=0)))";
    }

    /**
     * @param array $result
     */
    protected function boolFilterPimImageRelation(array &$result)
    {
        foreach ($this->getSelectData('where') as $row) {
            if ($row['type'] == 'bool' && !empty($row['data']['pimImageRelation'])) {
                // prepare field
                $field = lcfirst($row['data']['pimImageRelation']['scope']) . 'Id';

                // prepare id
                $id = (string)$row['data']['pimImageRelation']['id'];

                // prepare repository
                $repository = $this
                    ->getEntityManager()
                    ->getRepository('PimImage');

                // get linked images
                $linked = $repository
                    ->select(['imageId'])
                    ->distinct()
                    ->where([$field => $id])
                    ->find()
                    ->toArray();

                // get data
                $data = $repository
                    ->select(['id', 'imageId'])
                    ->where(['imageId!=' => array_column($linked, 'imageId')])
                    ->find()
                    ->toArray();

                // prepare ids
                $ids = array_values(array_column($data, 'id', 'imageId'));

                // prepare where clause
                $result['whereClause'][] = [
                    'id' => $ids
                ];
            }
        }
    }
}
