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
 * Class ProductsByTagDashlet
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductsByTagDashlet extends AbstractProductDashletService
{
    /**
     * Int Class
     */
    public function init()
    {
        parent::init();

        $this->addDependency('metadata');
    }

    /**
     * Get Product types
     *
     * @return array
     * @throws \Espo\Core\Exceptions\Error
     */
    public function getDashlet(): array
    {
        $result = ['total' => 0, 'list' => []];

        // get tags
        $tags = $this->getInjection('metadata')->get('entityDefs.Product.fields.tag.options');
        $tags = is_array($tags) ? $tags : [];

        $result['total'] = count($tags);
        // prepare data
        foreach ($tags as $tag) {
            $where = [
                'tag*' => "%\"$tag\"%",
                'type' => $this->getProductTypes()
            ];

            $result['list'][] = [
                'id'     => $tag,
                'name'   => $tag,
                'amount' => $this->getRepository('Product')->where($where)->count()
            ];
        }

        return $result;
    }
}
