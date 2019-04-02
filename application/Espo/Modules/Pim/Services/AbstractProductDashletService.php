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
 * Class AbstractProductDashletService
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractProductDashletService extends AbstractDashletService
{
    /**
     * Product types
     *
     * @var array
     */
    protected $productTypes = [];

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('metadata');
    }

    /**
     * Get product types for query
     *
     * @return string
     */
    protected function getProductTypesCondition(): string
    {
        $result = "('" . implode("','", $this->getProductTypes()) . "')";

        return $result;
    }


    /**
     * Get product types
     *
     * @return array
     */
    protected function getProductTypes(): array
    {
        if (empty($this->productTypes)) {
            $this->productTypes = array_keys($this->getInjection('metadata')->get('pim.productType'));
        }

        return $this->productTypes;
    }
}
