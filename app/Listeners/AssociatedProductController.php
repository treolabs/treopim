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
use PDO;
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

        $images = $this->getDBAssociatedProductsMainImage($productIds);

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

    /**
     * Get product main image
     *
     * @param array $productIds
     *
     * @return array
     */
    protected function getDBAssociatedProductsMainImage(array $productIds): array
    {
        $result = [];
        $productIds = "'" . implode("','", $productIds) . "'";
        if (!empty($productIds)) {
            $sql
                = "SELECT
                       pip.product_id AS productId,
                       pi.type AS imageType,
                       pi.image_id AS imageId,
                       pi.image_link AS imageLink,
                       pip.sort_order
                    FROM product_image pi
                      JOIN product_image_product pip
                        ON pip.product_image_id = pi.id AND pip.deleted = 0 AND pip.id = (
                          SELECT id
                          FROM product_image_product
                          WHERE product_id = pip.product_id
                          ORDER BY sort_order, id
                          LIMIT 1
                        )
                    WHERE pip.product_id IN ({$productIds}) AND pi.deleted = 0";

            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();

            $result = $sth->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

            return is_array($result) ? $result : [];
        }

        return $result;
    }
}
