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

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;
use Treo\Listeners\AbstractListener;
use PDO;

/**
 * Attribute listener
 *
 * @author r.ratsun@treolabs.com
 */
class Attribute extends AbstractListener
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionDelete(array $data): array
    {
        if (empty($data['data']->force) && !empty($data['params']['id'])) {
            // get attribute
            $attribute = $this
                ->getEntityManager()
                ->getEntity('Attribute', $data['params']['id']);

            if ($this->hasProductFamily($attribute)) {
                throw new BadRequest(
                    $this->getLanguage()->translate(
                        'Attribute is used in product families. Please, update product families first',
                        'exceptions',
                        'Attribute'
                    )
                );
            }

            if ($this->hasProduct($attribute)) {
                throw new BadRequest(
                    $this->getLanguage()->translate(
                        'Attribute is used in products. Please, update products first',
                        'exceptions',
                        'Attribute'
                    )
                );
            }
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionMassDelete(array $data): array
    {
        if (empty($data['data']->force)) {
            throw new BadRequest(
                $this->getLanguage()->translate(
                    'Attribute is used in product families. Please, update product families first',
                    'exceptions',
                    'Attribute'
                )
            );
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function beforeActionListLinked(array $data): array
    {
        if ($data['params']['link'] == 'productFamilyAttributes') {
            // get where
            $where = $data['request']->get('where', []);

            // prepare where
            $where[] = [
                'type'      => 'notIn',
                'attribute' => 'productFamilyId',
                'value'     => $this->getDeletedProductFamiliesIds()
            ];

            // set where
            $data['request']->setQuery('where', $where);
        }

        return $data;
    }

    /**
     * Get ids of deleted product families
     *
     * @return array
     */
    protected function getDeletedProductFamiliesIds(): array
    {
        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare("SELECT id FROM product_family WHERE deleted = 1");
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);

        return (!empty($data)) ? array_column($data, 'id') : [];
    }


    /**
     * Is attribute used in product families
     *
     * @param Entity $entity
     *
     * @return bool
     */
    protected function hasProductFamily(Entity $entity): bool
    {
        // prepare attribute id
        $attributeId = $entity->get('id');

        $sql
            = "SELECT
                  COUNT(f.id) as total
                FROM product_family AS f
                  JOIN product_family_attribute_linker AS fa 
                    ON f.id = fa.product_family_id
                WHERE f.deleted = 0 AND fa.deleted = 0 AND fa.attribute_id = '{$attributeId}'";

        // execute
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        // get data
        $data = $sth->fetch(PDO::FETCH_ASSOC);

        return !empty($data['total']);
    }


    /**
     * Is attribute used in products
     *
     * @param Entity $entity
     *
     * @return bool
     */
    protected function hasProduct(Entity $entity): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('Attribute')
            ->findRelated($entity, 'products')
            ->count();

        return !empty($count);
    }
}
