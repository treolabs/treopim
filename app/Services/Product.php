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

use Espo\Core\Exceptions\Forbidden;
use Espo\ORM\Entity;
use Espo\Core\Utils\Util;

/**
 * Service of Product
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Product extends AbstractService
{
    /**
     * @param \stdClass $data
     *
     * @return bool
     */
    public function addAssociateProducts(\stdClass $data): bool
    {
        if (empty($data->ids)
            || empty($data->foreignIds)
            || empty($data->associationId)
            || !is_array($data->ids)
            || !is_array($data->foreignIds)
            || empty($association = $this->getEntityManager()->getEntity("Association", $data->associationId))) {
            return false;
        }

        // prepare repository
        $repository = $this->getEntityManager()->getRepository("AssociatedProduct");

        // find exists entities
        $entities = $repository->where(
            [
                'associationId'    => $data->associationId,
                'mainProductId'    => $data->ids,
                'relatedProductId' => $data->foreignIds
            ]
        )->find();

        // prepare exists
        $exists = [];
        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $exists[] = $entity->get("associationId") . "_" . $entity->get("mainProductId") .
                    "_" . $entity->get("relatedProductId");
            }
        }

        foreach ($data->ids as $mainProductId) {
            foreach ($data->foreignIds as $relatedProductId) {
                if (!in_array($data->associationId . "_{$mainProductId}_{$relatedProductId}", $exists)) {
                    $entity = $repository->get();
                    $entity->set("associationId", $data->associationId);
                    $entity->set("mainProductId", $mainProductId);
                    $entity->set("relatedProductId", $relatedProductId);

                    // for backward association
                    if (!empty($backwardAssociationId = $association->get('backwardAssociationId'))) {
                        $entity->set('backwardAssociationId', $backwardAssociationId);

                        $backwardEntity = $repository->get();
                        $backwardEntity->set("associationId", $backwardAssociationId);
                        $backwardEntity->set("mainProductId", $relatedProductId);
                        $backwardEntity->set("relatedProductId", $mainProductId);

                        $this->getEntityManager()->saveEntity($backwardEntity);
                    }

                    $this->getEntityManager()->saveEntity($entity);
                }
            }
        }

        return true;
    }

    /**
     * Remove product association
     *
     * @param \stdClass $data
     *
     * @return bool
     */
    public function removeAssociateProducts(\stdClass $data): bool
    {
        if (empty($data->ids) || empty($data->foreignIds) || empty($data->associationId)) {
            return false;
        }

        // find associated products
        $associatedProducts = $this
            ->getEntityManager()
            ->getRepository('AssociatedProduct')
            ->where(
                [
                    'associationId'    => $data->associationId,
                    'mainProductId'    => $data->ids,
                    'relatedProductId' => $data->foreignIds
                ]
            )
            ->find();

        if (count($associatedProducts) > 0) {
            foreach ($associatedProducts as $associatedProduct) {
                // for backward association
                if (!empty($backwardAssociationId = $associatedProduct->get('backwardAssociationId'))) {
                    $backwards = $associatedProduct->get('backwardAssociation')->get('associatedProducts');

                    if (count($backwards) > 0) {
                        foreach ($backwards as $backward) {
                            if ($backward->get('mainProductId') == $associatedProduct->get('relatedProductId')
                                && $backward->get('relatedProductId') == $associatedProduct->get('mainProductId')
                                && $backward->get('associationId') == $backwardAssociationId) {
                                $this->getEntityManager()->removeEntity($backward);
                            }
                        }
                    }
                }

                // remove associated product
                $this->getEntityManager()->removeEntity($associatedProduct);
            }
        }

        return true;
    }

    /**
     * @param array $where
     * @return array
     */
    public function getAttributesForMassUpdate(array $where): array
    {
        $where = json_decode(json_encode($where), true);
        $where[] = ['attribute' => 'scope', 'type' => 'equals', 'value' => 'Global'];

        $whereParams = $this
            ->getInjection('selectManagerFactory')
            ->create('ProductAttributeValue')
            ->getSelectParams(['where' => $where], true, true);

        $select = ['attributeId',
                    ['attribute.name', 'name'],
                    ['attribute.type', 'attributeType'],
                    ['attribute.isMultilang', 'attributeIsMultilang'],
                    ['attribute.typeValue', 'typeValue']
                ];

        $attributes = $this->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->select($select)
            ->leftJoin(['attribute'])
            ->groupBy(['product_attribute_value.attributeId'])
            ->find($whereParams)
            ->toArray();

        $result = [];

        foreach ($attributes as $attribute) {
            $result[$attribute['attributeId']] = $attribute;
        }

        return $result;
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateProductAttributeValues(Entity $product, Entity $duplicatingProduct)
    {
        if ($duplicatingProduct->get('productFamilyId') == $product->get('productFamilyId')) {
            // get data for duplicating
            $rows = $duplicatingProduct->get('productAttributeValues');

            if (count($rows) > 0) {
                foreach ($rows as $item) {
                    $entity = $this->getEntityManager()->getEntity('ProductAttributeValue');
                    $entity->set($item->toArray());
                    $entity->id = Util::generateId();
                    $entity->set('productId', $product->get('id'));

                    $this->getEntityManager()->saveEntity($entity, ['skipProductAttributeValueHook' => true]);

                    // relate channels
                    if (count($item->get('channels')) > 0) {
                        foreach ($item->get('channels') as $channel) {
                            $this
                                ->getEntityManager()
                                ->getRepository('ProductAttributeValue')
                                ->relate($entity, 'channels', $channel);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateProductCategories(Entity $product, Entity $duplicatingProduct)
    {
        // get data for duplicating
        $rows = $duplicatingProduct->get('productCategories');

        if (count($rows) > 0) {
            $service = $this->getServiceFactory()->create('ProductCategory');

            foreach ($rows as $item) {
                $data = $service->getDuplicateAttributes($item->get('id'));
                $data->productId = $product->get('id');

                $service->createEntity($data);
            }
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateAssociatedMainProducts(Entity $product, Entity $duplicatingProduct)
    {
        // get data
        $data = $duplicatingProduct->get('associatedMainProducts');

        // copy
        if (count($data) > 0) {
            foreach ($data as $row) {
                $item = $row->toArray();
                $item['id'] = Util::generateId();
                $item['mainProductId'] = $product->get('id');

                // prepare entity
                $entity = $this->getEntityManager()->getEntity('AssociatedProduct');
                $entity->set($item);

                // save
                $this->getEntityManager()->saveEntity($entity);
            }
        }
    }

    /**
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateAssociatedRelatedProduct(Entity $product, Entity $duplicatingProduct)
    {
        // get data
        $data = $duplicatingProduct->get('associatedRelatedProduct');

        // copy
        if (count($data) > 0) {
            foreach ($data as $row) {
                $item = $row->toArray();
                $item['id'] = Util::generateId();
                $item['relatedProductId'] = $product->get('id');

                // prepare entity
                $entity = $this->getEntityManager()->getEntity('AssociatedProduct');
                $entity->set($item);

                // save
                $this->getEntityManager()->saveEntity($entity);
            }
        }
    }

    /**
     * Find linked AssociationMainProduct
     *
     * @param string $id
     * @param array  $params
     *
     * @return array
     * @throws Forbidden
     */
    protected function findLinkedEntitiesAssociatedMainProducts(string $id, array $params): array
    {
        // check acl
        if (!$this->getAcl()->check('Association', 'read')) {
            throw new Forbidden();
        }

        return [
            'list'  => $this->getDBAssociationMainProducts($id, '', $params),
            'total' => $this->getDBTotalAssociationMainProducts($id, '')
        ];
    }

    /**
     * Get AssociationMainProducts from DB
     *
     * @param string $productId
     * @param string $wherePart
     * @param array  $params
     *
     * @return array
     */
    protected function getDBAssociationMainProducts(string $productId, string $wherePart, array $params): array
    {
        // prepare limit
        $limit = '';
        if (!empty($params['maxSize'])) {
            $limit = ' LIMIT ' . (int)$params['maxSize'];
            $limit .= ' OFFSET ' . (empty($params['offset']) ? 0 : (int)$params['offset']);
        }

        //prepare sort
        $sortOrder = ($params['asc'] === true) ? 'ASC' : 'DESC';
        $orderColumn = ['relatedProduct', 'association'];
        $sortColumn = in_array($params['sortBy'], $orderColumn) ? $params['sortBy'] . '.name' : 'relatedProduct.name';

        $stringTypes = $this->getStringProductTypes();

        // prepare query
        $sql
            = "SELECT
                  ap.id,
                  ap.association_id         AS associationId,
                  association.name          AS associationName,
                  p_main.id                 AS mainProductId,
                  p_main.name               AS mainProductName,
                  p_main.image_id           AS mainProductImageId,
                  (SELECT name FROM attachment WHERE id = p_main.image_id) AS mainProductImageName,
                  relatedProduct.id         AS relatedProductId,
                  relatedProduct.name       AS relatedProductName,
                  relatedProduct.image_id   AS relatedProductImageId,
                  (SELECT name FROM attachment WHERE id = relatedProduct.image_id) AS relatedProductImageName
                FROM associated_product AS ap
                  JOIN product AS relatedProduct 
                    ON relatedProduct.id = ap.related_product_id AND relatedProduct.deleted = 0
                  JOIN product AS p_main 
                    ON p_main.id = ap.main_product_id AND p_main.deleted = 0
                  JOIN association 
                    ON association.id = ap.association_id AND association.deleted = 0
                WHERE ap.deleted = 0 
                  AND ap.main_product_id = :id AND relatedProduct.type IN ('{$stringTypes}') "
            . $wherePart
            . "ORDER BY " . $sortColumn . " " . $sortOrder
            . $limit;

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute([':id' => $productId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get total AssociationMainProducts
     *
     * @param string $productId
     * @param string $wherePart
     *
     * @return int
     */
    protected function getDBTotalAssociationMainProducts(string $productId, string $wherePart): int
    {
        $stringTypes = $this->getStringProductTypes();

        // prepare query
        $sql
            = "SELECT
                  COUNT(ap.id)                  
                FROM associated_product AS ap
                  JOIN product AS p_rel 
                    ON p_rel.id = ap.related_product_id AND p_rel.deleted = 0
                  JOIN product AS p_main 
                    ON p_main.id = ap.related_product_id AND p_main.deleted = 0
                  JOIN association 
                    ON association.id = ap.association_id AND association.deleted = 0
                WHERE ap.deleted = 0 AND ap.main_product_id = :id  AND p_rel.type IN ('{$stringTypes}') " . $wherePart;

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute([':id' => $productId]);

        return (int)$sth->fetchColumn();
    }

    /**
     * Before create entity method
     *
     * @param Entity $entity
     * @param        $data
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        if (isset($data->_duplicatingEntityId)) {
            $entity->isDuplicate = true;
        }
    }

    /**
     * @return string
     */
    protected function getStringProductTypes(): string
    {
        return join("','", array_keys($this->getMetadata()->get('pim.productType')));
    }
}
