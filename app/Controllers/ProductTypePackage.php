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

declare(strict_types = 1);

namespace Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;
use Espo\Core\Utils\Json;

/**
 * ProductTypePackage controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductTypePackage extends AbstractProductTypeController
{

    /**
     * @ApiDescription(description="Get package product")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/ProductTypePackage/{productId}/view")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product ID")
     * @ApiReturn(sample="'array'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionView($params, $data, Request $request): array
    {
        if (!$this->getAcl()->check('Product', 'read')) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('ProductTypePackage')->getPackageProduct($params['entity_id']);
    }

    /**
     * @ApiDescription(description="Update package product")
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/Markets/ProductTypePackage/{productId}/update")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product ID")
     * @ApiParams(name="measuringUnitId", type="string", is_required=1, description="Price Unit ID")
     * @ApiParams(name="content", type="string", is_required=1, description="Content")
     * @ApiParams(name="basicUnit", type="string", is_required=1, description="Basic Unit")
     * @ApiParams(name="packingUnit", type="string", is_required=0, description="Packing Unit")
     * @ApiReturn(sample="'bool'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Error
     * @throws Exceptions\Forbidden
     */
    public function actionUpdate($params, $data, Request $request): bool
    {
        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!$request->isPut() && !$request->isPatch()) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        if (!empty($data['measuringUnitId']) && !empty($data['content']) && !empty($data['basicUnit'])) {
            return $this->getService('ProductTypePackage')->update($params['entity_id'], $data);
        }

        throw new Exceptions\Error();
    }
}
