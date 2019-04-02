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

namespace Espo\Modules\Pim\Listeners;

use Treo\Listeners\AbstractListener;

/**
 * Stream listener
 *
 * @author r.zablodskiy@treolabs.com
 */
class Stream extends AbstractListener
{
    /**
     * After action list
     *
     * @param array $data
     *
     * @return array
     */
    public function afterActionList(array $data): array
    {
        $data = $this->injectAttributeType($data);

        return $data;
    }

    /**
     * Inject attribute type in data
     *
     * @param array $data
     *
     * @return array
     */
    protected function injectAttributeType(array $data): array
    {
        if (isset($data['result']['list']) && is_array($data['result']['list'])) {
            // find attributes
            $attributes = $this->getEntityManager()
                ->getRepository('Attribute')
                ->select(['id', 'type'])
                ->where(['id' => array_column($data['result']['list'], 'attributeId')])
                ->find();

            if (!empty($attributes)) {
                $attributes = array_column($attributes->toArray(), 'type', 'id');

                foreach ($data['result']['list'] as $key => $item) {
                    if (isset($attributes[$item['attributeId']])) {
                        $data['result']['list'][$key]['attributeType'] = $attributes[$item['attributeId']];
                    }
                }
            }
        }

        return $data;
    }
}
