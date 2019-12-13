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

namespace Pim\Listeners;

use Espo\Core\Utils\Json;
use Treo\Core\EventManager\Event;
use Treo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * Class LayoutController
 *
 * @author r.ratsun@treolabs.com
 */
class LayoutController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionRead(Event $event)
    {
        /** @var string $scope */
        $scope = $event->getArgument('params')['scope'];

        /** @var string $name */
        $name = $event->getArgument('params')['name'];

        /** @var bool $isAdminPage */
        $isAdminPage = $event->getArgument('request')->get('isAdminPage') === 'true';

        $method = 'modify' . $scope . ucfirst($name);
        if (!$isAdminPage && method_exists($this, $method)) {
            $this->{$method}($event);
        }
    }

    /**
     * @param Event $event
     */
    protected function modifyAttributeDetail(Event $event)
    {
        /** @var array $result */
        $result = Json::decode($event->getArgument('result'), true);

        $result[0]['rows'][] = [['name' => 'isMultilang', 'inlineEditDisabled' => true], false];
        $result[0]['rows'][] = [['name' => 'name'], ['name' => 'typeValue']];

        foreach ($this->getInputLanguageList() as $locale => $key) {
            $result[0]['rows'][] = [['name' => 'name' . $key], ['name' => 'typeValue' . $key]];
        }

        $event->setArgument('result', Json::encode($result));
    }

    /**
     * @param Event $event
     */
    protected function modifyAttributeDetailSmall(Event $event)
    {
        $this->modifyAttributeDetail($event);
    }

    /**
     * @param Event $event
     */
    protected function modifyProductAttributeValueDetailSmall(Event $event)
    {
        /** @var array $result */
        $result = Json::decode($event->getArgument('result'), true);

        foreach ($this->getInputLanguageList() as $locale => $key) {
            $result[0]['rows'][] = [['name' => 'value' . $key], false];
        }

        $event->setArgument('result', Json::encode($result));
    }

    /**
     * @param Event $event
     */
    protected function modifyProductRelationships(Event $event)
    {
        /** @var array $result */
        $result = Json::decode($event->getArgument('result'), true);
        if (!empty($this->getMetadata()->get('entityDefs.Product.links.assets'))) {
            $result[] = 'asset_relations';
        }
        $event->setArgument('result', Json::encode($result));
    }

    /**
     * @param Event $event
     */
    protected function modifyCategoryRelationships(Event $event)
    {
        /** @var array $result */
        $result = Json::decode($event->getArgument('result'), true);
        if (!empty($this->getMetadata()->get('entityDefs.Category.links.assets'))) {
            $result[] = 'asset_relations';
        }
        $event->setArgument('result', Json::encode($result));
    }


    /**
     * @param Event $event
     */
    protected function modifyCategoryList(Event $event)
    {
        /** @var array $result */
        $result = Json::decode($event->getArgument('result'), true);
        if (!empty($this->getMetadata()->get('entityDefs.Category.fields.image'))) {
            $first = array_shift($result);
            array_unshift($result, $first, ['name' => 'image']);
        }
        $event->setArgument('result', Json::encode($result));
    }

    /**
     * @param Event $event
     */
    protected function modifyAssociatedProductListSmall(Event $event)
    {
        /** @var array $result */
        $result = Json::decode($event->getArgument('result'), true);
        if (!empty($this->getMetadata()->get('entityDefs.AssociatedProduct.fields.relatedProductImage'))) {
            $first = array_shift($result);
            array_unshift($result, $first, ['name' => 'relatedProductImage']);
        }
        $event->setArgument('result', Json::encode($result));
    }

    /**
     * @param Event $event
     */
    protected function modifyAssociatedProductList(Event $event)
    {
        // for DAM and Images
        if (!empty($this->getMetadata()->get('entityDefs.AssociatedProduct.fields.mainProductImage'))) {
            $columns = Json::decode($event->getArgument('result'), true);
            $images = [
                'mainProduct' => ['name' => 'mainProductImage', 'notSortable' => true],
                'relatedProduct' => ['name' => 'relatedProductImage', 'notSortable' => true]
            ];
            for ($k = 0; $k < count($columns); $k++) {
                if (!empty($images[$columns[$k]['name']])) {
                    //put new row
                    array_splice($columns, $k, 0, [$images[$columns[$k]['name']]]);
                    //skip next row
                    $k++;
                }
            }
            $event->setArgument('result', Json::encode($columns));
        }
    }

    /**
     * @param Event $event
     */
    public function modifyAssociatedProductDetail(Event $event)
    {
        // for DAM and Images
        if (!empty($this->getMetadata()->get('entityDefs.AssociatedProduct.fields.mainProductImage'))) {
            $result = Json::decode($event->getArgument('result'), true);
            $result[0]['rows'][] = [
                [
                    "name" => "mainProductImage"
                ],
                [
                    "name" => "relatedProductImage"
                ]
            ];
            $event->setArgument('result', Json::encode($result));
        }
    }

    /**
     * @param Event $event
     */
    public function modifyAssociatedProductDetailSmall(Event $event)
    {
        // for DAM and Images
       $this->modifyAssociatedProductDetail($event);
    }

    /**
     * @return array
     */
    protected function getInputLanguageList(): array
    {
        $result = [];
        if ($this->getConfig()->get('isMultilangActive', false)) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $result[$locale] = ucfirst(Util::toCamelCase(strtolower($locale)));
            }
        }

        return $result;
    }
}
