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

/**
 * Class ProductFamily
 *
 * @author r.ratsun@treolabs.com
 */
class ProductFamily extends \Espo\Core\Templates\Entities\Base
{
    /**
     * @var string
     */
    protected $entityType = "ProductFamily";

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param string $attributeId
     *
     * @return bool
     */
    public function isAttributeRequired(string $attributeId): bool
    {
        // get attribute data
        $data = array_column($this->getPFAttributes(), 'is_required', 'attribute_id');

        return !empty($data[$attributeId]);
    }

    /**
     * @param string $attributeId
     *
     * @return bool
     */
    public function isAttributeMultiChannel(string $attributeId): bool
    {
        // get attribute data
        $data = array_column($this->getPFAttributes(), 'is_multi_channel', 'attribute_id');

        return !empty($data[$attributeId]);
    }

    /**
     * @return array
     */
    protected function getPFAttributes(): array
    {
        if (!isset($this->attributes[$this->get('id')])) {
            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare("SELECT * FROM product_family_attribute_linker WHERE product_family_id=:id AND deleted=0");
            $sth->execute(['id' => $this->get('id')]);
            $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

            $this->attributes[$this->get('id')] = (!empty($data)) ? $data : [];
        }

        return $this->attributes[$this->get('id')];
    }
}
