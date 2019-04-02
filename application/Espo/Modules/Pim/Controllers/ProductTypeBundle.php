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

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;
use Espo\Core\Utils\Json;

/**
 * ProductTypeBundle controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductTypeBundle extends AbstractProductTypeController
{

    /**
     * @ApiDescription(description="Get bundle product")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/ProductTypeBundle/{bundleProductId}")
     * @ApiParams(name="bundleProductId", type="string", is_required=1, description="Bundle Product id")
     * @ApiReturn(sample="'array'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     * @throws Exceptions\NotFound
     */
    public function actionRead($params, $data, Request $request): array
    {
        if (!$this->getAcl()->check('Product', 'read')) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        $data = $this->getService('ProductTypeBundle')->getBundleProduct($params['id']);

        if (empty($data)) {
            throw new Exceptions\NotFound();
        }

        return $data;
    }

    /**
     * @ApiDescription(description="Update bundle product")
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/ProductTypeBundle/{bundleProductId}")
     * @ApiParams(name="bundleProductId", type="string", is_required=1, description="Bundle Product id")
     * @ApiReturn(sample="'array'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Error
     * @throws Exceptions\Forbidden
     */
    public function actionUpdate($params, $data, Request $request): array
    {
        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!$request->isPut() && !$request->isPatch()) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        if ($this->getService('ProductTypeBundle')->update($params['id'], $data)) {
            return $this->getService('ProductTypeBundle')->getBundleProduct($params['id']);
        }

        throw new Exceptions\Error();
    }

    /**
     * @ApiDescription(description="Get bundles product")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/ProductTypeBundle/{product_id}/bundleProduct")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="'array'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Error
     * @throws Exceptions\Forbidden
     */
    public function actionBundleProduct($params, $data, Request $request): array
    {
        if (!$this->getAcl()->check('Product', 'read')) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        if (!empty($productId = $params['entity_id'])) {
            return $this->getService('ProductTypeBundle')->getBundleProducts($productId);
        }

        throw new Exceptions\Error();
    }

    /**
     * @ApiDescription(description="Create bundles product")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ProductTypeBundle/action/create")
     * @ApiParams(name="bundleProductId", type="string", is_required=1, description="Bundle Product id")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product id")
     * @ApiParams(name="amount", type="float", is_required=1, description="Amount")
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
    public function actionCreate($params, $data, Request $request): bool
    {
        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!$this->getAcl()->check('Product', 'create')) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        if (!empty($data['bundleProductId']) && !empty($data['productId']) && !empty($data['amount'])) {
            return $this
                    ->getService('ProductTypeBundle')
                    ->create($data['bundleProductId'], $data['productId'], (float) $data['amount']);
        }

        throw new Exceptions\Error();
    }

    /**
     * @ApiDescription(description="Delete bundles product")
     * @ApiMethod(type="DELETE")
     * @ApiRoute(name="/ProductTypeBundle/{bundleProductId}/delete")
     * @ApiParams(name="bundleProductId", type="string", is_required=1, description="Bundle Product id")
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
    public function actionDelete($params, $data, Request $request): bool
    {
        if (!$this->getAcl()->check('Product', 'delete')) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isDelete()) {
            throw new Exceptions\BadRequest();
        }

        if (!empty($id = $params['entity_id'])) {
            return $this->getService('ProductTypeBundle')->delete($id);
        }

        throw new Exceptions\Error();
    }
}
