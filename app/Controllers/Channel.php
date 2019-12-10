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

namespace Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;
use Treo\Core\Utils\Util;

/**
 * Channel controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Channel extends AbstractController
{
    /**
     * @ApiDescription(description="Get channel")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Channel/{id}")
     * @ApiReturn(sample="'object'")
     *
     * @param $params
     * @param $data
     * @param $request
     *
     * @return mixed
     * @throws Exceptions\NotFound
     */
    public function actionRead($params, $data, $request)
    {
        // get parent
        $result = parent::actionRead($params, $data, $request);

        // prepare locales
        if (!empty($result->locales)) {
            $result->locales = $this
                ->getRecordService()
                ->prepareLocales($result->locales);
        }

        return $result;
    }

    /**
     * @ApiDescription(description="Get list of channels")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Channel")
     * @ApiReturn(sample="{
     * 'list': 'array',
     * 'total': 'int'
     * }")
     *
     * @param $params
     * @param $data
     * @param $request
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function actionList($params, $data, $request)
    {
        // get parent
        $result = parent::actionList($params, $data, $request);

        // prepare locales
        if ($result['total'] > 0) {
            foreach ($result['list'] as $k => $row) {
                if (!empty($row->locales)) {
                    $result['list'][$k]->locales = $this
                        ->getRecordService()
                        ->prepareLocales($row->locales);
                }
            }
        }

        return $result;
    }

    /**
     * Get channel product attributes action
     *
     * @ApiDescription(description="Get channel product attributes")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Channel/{channel_id}/Product/{product_id}/attributes")
     * @ApiParams(name="channel_id", type="string", is_required=1, description="Channel id")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="[{
     * 'productAttributeValueId': 'string',
     * 'attributeId': 'string',
     * 'name': 'string',
     * 'type': 'string',
     * 'isRequired': 'bool',
     * 'typeValue': [
     *   'string',
     *   'string',
     *   '...'
     * ],
     * 'typeValueEnUs': [
     *   'string',
     *   'string',
     *   '...'
     * ],
     * 'typeValue other languages ...': [],
     * 'value': 'array|string',
     * 'valueEnUs': 'array|string',
     * 'value other languages ...': 'array|string',
     * 'attributeGroupId': 'string',
     * 'attributeGroupName': 'string',
     * 'attributeGroupOrder': 'int',
     * 'isCustom': 'bool'
     * }]")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     */
    public function actionGetChannelProductAttributes($params, $data, Request $request)
    {
        if ($this->isReadAction($request)
            && $this->getAcl()->check('Attribute', 'read')
            && $this->getAcl()->check('Product', 'read')
        ) {
            return $this->getRecordService()->getChannelProductAttributes($params['channel_id'], $params['product_id']);
        }

        throw new Exceptions\Forbidden();
    }

    /**
     * Get products action
     *
     * @ApiDescription(description="Get products in channel")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Channel/{channel_id}/products")
     * @ApiParams(name="channel_id", type="string", is_required=1, description="Channel id")
     * @ApiReturn(sample="[{
     *     'channelProductId': 'string',
     *     'productId': 'string',
     *     'productName': 'string',
     *     'categories': [
     *          'string - categories name'
     *     ],
     *     'isActive': 'bool',
     *     'isEditable': 'bool'
     *},
     *     '...'
     *]")
     *
     * @param string $channelId
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function getProducts(string $channelId): array
    {
        if ($this->isReadEntity($this->name, $channelId)
            && $this->getAcl()->check('Product', 'read')
        ) {
            return $this->getRecordService()->getProducts($channelId);
        }

        throw new Exceptions\Forbidden();
    }

    /**
     * Set IsActiveEntity for link Channel to Product
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     */
    public function actionSetIsActiveEntity($params, $data, Request $request): bool
    {
        if (!$request->isPut() && !empty($data->entityName) && !empty($data->value) && !empty($data->entityId)) {
            throw new Exceptions\BadRequest();
        }
        $channelId = $params['channelId'];
        if (!$this->isReadEntity($this->name, $channelId) && !$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->setIsActiveEntity($channelId, $data);
    }
}
