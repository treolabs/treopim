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

namespace Espo\Modules\Pim\Metadata;

use Treo\Metadata\AbstractMetadata;

/**
 * Metadata
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Metadata extends AbstractMetadata
{

    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    public function modify(array $data): array
    {
        // push pim menu items
        $this->pushPimMenuItems();

        return $data;
    }

    /**
     * @return bool
     */
    protected function pushPimMenuItems(): bool
    {
        // get config
        $config = $this->getContainer()->get('config');

        if (empty($config->get('isPimMenuPushed'))) {
            // prepare items
            $items = [
                'Association',
                'Attribute',
                'AttributeGroup',
                'Brand',
                'Category',
                'Catalog',
                'Channel',
                'Product',
                'ProductFamily'
            ];

            // get config data
            $tabList = $config->get("tabList", []);
            $quickCreateList = $config->get("quickCreateList", []);
            $twoLevelTabList = $config->get("twoLevelTabList", []);

            foreach ($items as $item) {
                if (!in_array($item, $tabList)) {
                    $tabList[] = $item;
                }
                if (!in_array($item, $quickCreateList)) {
                    $quickCreateList[] = $item;
                }
                if (!in_array($item, $twoLevelTabList)) {
                    $twoLevelTabList[] = $item;
                }
            }

            // set to config
            $config->set('tabList', $tabList);
            $config->set('quickCreateList', $quickCreateList);
            $config->set('twoLevelTabList', $twoLevelTabList);
            if ($config->get('applicationName') == 'TreoCore') {
                $config->set('applicationName', 'TreoPIM');
            }

            // set flag
            $config->set('isPimMenuPushed', true);

            // save
            $config->save();
        }

        return true;
    }
}
