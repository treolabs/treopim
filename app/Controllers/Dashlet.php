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

namespace Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;
use Treo\Services\AbstractService;

/**
 * Class DashletController
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Dashlet extends AbstractController
{

    /**
     * Get dashlet
     *
     * @ApiDescription(description="Get Dashlet data")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Dashlet/{dashletName}")
     * @ApiParams(name="dashletName", type="string", is_required=1, description="Dashlet name")
     * @ApiReturn(sample="[{
     *     'total': 'integer',
     *     'list': 'array'
     * }]")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function actionGetDashlet($params, $data, Request $request): array
    {
        // is get?
        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        if (!empty($params['dashletName'])) {
            return $this->createDashletService($params['dashletName'])->getDashlet();
        }

        throw new Exceptions\Error();
    }

    /**
     * Create dashlet service
     *
     * @param string $dashletName
     *
     * @return AbstractService
     * @throws Exceptions\Error
     */
    protected function createDashletService(string $dashletName): AbstractService
    {
        $serviceName = ucfirst($dashletName) . 'Dashlet';

        $dashletService = $this->getServiceFactory()->create($serviceName);

        if (!method_exists($dashletService, 'getDashlet')) {
            $message = sprintf($this->translate('notDashletService'), $serviceName);

            throw new Exceptions\Error($message);
        }

        return $dashletService;
    }

    /**
     * Translate
     *
     * @param string $key
     *
     * @param string $category
     *
     * @return string
     */
    protected function translate(string $key, string $category = 'exceptions'): string
    {
        return $this->getContainer()->get('language')->translate($key, $category);
    }
}
