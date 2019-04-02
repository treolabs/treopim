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

namespace Espo\Modules\Pim\Services;

/**
 * Class GeneralStatisticsDashlet
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class GeneralStatisticsDashlet extends AbstractProductDashletService
{

    /**
     * Get general statistic
     *
     * @return array
     */
    public function getDashlet(): array
    {
        $result = ['total' => 0, 'list' => []];

        $result['list'] = [
            [
                'id'     => 'product',
                'name'   => 'product',
                'amount' => $this->getAmountProduct()
            ],
            [
                'id'     => 'category',
                'name'   => 'category',
                'amount' => $this->getAmountEntity('Category')
            ],
            [
                'id'     => 'productFamily',
                'name'   => 'productFamily',
                'amount' => $this->getAmountEntity('ProductFamily')
            ],
            [
                'id'     => 'attribute',
                'name'   => 'attribute',
                'amount' => $this->getAmountEntity('Attribute')
            ],
            [
                'id'     => 'productWithoutAssociatedProduct',
                'name'   => 'productWithoutAssociatedProduct',
                'amount' => $this->getAmountProductWithoutAssociatedProduct()
            ],
            [
                'id'     => 'productWithoutCategory',
                'name'   => 'productWithoutCategory',
                'amount' => $this->getAmountProductWithoutCategory()
            ],
            [
                'id'     => 'productWithoutAttribute',
                'name'   => 'productWithoutAttribute',
                'amount' => $this->getAmountProductWithoutAttribute()
            ],
            [
                'id'     => 'productWithoutImage',
                'name'   => 'productWithoutImage',
                'amount' => $this->getAmountProductWithoutImage()
            ]
        ];

        $result['total'] = count($result['list']);

        return $result;
    }

    /**
     * Get query for Product without Image
     *
     * @param bool $count
     *
     * @return string
     */
    public function getQueryProductWithoutImage($count = false): string
    {
        $select = $count ? 'COUNT(p.id)' : 'p.id AS id';
        $sql =
            "SELECT " . $select . " 
                FROM product as p 
                WHERE
                    (SELECT COUNT(pi.id)
                    FROM product_image AS pi
                      JOIN 
                      product_image_product AS pip ON pip.deleted = 0 AND pip.product_image_id = pi.id
                    WHERE pi.deleted = 0 AND pip.product_id = p.id) = 0 
                AND p.deleted = 0 AND p.type IN " . $this->getProductTypesCondition();

        return $sql;
    }

    /**
     * Get query for Product without AssociatedProduct
     *
     * @param bool $count
     *
     * @return string
     */
    public function getQueryProductWithoutAssociatedProduct($count = false): string
    {
        $select = $count ? 'COUNT(p.id)' : 'p.id AS id';
        $sql = "SELECT " . $select . " 
                FROM product as p 
                WHERE 
                    (SELECT COUNT(ap.id)                  
                    FROM associated_product AS ap
                      JOIN product AS p_rel 
                        ON p_rel.id = ap.related_product_id AND p_rel.deleted = 0
                      JOIN product AS p_main 
                        ON p_main.id = ap.related_product_id AND p_main.deleted = 0
                      JOIN association 
                        ON association.id = ap.association_id AND association.deleted = 0
                    WHERE ap.deleted = 0 AND  ap.main_product_id = p.id) = 0 
                AND p.deleted = 0 AND p.type IN " . $this->getProductTypesCondition();

        return $sql;
    }

    /**
     * Get query for Product without Category
     *
     * @param bool $count
     *
     * @return string
     */
    public function getQueryProductWithoutCategory($count = false): string
    {
        $select = $count ? 'COUNT(p.id)' : 'p.id AS id';
        $sql
            = "SELECT " . $select . " 
                FROM product as p 
                WHERE
                    (SELECT COUNT(c.id)
                    FROM category AS c
                      JOIN 
                      product_category_linker AS pcl ON pcl.deleted = 0 AND c.id = pcl.category_id
                    WHERE c.deleted = 0 AND pcl.product_id = p.id) = 0 
                AND p.deleted = 0 AND p.type IN " . $this->getProductTypesCondition();

        return $sql;
    }

    /**
     * Get query for Product without Attribute
     *
     * @param bool $count
     *
     * @return string
     */
    public function getQueryProductWithoutAttribute($count = false): string
    {
        $select = $count ? 'COUNT(DISTINCT p.id)' : 'DISTINCT p.id AS id';
        $sql =
            "SELECT " . $select . " 
                FROM product as p
                    LEFT JOIN product_attribute_value AS pal ON pal.product_id = p.id AND pal.deleted = 0
                    LEFT JOIN product_family AS pf ON pf.deleted = 0 AND pf.id = p.product_family_id
                    LEFT JOIN product_family_attribute_linker AS pfa ON pfa.deleted = 0 AND pfa.product_family_id = pf.id
                    LEFT JOIN attribute AS a ON a.deleted = 0 AND (a.id = pfa.attribute_id OR a.id = pal.attribute_id)
                WHERE a.id IS NULL AND p.deleted = 0 AND p.type IN " . $this->getProductTypesCondition();

        return $sql;
    }

    /**
     * Get amount product
     *
     * @return int
     */
    protected function getAmountProduct(): int
    {
        return $this->getRepository('Product')->where(['type' => $this->getProductTypes()])->count();
    }

    /**
     * Get amount Entity
     *
     * @param $entityType
     *
     * @return int
     */
    protected function getAmountEntity($entityType): int
    {
        return $this->getRepository($entityType)->count();
    }

    /**
     * Get Amount Product without Attribute
     *
     * @return int
     */
    protected function getAmountProductWithoutAttribute(): int
    {
        $sth = $this->getPDO()->prepare($this->getQueryProductWithoutAttribute(true));
        $sth->execute();

        return (int)$sth->fetchColumn();
    }

    /**
     * Get Amount Product without image
     *
     * @return int
     */
    protected function getAmountProductWithoutImage(): int
    {
        $sth = $this->getPDO()->prepare($this->getQueryProductWithoutImage(true));
        $sth->execute();

        return (int)$sth->fetchColumn();
    }

    /**
     * Get Amount Product without AssociatedProduct
     *
     * @return int
     */
    protected function getAmountProductWithoutAssociatedProduct(): int
    {
        $sth = $this->getPDO()->prepare($this->getQueryProductWithoutAssociatedProduct(true));
        $sth->execute();

        return (int)$sth->fetchColumn();
    }

    /**
     * Get amount Product without category
     *
     * @return int
     */
    protected function getAmountProductWithoutCategory(): int
    {
        $sth = $this->getPDO()->prepare($this->getQueryProductWithoutCategory(true));
        $sth->execute();

        return (int)$sth->fetchColumn();
    }
}
