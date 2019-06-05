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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Treo\Listeners\AbstractListener;
use Espo\Core\Utils\Util;
use Treo\Core\EventManager\Event;

/**
 * Class ProductAttributeValueController
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductAttributeValueController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionRead(Event $event)
    {
        // get data
        $data = $event->getArguments();

        if (isset($data['result']->attributeId)) {
            $attribute = $this->getEntityManager()->getEntity('Attribute', $data['result']->attributeId);

            if (!empty($attribute)) {
                $data['result']->typeValue = $attribute->get('typeValue');

                // for multiLang fields
                if ($this->getConfig()->get('isMultilangActive')) {
                    foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                        $multiLangField = Util::toCamelCase('typeValue_' . strtolower($locale));
                        $data['result']->$multiLangField = $attribute->get($multiLangField);
                    }
                }
            }

            // set data
            $event->setArgument('result', $data['result']);
        }
    }

    /**
     * @param Event $event
     */
    public function beforeActionCreate(Event $event)
    {
        // get data
        $data = $event->getArguments();

        if (is_array($data['data']->value)) {
            $data['data']->value = Json::encode($data['data']->value);
        }

        // for multiLang fields
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                $multiLangField = Util::toCamelCase('value_' . strtolower($locale));
                if (isset($data['data']->$multiLangField) && is_array($data['data']->$multiLangField)) {
                    $data['data']->$multiLangField = Json::encode($data['data']->$multiLangField);
                }
            }
        }

        // set data
        $event->setArgument('result', $data['result']);
    }

    /**
     * @param Event $event
     *
     * @throws BadRequest
     * @throws Error
     */
    public function beforeActionUpdate(Event $event)
    {
        // get data
        $data = $event->getArguments();

        if (isset($data['params']['id']) && isset($data['data']->productId)) {
            $productAttribute = $this->getEntityManager()->getEntity('ProductAttributeValue', $data['params']['id']);

            // check is ProductFamily attribute
            if (!empty($productAttribute->get('productFamilyAttributeId'))) {
                $message = $this
                    ->getLanguage()
                    ->translate(
                        'You can\'t change product in attribute from Product Family',
                        'exceptions',
                        'ProductAttributeValue'
                    );

                throw new BadRequest($message);
            }
        }
    }
}
