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

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Templates\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * AbstractPimImage class
 *
 * @author r.ratsun@treolabs.com
 */
abstract class AbstractPimImage extends Base
{
    /**
     * Get image entity name
     *
     * @return string
     */
    abstract protected function getEntityName(): string;

    /**
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function actionListImageChannels($params, $data, Request $request): array
    {
        // is get?
        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        // is granted?
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Exceptions\Forbidden();
        }

        // prepare method
        $method = "get" . $this->getEntityName() . "Channels";

        return $this
            ->getRecordService()
            ->{$method}(
                $params['entityId'], $params['entityImageId'], $request
            );
    }

    /**
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function actionUpdateImageChannels($params, $data, Request $request): bool
    {
        // is put?
        if (!$request->isPut()) {
            throw new Exceptions\BadRequest();
        }

        // is granted?
        if (!$this->getAcl()->check($this->name, 'edit')) {
            throw new Exceptions\Forbidden();
        }

        // prepare method
        $method = "update" . $this->getEntityName() . "Channels";

        return $this
            ->getRecordService()
            ->{$method}(
                $params['entityId'], $params['entityImageId'], $data
            );
    }

    /**
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\Error
     */
    public function actionUpdateSortOrder($params, $data, Request $request): bool
    {
        // is put?
        if (!$request->isPut()) {
            throw new Exceptions\BadRequest();
        }

        // is granted?
        if (!$this->getAcl()->check($this->name, 'edit')) {
            throw new Exceptions\Forbidden();
        }

        return $this
            ->getRecordService()
            ->updateSortOrder($params['entityId'], $data->ids);
    }
}
