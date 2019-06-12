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

namespace Pim\Services;

use Espo\Core\Utils\Util;
use Slim\Http\Request;
use PDO;

/**
 * CategoryImage service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CategoryImage extends AbstractImageService
{

    /**
     * @var array
     */
    protected $linkSelectParams
        = [
            'categories' => [
                'additionalColumns' => [
                    'sortOrder' => 'sortOrder',
                ]
            ]
        ];

    /**
     * Update ProductImage channels
     *
     * @param string $categoryId
     * @param string $categoryImageId
     * @param array  $channels
     *
     * @return bool
     */
    public function updateCategoryImageChannels(string $categoryId, string $categoryImageId, $channels): bool
    {
        /**
         * Delete old records
         */
        $sql = "DELETE FROM category_image_channel WHERE category_image_id='%s' AND category_id='%s';";
        $sql = sprintf($sql, $categoryImageId, $categoryId);


        /**
         * Create new records
         */
        if (!empty($channels) && is_array($channels)) {
            $template = "INSERT INTO category_image_channel SET id='%s', category_image_id='%s', category_id='%s'";
            foreach ($channels as $channelId) {
                // prepare data
                $target = $template . ", channel_id='%s';";
                $id = Util::generateId();

                $sql .= sprintf($target, $id, $categoryImageId, $categoryId, $channelId);
            }
        }

        // execute
        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        return true;
    }

    /**
     * Get CategoryImage channels
     *
     * @param string  $categoryId
     * @param string  $categoryImageId
     * @param Request $request
     *
     * @return array
     */
    public function getCategoryImageChannels(string $categoryId, string $categoryImageId, Request $request): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        /**
         * Get from DB
         */
        $sql
            = "SELECT
                  pic.channel_id AS id,
                  c.name AS name
                FROM category_image_channel AS pic
                JOIN channel AS c ON c.id=pic.channel_id
                WHERE pic.deleted = 0
                      AND pic.category_id = '{$categoryId}'
                      AND pic.category_image_id = '{$categoryImageId}'";
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);

        // prepare result
        if (!empty($data)) {
            $result = [
                'total' => count($data),
                'list'  => $data
            ];
        }

        return $result;
    }

    /**
     * Update category image sort order
     *
     * @param string $categoryId
     * @param array  $data
     *
     * @return bool
     */
    public function updateSortOrder(string $categoryId, array $data): bool
    {
        // prepare data
        $result = false;

        if (!empty($data)) {
            $template
                = "UPDATE category_image_category SET sort_order = %s 
                      WHERE category_image_id = '%s' AND category_id = '%s';";
            $sql = '';
            foreach ($data as $k => $categoryImageId) {
                $sql .= sprintf($template, $k, $categoryImageId, $categoryId);
            }

            // update DB data
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }
}
