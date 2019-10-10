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

namespace Pim\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\Utils\Util;
use Treo\Core\EventManager\Event;

/**
 * Class ProductController
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterActionListLinked(Event $event)
    {
        $params = $event->getArgument('params');

        if ($params['link'] == 'productAttributeValues') {
            $fields = ['typeValue'];
            if ($this->getConfig()->get('isMultilangActive')) {
                foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                    $fields[] = Util::toCamelCase('typeValue_' . strtolower($locale));
                }
            }

            $attributes = $this
                ->getEntityManager()
                ->getRepository('Attribute')
                ->distinct()
                ->join(['productAttributeValues'])
                ->where([
                    'productAttributeValues.productId' => $params['id']
                ])
                ->find()
                ->toArray();

            $result = $event->getArgument('result');

            foreach ($result['list'] as $key => $item) {
                foreach ($attributes as $attribute) {
                    if ($attribute['id'] == $item->attributeId) {
                        foreach ($fields as $field) {
                            $result['list'][$key]->$field = $attribute[$field];
                        }
                    }
                }
            }

            $event->setArgument('result', $result);
        }
    }
}
