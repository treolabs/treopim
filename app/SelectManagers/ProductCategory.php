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
 * Class ProductCategory
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductCategory extends AbstractSelectManager
{
    /**
     * @inheritdoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        // get select params
        $selectParams = parent::getSelectParams($params, $withAcl, $checkWherePermission);

        // prepare product types
        $types = implode("','", array_keys($this->getMetadata()->get('pim.productType', [])));

        // add filtering by product types
        $selectParams['customWhere'] .= " AND product_category.product_id IN (SELECT id FROM product WHERE type IN ('$types') AND deleted=0)";

        return $selectParams;
    }
}
