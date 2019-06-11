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

namespace Espo\Modules\Pim\Entities;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Util;
use Espo\Core\Templates\Entities\Base;
use Espo\ORM\EntityCollection;

/**
 * Product entity
 *
 * @author r.ratsun@treolabs.com
 */
class Product extends Base
{
    /**
     * @var array
     */
    public $productAttribute = [];

    /**
     * @var string
     */
    protected $entityType = "Product";

    /**
     * @var string
     */
    private $attrMask = "/^attr_(.*)$/";

    /**
     * @inheritdoc
     */
    public function set($p1, $p2 = null)
    {
        // call parent
        parent::set($p1, $p2);

        // for product attribute
        if (is_string($p1) && preg_match_all($this->attrMask, $p1, $parts)) {
            // parse key
            $keyParts = explode("_", $parts[1][0]);

            // prepare data
            $attributeId = (string)$keyParts[0];
            $locale = $this->getLocale(substr($attributeId, -4));
            if (!empty($locale)) {
                $attributeId = substr($attributeId, 0, -4);
            }
            $value = (is_array($p2)) ? json_encode($p2, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : (string)$p2;

            $this->setProductAttributeValue($attributeId, $value, $locale);
        }
    }

    /**
     * @inheritdoc
     */
    public function get($name, $params = [])
    {
        // for product attribute
        if (preg_match_all($this->attrMask, (string)$name, $parts)) {
            // parse key
            $keyParts = explode("_", $parts[1][0]);

            // prepare data
            $attributeId = (string)$keyParts[0];
            $locale = $this->getLocale(substr($attributeId, -4));
            if (!empty($locale)) {
                $attributeId = substr($attributeId, 0, -4);
            }

            return $this->getProductAttributeValue($attributeId, $locale);
        }

        return parent::get($name, $params);
    }

    /**
     * Set product attribute value
     *
     * @param string      $attributeId
     * @param string      $value
     * @param string|null $locale
     *
     * @return Product
     */
    public function setProductAttributeValue(string $attributeId, string $value, string $locale = null): Product
    {
        if (!isset($this->productAttribute[$attributeId])) {
            $this->productAttribute[$attributeId] = [];
        }

        // prepare locale
        if (empty($locale)) {
            $locale = 'default';
        }

        $this->productAttribute[$attributeId]['locales'][$locale] = $value;

        return $this;
    }

    /**
     * Set product attribute data
     *
     * @param string $attributeId
     * @param string $field
     * @param string $data
     *
     * @return Product
     */
    public function setProductAttributeData(string $attributeId, string $field, string $data): Product
    {
        if (!isset($this->productAttribute[$attributeId])) {
            $this->productAttribute[$attributeId] = [];
        }

        $this->productAttribute[$attributeId]['data'][$field] = $data;

        return $this;
    }

    /**
     * Get product attribute value
     *
     * @param string      $attributeId
     * @param string|null $locale
     *
     * @return mixed
     * @throws Error
     */
    public function getProductAttributeValue(string $attributeId, string $locale = null)
    {
        // find
        $attribute = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(
                [
                    'productId'   => $this->get('id'),
                    'attributeId' => $attributeId,
                    'scope'       => 'Global'
                ]
            )
            ->findOne();

        // prepare value
        $value = null;

        if (!empty($attribute)) {
            // prepare key
            $key = 'value';
            if (!empty($locale)) {
                $key .= Util::toCamelCase(strtolower($locale), '_', true);
            }

            // global value
            $value = $attribute->get($key);
        }

        return $value;
    }

    /**
     * Get product attribute data
     *
     * @param string $attributeId
     * @param string $field
     *
     * @return mixed
     *
     * @throws Error
     */
    public function getProductAttributeData(string $attributeId, string $field)
    {
        $value = null;

        // find
        $attribute = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(
                [
                    'productId'   => $this->get('id'),
                    'attributeId' => $attributeId,
                    'scope'       => 'Global'
                ]
            )
            ->findOne();

        if (!empty($attribute) && $attribute->hasField($field)) {
            $value = $attribute->get($field);
        }

        return $value;
    }

    /**
     * Get product categories
     *
     * @return EntityCollection
     * @throws Error
     */
    public function getCategories(): EntityCollection
    {
        if (empty($this->get('id'))) {
            throw new Error('No such Product');
        }

        return $this
            ->getEntityManager()
            ->getRepository('Category')
            ->distinct()
            ->join('productCategories')
            ->where(['productCategories.productId' => $this->get('id')])
            ->find();
    }

    /**
     * Get images
     *
     * @return array
     *
     * @deprecated this method is deprecated!
     */
    public function getImages(): array
    {
        // prepare data
        $productId = $this->get('id');

        $sql
            = "SELECT
                  pi.id          AS id,
                  pi.name        AS code,
                  pi.alt         AS alt,
                  pi.width       AS width,
                  pi.height      AS height,
                  pi.size        AS size,
                  pi.image_type  AS imageType,
                  pi.type        AS type,
                  pi.image_id    AS imageId,
                  pi.image_link  AS imageLink,
                  pip.scope      AS scope,
                  pic.channel_id AS channelId
                FROM product_image_product AS pip
                JOIN product_image AS pi ON pi.id=pip.product_image_id AND pi.deleted=0
                LEFT JOIN product_image_channel AS pic 
                  ON pic.product_image_id=pi.id AND pic.product_id=pip.product_id AND pic.deleted=0
                WHERE 
                     pip.deleted = 0 
                 AND pip.product_id='$productId'
                ORDER BY pip.sort_order ASC";
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $locale
     *
     * @return null|string
     */
    protected function getLocale(string $locale): ?string
    {
        // prepare locale
        $locale = Util::toUnderScore($locale);

        // get input languages list
        $inputLanguageList = $this
            ->getEntityManager()
            ->getRepository($this->getEntityType())
            ->getInputLanguageList();

        foreach ($inputLanguageList as $item) {
            if (strtolower($item) == $locale) {
                return $item;
            }
        }

        return null;
    }

}
