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

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class AssociatedProductController
 *
 * @author r.zablodskiy@treolabs.com
 */
class AssociatedProductController extends AbstractListener
{
    /**
     * After action list
     *
     * @param Event $event
     */
    public function afterActionList(Event $event)
    {
        $result = $event->getArgument('result');
        $result['list'] = $this->setAssociatedProductsImage((array)$result['list']);
        $event->setArgument('result', $result);
    }

    /**
     * After action read
     *
     * @param Event $event
     */
    public function afterActionRead(Event $event)
    {
        $event->setArgument('result', $this->setAssociatedProductsImage((array)$event->getArgument('result')));
    }

    /**
     * Set main images for associated products
     *
     * @param array $result
     *
     * @return \stdClass
     */
    protected function setAssociatedProductsImage(array $result): array
    {
        $productIds = [];
        foreach ($result as $item) {
            if (isset($item->{'mainProductId'}) && !in_array($item->{'mainProductId'}, $productIds)) {
                $productIds[] = $item->{'mainProductId'};
            }

            if (isset($item->{'relatedProductId'}) && !in_array($item->{'relatedProductId'}, $productIds)) {
                $productIds[] = $item->{'relatedProductId'};
            }
        }

        $images = $this
            ->getService('Product')
            ->getDBAssociatedProductsMainImage($productIds);

        foreach ($result as $key => $item) {
            if ($images[$item->mainProductId]) {
                $result[$key]->{'mainProductImageId'} = !empty($images[$item->mainProductId]['imageId'])
                    ? $images[$item->mainProductId]['imageId'] : null;
                $result[$key]->{'mainProductImageLink'} = !empty($images[$item->mainProductId]['imageLink'])
                    ? $images[$item->mainProductId]['imageLink'] : null;
            }

            if ($images[$item->relatedProductId]) {
                $result[$key]->{'relatedProductImageId'} = !empty($images[$item->relatedProductId]['imageId'])
                    ? $images[$item->relatedProductId]['imageId'] : null;
                $result[$key]->{'relatedProductImageLink'} = !empty($images[$item->relatedProductId]['imageLink'])
                    ? $images[$item->relatedProductId]['imageLink'] : null;

            }
        }

        return $result;
    }
}
