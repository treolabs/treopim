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

use Espo\ORM\Entity;

/**
 * Attribute service
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Attribute extends AbstractService
{
    /**
     * Get filters
     *
     * @return array
     */
    public function getFiltersData(): array
    {
        // prepare result
        $result = [];

        // get all attributes
        if (!empty($data = $this->getAttributesForFilter())) {
            // get multilang fields
            $multilangFields = $this->getMultilangFields();

            // prepare no family data
            $noFamilyData = [
                'id'   => 'all',
                'name' => $this->getTranslate('All', 'filterLabels', 'Attribute'),
                'rows' => []
            ];

            foreach ($data as $row) {
                // skip multilang types
                if (in_array($row['attributeType'], $multilangFields)) {
                    continue;
                }

                // prepare attribute typeValue param
                $attributeTypeValue = [];
                if (!empty($row['attributeTypeValue'])) {
                    $attributeTypeValue = json_decode($row['attributeTypeValue'], true);
                }

                if (!empty($row['productFamilyId'])) {
                    $result[$row['productFamilyId']]['id'] = $row['productFamilyId'];
                    $result[$row['productFamilyId']]['name'] = $row['productFamilyName'];
                    $result[$row['productFamilyId']]['rows'][$row['attributeId']] = [
                        'attributeId' => $row['attributeId'],
                        'name'        => $row['attributeName'],
                        'type'        => $row['attributeType'],
                        'typeValue'   => $attributeTypeValue
                    ];
                }

                // push to all
                $noFamilyData['rows'][$row['attributeId']] = [
                    'attributeId' => $row['attributeId'],
                    'name'        => $row['attributeName'],
                    'type'        => $row['attributeType'],
                    'typeValue'   => $attributeTypeValue
                ];
            }
            $noFamilyData['rows'] = array_values($noFamilyData['rows']);

            // prepare result
            $result = array_values($result);
            foreach ($result as $k => $v) {
                $result[$k]['rows'] = array_values($v['rows']);
            }

            // push no family to the end of result
            if (!empty($noFamilyData['rows'])) {
                $result[] = $noFamilyData;
            }
        }

        return $result;
    }

    /**
     * Update attributes sort order
     *
     * @param string $attributeGroupId
     * @param array  $data
     *
     * @return bool
     */
    public function updateSortOrder(string $attributeGroupId, array $data): bool
    {
        $result = false;

        if (!empty($data)) {
            $query = "UPDATE attribute SET sort_order='%s' WHERE attribute_group_id='%s' AND id='%s';";

            $sql = '';
            foreach ($data as $key => $attributeId) {
                $sql .= sprintf($query, $key, $attributeGroupId, $attributeId);
            }

            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();

            $result = true;
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getAttributesForFilter(): array
    {
        $sql
            = 'SELECT
                    pf.id        AS productFamilyId,
                    pf.name      AS productFamilyName,
                    a.id         AS attributeId,
                    a.name       AS attributeName,
                    a.type       AS attributeType,
                    a.type_value AS attributeTypeValue
               FROM attribute AS a
                    LEFT JOIN product_family_attribute AS pfa ON a.id = pfa.attribute_id AND pfa.deleted = 0
                    LEFT JOIN (
                        SELECT DISTINCT pf.id, pf.name
                        FROM product_family AS pf
                        JOIN product AS p ON p.product_family_id = pf.id AND p.deleted = 0
                        WHERE pf.deleted = 0
                    ) AS pf ON pf.id = pfa.product_family_id
               WHERE a.deleted=0
                    AND a.id IN (SELECT attribute_id FROM product_attribute_value WHERE deleted=0)';

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data)) ? $data : [];
    }

    /**
     * Get multilang fields
     *
     * @return array
     */
    protected function getMultilangFields(): array
    {
        // get config
        $config = $this->getConfig()->get('modules');

        return (!empty($config['multilangFields'])) ? array_keys($config['multilangFields']) : [];
    }

    /**
     * @param Entity $entity
     */
    protected function afterDeleteEntity(Entity $entity)
    {
        // call parent action
        parent::afterDeleteEntity($entity);

        // unlink
        $this->unlinkAttribute([$entity->get('id')]);
    }

    /**
     * @param array $idList
     */
    protected function afterMassRemove(array $idList)
    {
        // call parent action
        parent::afterMassRemove($idList);

        // unlink
        $this->unlinkAttribute($idList);
    }


    /**
     * Unlink attribute from ProductFamily and Product
     *
     * @param array $ids
     *
     * @return bool
     */
    protected function unlinkAttribute(array $ids): bool
    {
        // prepare data
        $result = false;

        if (!empty($ids)) {
            // remove from product families
            $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->where([
                    'attributeId' => $ids
                ])
                ->removeCollection();

            // remove from products
            $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where([
                    'attributeId' => $ids
                ])
                ->removeCollection();

            // prepare result
            $result = true;
        }

        return $result;
    }
}
