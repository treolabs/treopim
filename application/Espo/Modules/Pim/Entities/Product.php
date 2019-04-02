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
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\Modules\Pim\Repositories\Product as ProductRepository;

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
     * @var array
     */
    public $productChannelAttribute = [];

    /**
     * @var string
     */
    protected $entityType = "Product";

    /**
     * @var string
     */
    private $attrMask = "/^attr_(.*)$/";

    /**
     * @var array
     */
    private $data = [];

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
            $channelId = (isset($keyParts[1])) ? (string)$keyParts[1] : null;
            $locale = $this->getLocale(substr($attributeId, -4));
            if (!empty($locale)) {
                $attributeId = substr($attributeId, 0, -4);
            }
            $value = (is_array($p2)) ? json_encode($p2, \JSON_UNESCAPED_UNICODE) : (string)$p2;

            if (empty($channelId)) {
                $this->setProductAttributeValue($attributeId, $value, $locale);
            } else {
                $this->setProductChannelAttributeValue($attributeId, $channelId, $value, $locale);
            }
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
            $channelId = (isset($keyParts[1])) ? (string)$keyParts[1] : null;
            $locale = $this->getLocale(substr($attributeId, -4));
            if (!empty($locale)) {
                $attributeId = substr($attributeId, 0, -4);
            }

            if (empty($channelId)) {
                return $this->getProductAttributeValue($attributeId, $locale);
            } else {
                return $this->getProductChannelAttributeValue($attributeId, $channelId, $locale);
            }
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

        $this->productAttribute[$attributeId][$locale] = $value;

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
        // prepare value
        $value = null;

        if (!empty($attribute = $this->getProductAttribute($attributeId))) {
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
     * Set product channel attribute value
     *
     * @param string      $attributeId
     * @param string      $channelId
     * @param string      $value
     * @param string|null $locale
     *
     * @return Product
     */
    public function setProductChannelAttributeValue(string $attributeId, string $channelId, string $value, string $locale = null): Product
    {
        if (!isset($this->productChannelAttribute[$attributeId][$channelId])) {
            $this->productChannelAttribute[$attributeId][$channelId] = [];
        }

        // prepare locale
        if (empty($locale)) {
            $locale = 'default';
        }

        $this->productChannelAttribute[$attributeId][$channelId][$locale] = $value;

        return $this;
    }

    /**
     * Get product channel attribute value
     *
     * @param string $attributeId
     * @param string $channelId
     * @param string|null $locale
     * @param bool $isStrict
     *
     * @return mixed
     * @throws Error
     */
    public function getProductChannelAttributeValue(
        string $attributeId,
        string $channelId,
        string $locale = null,
        bool $isStrict = false
    ) {
        if (!empty($data = $this->getProductChannelAttributes()) && count($data) > 0) {
            foreach ($data as $item) {
                if ($item->get('channelId') == $channelId && $item->get('productAttribute')->get('attributeId') == $attributeId) {
                    // prepare key
                    $key = 'value';
                    if (!empty($locale)) {
                        $key .= Util::toCamelCase(strtolower($locale), '_', true);
                    }

                    return $item->get($key);
                }
            }
        }

        if ($isStrict) {
            return null;
        } else {
            return $this->getProductAttributeValue($attributeId, $locale);
        }
    }

    /**
     * Get product attribute
     *
     * @param string $attributeId
     *
     * @return Entity|null
     * @throws Error
     */
    public function getProductAttribute(string $attributeId): ?Entity
    {
        if (!empty($data = $this->getProductAttributes())) {
            foreach ($data as $item) {
                if ($item->get('attributeId') == $attributeId) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Get product attributes
     *
     * @return array
     * @throws Error
     */
    public function getProductAttributes(): ?EntityCollection
    {
        if (!empty($this->get('id'))) {
            $data = $this
                ->getProductRepository()
                ->getProductsAttributes([$this->get('id')]);

            if (isset($data[$this->get('id')])) {
                return $data[$this->get('id')];
            }
        }

        return null;
    }

    /**
     * Get product attributes (channel specific)
     *
     * @return EntityCollection|null
     * @throws Error
     */
    public function getProductChannelAttributes(): ?EntityCollection
    {
        if (!empty($this->get('id'))) {
            if (empty($this->data)) {
                $this->data = $this
                    ->getProductRepository()
                    ->getProductsChannelAttributes([$this->get('id')]);
            }

            if (isset($this->data[$this->get('id')])) {
                return $this->data[$this->get('id')];
            }
        }

        return null;
    }

    /**
     * Get product categories
     *
     * @param bool $tree
     *
     * @return EntityCollection|null
     * @throws Error
     */
    public function getCategories(bool $tree = false): ?EntityCollection
    {
        if (!empty($this->get('id'))) {
            $data = $this
                ->getProductRepository()
                ->getCategories([$this->get('id')], $tree);

            if (isset($data[$this->get('id')])) {
                return $data[$this->get('id')];
            }
        }

        return null;
    }

    /**
     * Get product channels
     *
     * @return EntityCollection|null
     * @throws Error
     */
    public function getChannels(): ?EntityCollection
    {
        if (!empty($this->get('id'))) {
            $data = $this
                ->getProductRepository()
                ->getChannels([$this->get('id')]);

            if (isset($data[$this->get('id')])) {
                return $data[$this->get('id')];
            }
        }

        return null;
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
     * @return ProductRepository
     */
    protected function getProductRepository(): ProductRepository
    {
        return $this->getEntityManager()->getRepository($this->getEntityType());
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

        foreach ($this->getProductRepository()->getInputLanguageList() as $item) {
            if (strtolower($item) == $locale) {
                return $item;
            }
        }

        return null;
    }
}
