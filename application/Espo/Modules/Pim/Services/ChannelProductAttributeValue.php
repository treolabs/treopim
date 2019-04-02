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

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;

/**
 * Service ChannelProductAttributeValue
 *
 * @author r.ratsun@treolabs.com
 */
class ChannelProductAttributeValue extends \Espo\Core\Templates\Services\Base
{
    /**
     * @inheritdoc
     */
    public function createEntity($data)
    {
        // prepare data
        $data = $this->prepareValues($data);

        return parent::createEntity($data);
    }

    /**
     * @inheritdoc
     */
    public function updateEntity($id, $data)
    {
        // prepare data
        $data = $this->prepareValues($data);

        return parent::updateEntity($id, $data);
    }

    /**
     * Prepare data values
     *
     * @param \stdClass $data
     *
     * @return \stdClass
     */
    protected function prepareValues(\stdClass $data): \stdClass
    {
        if (is_array($data->value)) {
            $data->value = Json::encode($data->value);
        }

        // for multilang
        if (!empty($languages = $this->getConfig()->get('inputLanguageList'))) {
            foreach ($languages as $language) {
                // prepare key
                $key = 'value' . Util::toCamelCase(strtolower($language), '_', true);

                if (isset($data->$key) && is_array($data->$key)) {
                    $data->$key = Json::encode($data->$key);
                }
            }
        }

        return $data;
    }
}
