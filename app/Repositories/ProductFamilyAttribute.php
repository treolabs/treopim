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

namespace Pim\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;
use Treo\Core\Utils\Util;

/**
 * Class ProductFamilyAttribute
 *
 * @author r.ratsun@treolabs.com
 */
class ProductFamilyAttribute extends Base
{
    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        // is valid
        if (empty($options['skipValidation'])) {
            $this->isValid($entity);
        }

        if ($entity->isNew()) {
            // set type
            $entity->set('attributeType', $entity->get('attribute')->get('type'));
        }

        // clearing channels ids
        if ($entity->get('scope') == 'Global') {
            $entity->set('channelsIds', []);
        }

        parent::beforeSave($entity, $options);
    }

    /**
     * @inheritDoc
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        // update product attribute values
        $this->updateProductAttributeValues($entity);

        // update locales attributes recursively
        $this->updateLocaleAttributes($entity);

        parent::afterSave($entity, $options);
    }

    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    public function beforeRemove(Entity $entity, array $options = [])
    {
        if (empty($options['skipLocaleAttributeDeleting']) && !empty($entity->get('locale'))) {
            throw new BadRequest("Locale attribute can't be deleted");
        }

        parent::beforeRemove($entity, $options);
    }

    /**
     * @inheritDoc
     */
    public function afterRemove(Entity $entity, array $options = [])
    {
        // remove locales attribute recursively
        $this->deleteLocaleAttributes($entity, $options);

        // delete product attribute values
        if (empty($options['skipAttributeValueDeleting'])) {
            $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->removeCollectionByProductFamilyAttribute($entity->get('id'));
        } else {
            /** @var string $id */
            $id = $entity->get('id');

            $this
                ->getEntityManager()
                ->nativeQuery("UPDATE product_attribute_value SET product_family_attribute_id=NULL,is_required=0 WHERE product_family_attribute_id='$id'");
        }

        parent::afterRemove($entity, $options);
    }

    /**
     * @param Entity $entity
     *
     * @throws BadRequest
     */
    protected function isValid(Entity $entity): void
    {
        if (!$entity->isNew() && $entity->isAttributeChanged('attributeId')) {
            throw new BadRequest($this->exception('Product family attribute cannot be changed'));
        }

        if (empty($entity->get('productFamilyId')) || empty($entity->get('attributeId'))) {
            throw new BadRequest($this->exception('ProductFamily and Attribute cannot be empty'));
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }

        if ($entity->isNew() && !empty($entity->get('attribute')->get('locale'))) {
            throw new BadRequest("Locale attribute can't be linked");
        }

        if (!$entity->isNew() && !empty($entity->get('locale')) && ($entity->isAttributeChanged('scope') || !empty($entity->get('channelsIds')))) {
            throw new BadRequest("Locale attribute scope can't be changed");
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        // prepare count
        $item = null;

        if ($entity->get('scope') == 'Global') {
            $item = $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->select(['id'])
                ->where(
                    [
                        'id!='            => $entity->get('id'),
                        'productFamilyId' => $entity->get('productFamilyId'),
                        'attributeId'     => $entity->get('attributeId'),
                        'scope'           => 'Global',
                    ]
                )
                ->findOne();
        } elseif ($entity->get('scope') == 'Channel') {
            $item = $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->distinct()
                ->select(['id'])
                ->join('channels')
                ->where(
                    [
                        'id!='            => $entity->get('id'),
                        'productFamilyId' => $entity->get('productFamilyId'),
                        'attributeId'     => $entity->get('attributeId'),
                        'scope'           => 'Channel',
                        'channels.id'     => $entity->get('channelsIds'),
                    ]
                )
                ->findOne();
        }

        return empty($item);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function updateProductAttributeValues(Entity $entity): bool
    {
        /** @var \Pim\Entities\ProductFamily $productFamily */
        if (empty($productFamily = $entity->get('productFamily'))) {
            $productFamily = $this->getEntityManager()->getEntity('ProductFamily', $entity->get('productFamilyId'));
        }

        if (empty($productFamily)) {
            return false;
        }

        // get products ids
        if (empty($productsIds = $productFamily->get('productsIds'))) {
            return false;
        }

        /** @var array $sqls */
        $sqls = [];

        // get channels ids
        $channelsIds = (array)$entity->get('channelsIds');

        // implode channels
        $channels = implode(',', $channelsIds);

        // get already exists
        $exists = $this->getExistsProductAttributeValues($entity, $productsIds);

        // get product family attribute id
        $pfaId = $entity->get('id');

        // get scope
        $scope = $entity->get('scope');

        // get is required param
        $isRequired = (int)$entity->get('isRequired');

        // get attribute id
        $attributeId = $entity->get('attributeId');

        // Link exists records to product family attribute if it needs
        $skipToCreate = [];
        foreach ($exists as $item) {
            // prepare id
            $id = $item['id'];

            if (empty($item['productFamilyAttributeId'])) {
                if (($item['scope'] == $scope && $item['channels'] == $channels) || (!empty($entity->get('locale')) && $entity->get('locale') == $item['locale'])) {
                    if ($entity->isNew()) {
                        $skipToCreate[] = $item['productId'];
                        $sqls[] = "UPDATE product_attribute_value SET product_family_attribute_id='$pfaId',is_required=$isRequired,scope='$scope' WHERE id='$id'";
                    } else {
                        $sqls[] = "UPDATE product_attribute_value SET deleted=1 WHERE id='$id'";
                        $sqls[] = "DELETE FROM product_attribute_value_channel WHERE product_attribute_value_id='$id'";
                    }
                }
            }
        }

        // Unlink channels from exists records if it needs
        if ($scope == 'Channel') {
            foreach ($exists as $item) {
                // prepare id
                $id = $item['id'];

                if (empty($item['productFamilyAttributeId']) && $item['scope'] == 'Channel' && !empty($item['channels']) && $item['channels'] != $channels) {
                    foreach (explode(',', (string)$item['channels']) as $itemChannel) {
                        if (in_array($itemChannel, $channelsIds)) {
                            $sqls[] = "DELETE FROM product_attribute_value_channel WHERE product_attribute_value_id='$id' AND channel_id='$itemChannel'";
                        }
                    }
                }
            }
        }

        // Update exists records if it needs
        if (!$entity->isNew()) {
            // find ids
            $ids = [];
            foreach ($exists as $item) {
                if ($item['productFamilyAttributeId'] == $pfaId) {
                    $ids[] = $item['id'];
                }
            }
            $sqls[] = "UPDATE product_attribute_value SET is_required=$isRequired,scope='$scope' WHERE product_family_attribute_id='$pfaId' AND deleted=0";
            $sqls[] = "DELETE FROM product_attribute_value_channel WHERE product_attribute_value_id IN ('" . implode("','", $ids) . "')";
            foreach ($ids as $id) {
                foreach ($channelsIds as $channelId) {
                    $sqls[] = "INSERT INTO product_attribute_value_channel (channel_id, product_attribute_value_id) VALUES ('$channelId','$id')";
                }
            }
        }

        // Create a new records if it needs
        if ($entity->isNew()) {
            $createdById = $entity->get('createdById');
            $ownerUserId = $entity->get('ownerUserId');
            $assignedUserId = $entity->get('assignedUserId');
            $createdAt = $entity->get('createdAt');
            $teamsIds = (array)$entity->get('teamsIds');

            foreach ($productsIds as $productId) {
                if (in_array($productId, $skipToCreate)) {
                    continue 1;
                }

                // generate id
                $id = Util::generateId();

                /** @var string $type */
                $type = $entity->get('attributeType');

                /** @var string $locale */
                $locale = (empty($entity->get('locale'))) ? 'NULL' : "'" . $entity->get('locale') . "'";

                // prepare locale parent id
                $localeParentId = 'NULL';
                if (!empty($lpId = $entity->get('localeParentId'))) {
                    $localeParentId = "(SELECT id FROM product_attribute_value WHERE product_family_attribute_id='$lpId' AND product_id='$productId' LIMIT 1)";
                }

                $sqls[]
                    = "SET @localeParentId=$localeParentId;INSERT INTO product_attribute_value (id,scope,product_id,attribute_id,product_family_attribute_id,created_by_id,created_at,owner_user_id,assigned_user_id,attribute_type,locale,is_required,locale_parent_id) VALUES ('$id','$scope','$productId','$attributeId','$pfaId','$createdById','$createdAt','$ownerUserId','$assignedUserId','$type',$locale,$isRequired,@localeParentId)";
                if (!empty($teamsIds)) {
                    foreach ($teamsIds as $teamId) {
                        $sqls[] = "INSERT INTO entity_team (entity_id, team_id, entity_type) VALUES ('$id','$teamId','ProductAttributeValue')";
                    }
                }
                if ($scope == 'Channel') {
                    foreach ($channelsIds as $channelId) {
                        $sqls[] = "INSERT INTO product_attribute_value_channel (channel_id, product_attribute_value_id) VALUES ('$channelId','$id')";
                    }
                }
            }
        }

        if (empty($sqls)) {
            return false;
        }

        $subSqls = [];
        foreach ($sqls as $sql) {
            $subSqls[] = $sql;
            if (count($subSqls) > 200) {
                $this->getEntityManager()->nativeQuery(implode(";", $subSqls));
                $subSqls = [];
            }
        }
        if (!empty($subSqls)) {
            $this->getEntityManager()->nativeQuery(implode(";", $subSqls));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->addDependency('language');
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'ProductFamilyAttribute');
    }

    /**
     * @param Entity $entity
     */
    protected function updateLocaleAttributes(Entity $entity): void
    {
        if ($entity->isNew() && empty($entity->get('locale'))) {
            $attributes = $entity->get('attribute')->get('attributes');
            if (count($attributes) > 0) {
                foreach ($attributes as $attribute) {
                    $newEntity = $this->get();
                    $newEntity->set($entity->toArray());
                    $newEntity->id = Util::generateId();
                    $newEntity->set('attributeId', $attribute->get('id'));
                    $newEntity->set('attributeType', $attribute->get('type'));
                    $newEntity->set('locale', $attribute->get('locale'));
                    $newEntity->set('localeParentId', $entity->get('id'));
                    $this->getEntityManager()->saveEntity($newEntity, ['skipValidation' => true]);

                    if ($entity->get('scope') == 'Channel') {
                        $channels = $entity->get('channels');
                        if (count($channels) > 0) {
                            foreach ($channels as $channel) {
                                $this->relate($newEntity, 'channels', $channel);
                            }
                        }
                    }
                }
            }
        }

        if (!$entity->isNew() && !empty($entity->get('attribute')->get('isMultilang')) && ($entity->isAttributeChanged('scope') || $entity->isAttributeChanged('channelsIds'))) {
            /** @var \Pim\Entities\ProductFamilyAttribute[] $children */
            $children = $entity->get('localeChildren');
            if (count($children) > 0) {
                foreach ($children as $child) {
                    $child->set('scope', $entity->get('scope'));
                    $child->set('channelsIds', $entity->get('channelsIds'));
                    $this->getEntityManager()->saveEntity($child, ['skipValidation' => true]);
                }
            }
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    protected function deleteLocaleAttributes(Entity $entity, array $options): void
    {
        if (empty($options['skipLocaleAttributeDeleting']) && !empty($entity->get('attribute'))) {
            // find product family attributes
            $pfas = $entity->get('localeChildren');

            if (count($pfas) > 0) {
                foreach ($pfas as $pfa) {
                    $this
                        ->getEntityManager()
                        ->removeEntity(
                            $pfa, [
                                'skipLocaleAttributeDeleting' => true,
                                'skipAttributeValueDeleting'  => !empty($options['skipAttributeValueDeleting'])
                            ]
                        );
                }
            }
        }
    }

    /**
     * @param Entity $entity
     * @param array  $productsIds
     *
     * @return array
     */
    private function getExistsProductAttributeValues(Entity $entity, array $productsIds): array
    {
        // prepare sql
        $sql = "SELECT
                       id,
                       scope,
                       product_id AS productId,
                       (SELECT GROUP_CONCAT(channel_id ORDER BY channel_id ASC) FROM product_attribute_value_channel WHERE product_attribute_value_id=product_attribute_value.id) AS channels,
                       product_family_attribute_id AS productFamilyAttributeId,
                       locale
                FROM product_attribute_value
                WHERE product_id IN ('" . implode("','", $productsIds) . "')
                  AND deleted=0
                  AND attribute_id=:attributeId";

        return $this
            ->getEntityManager()
            ->nativeQuery($sql, ['attributeId' => $entity->get('attributeId')])
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
