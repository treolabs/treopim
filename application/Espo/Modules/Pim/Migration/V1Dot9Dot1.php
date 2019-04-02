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

namespace Espo\Modules\Pim\Migration;

use Treo\Core\Migration\AbstractMigration;

/**
 * Migration class for version 1.9.1
 *
 * @author r.ratsun@treolabs.com
 */
class V1Dot9Dot1 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $sth = $this->getEntityManager()->getPDO()->prepare("SELECT * FROM product_family_attribute");
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $sql = '';
            foreach ($data as $row) {
                // prepare data
                $isRequired = $row['is_required'];
                $productFamilyId = $row['product_family_id'];
                $attributeId = $row['attribute_id'];
                $isMultiChannel = $row['is_multi_channel'];

                $sql
                    .= "INSERT INTO product_family_attribute_linker
                            (is_required, product_family_id, attribute_id, is_multi_channel) 
                         VALUES 
                            ($isRequired, '$productFamilyId', '$attributeId', $isMultiChannel);";
            }

            $sql .= "DELETE FROM product_family_attribute";

            // execute
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();
        }
    }
}
