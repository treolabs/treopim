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

namespace Pim;

use DamCommon\Services\PimLayout;
use Treo\Core\ModuleManager\AbstractEvent;

/**
 * Class Event
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Event extends AbstractEvent
{
    /**
     * @var array
     */
    protected $searchEntities
        = [
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

    /**
     * @var array
     */
    protected $menuItems
        = [
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

    /**
     * @inheritdoc
     */
    public function afterInstall(): void
    {
        // add global search
        $this->addGlobalSearchEntities();

        // add menu items
        $this->addMenuItems();

        //for Dam && PIM
        (new PimLayout($this->getContainer()))->modify();
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        // delete global search
        $this->deleteGlobalSearchEntities();

        // delete menu items
        $this->deleteMenuItems();
    }

    /**
     * Add global search entities
     */
    protected function addGlobalSearchEntities(): void
    {
        // get config
        $config = $this->getContainer()->get('config');

        // get config data
        $globalSearchEntityList = $config->get("globalSearchEntityList", []);

        foreach ($this->searchEntities as $entity) {
            if (!in_array($entity, $globalSearchEntityList)) {
                $globalSearchEntityList[] = $entity;
            }
        }

        // set to config
        $config->set('globalSearchEntityList', $globalSearchEntityList);

        // save
        $config->save();
    }

    /**
     * Delete global search entities
     */
    protected function deleteGlobalSearchEntities(): void
    {
        // get config
        $config = $this->getContainer()->get('config');

        $globalSearchEntityList = [];
        foreach ($config->get("globalSearchEntityList", []) as $entity) {
            if (!in_array($entity, $this->searchEntities)) {
                $globalSearchEntityList[] = $entity;
            }
        }

        // set to config
        $config->set('globalSearchEntityList', $globalSearchEntityList);

        // save
        $config->save();
    }


    /**
     * Add menu items
     */
    protected function addMenuItems()
    {
        // get config
        $config = $this->getContainer()->get('config');

        // get config data
        $tabList = $config->get("tabList", []);
        $quickCreateList = $config->get("quickCreateList", []);
        $twoLevelTabList = $config->get("twoLevelTabList", []);

        foreach ($this->menuItems as $item) {
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

        // save
        $config->save();
    }

    /**
     * Delete menu items
     */
    protected function deleteMenuItems()
    {
        // get config
        $config = $this->getContainer()->get('config');

        // for tabList
        $tabList = [];
        foreach ($config->get("tabList", []) as $entity) {
            if (!in_array($entity, $this->menuItems)) {
                $tabList[] = $entity;
            }
        }
        $config->set('tabList', $tabList);

        // for quickCreateList
        $quickCreateList = [];
        foreach ($config->get("quickCreateList", []) as $entity) {
            if (!in_array($entity, $this->menuItems)) {
                $quickCreateList[] = $entity;
            }
        }
        $config->set('quickCreateList', $quickCreateList);

        // for twoLevelTabList
        $twoLevelTabList = [];
        foreach ($config->get("twoLevelTabList", []) as $entity) {
            if (!in_array($entity, $this->menuItems)) {
                $twoLevelTabList[] = $entity;
            }
        }
        $config->set('twoLevelTabList', $twoLevelTabList);

        // save
        $config->save();
    }
}
