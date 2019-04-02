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

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ProductImage controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductImage extends AbstractPimImage
{
    /**
     * @ApiDescription(description="Get list of product image channels")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="ProductImage/{productImageId}/channels/{productId}")
     * @ApiParams(name="productImageId", type="string", is_required=1, description="Product image id")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="{
     *     'total': 'integer',
     *     'list': 'array'
     * }")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function actionListImageChannels($params, $data, Request $request): array
    {
        return parent::actionListImageChannels($params, $data, $request);
    }

    /**
     * @ApiDescription(description="Update product image channels")*
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="ProductImage/{productImageId}/channels/{productId}")
     * @ApiParams(name="productImageId", type="string", is_required=1, description="Product image id")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product id")
     * @ApiBody(sample="'array'")
     * @ApiReturn(sample="'bool'")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function actionUpdateImageChannels($params, $data, Request $request): bool
    {
        return parent::actionUpdateImageChannels($params, $data, $request);
    }

    /**
     * @ApiDescription(description="Update sort order")*
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="ProductImage/{productId}/sortOrder")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product id")
     * @ApiBody(sample="{
     *     'ids': 'array'
     * }")
     * @ApiReturn(sample="'bool'")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function actionUpdateSortOrder($params, $data, Request $request): bool
    {
        return parent::actionUpdateSortOrder($params, $data, $request);
    }

    /**
     * Get image entity name
     *
     * @return string
     */
    protected function getEntityName(): string
    {
        return 'ProductImage';
    }
}
