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

namespace Pim\Migrations;

use Treo\Core\Migration\AbstractMigration;
use Espo\Core\Utils\Json;

/**
 * Migration class for version 2.9.8
 *
 * @author r.zablodskiy@treolabs.com
 */
class V2Dot9Dot8 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $attributes = $this
            ->getEntityManager()
            ->getRepository('Attribute')
            ->select(['id'])
            ->where([
                'type' => 'text'
            ])
            ->find();

        if (count($attributes) > 0) {
            $notes = $this
                ->getEntityManager()
                ->getRepository('Note')
                ->where([
                    'attributeId' => array_column($attributes->toArray(), 'id')
                ])
                ->find();

            if (count($notes) > 0) {
                foreach ($notes as $note) {
                    if (!empty($note->get('data'))) {
                        $data = Json::decode(Json::encode($note->get('data')), true);

                        foreach ($data['fields'] as $field) {
                            $data['attributes']['was'][$field] = (string)$data['attributes']['was'][$field];
                        }

                        $note->set('data', Json::encode($data));
                        $this->getEntityManager()->saveEntity($note);
                    }
                }
            }
        }
    }
}
