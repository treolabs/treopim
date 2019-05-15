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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Espo\Modules\Pim\Listeners;

use Treo\Listeners\AbstractListener;

/**
 * Class ProductFamily
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductFamily extends AbstractListener
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function afterActionListLinked(array $data): array
    {
        if ($data['params']['link'] == 'productFamilyAttributes' && !empty($data['result']['list'])) {
            $attributes = $this
                ->getEntityManager()
                ->getRepository('Attribute')
                ->select(['id', 'attributeGroupId', 'attributeGroupName'])
                ->where([
                    'id' => array_column($data['result']['list'], 'attributeId')
                ])
                ->find();

            if (count($attributes) > 0) {
                foreach ($attributes as $attribute) {
                    foreach ($data['result']['list'] as $key => $item) {
                        if ($item->attributeId == $attribute->get('id')) {
                            // add to attribute group to result
                            $data['result']['list'][$key]->attributeGroupId = $attribute->get('attributeGroupId');
                            $data['result']['list'][$key]->attributeGroupName = $attribute->get('attributeGroupName');

                            // add sort order
                            $data['result']['list'][$key]->sortOrder = $attribute->get('sortOrder');
                        }
                    }
                }
            }
        }

        return $data;
    }
}
