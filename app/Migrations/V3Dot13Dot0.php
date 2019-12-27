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

use Treo\Core\Migration\Base;
use Treo\Core\Utils\Util;

/**
 * Migration class for version 3.13.0
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot13Dot0 extends Base
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        /**
         * Migrate Attribute DB schema
         */
        $this->exec("ALTER TABLE attribute DROP is_system");
        $this->exec("ALTER TABLE attribute ADD locale VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_LOCALE ON `attribute` (locale, deleted)");
        $this->exec("ALTER TABLE attribute ADD parent_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_PARENT_ID ON attribute (parent_id)");

        /**
         * Migrate ProductFamilyAttribute DB schema
         */
        $this->exec("DROP INDEX IDX_NAME ON `product_family_attribute`");
        $this->exec("ALTER TABLE `product_family_attribute` DROP name");
        $this->exec("ALTER TABLE `product_family_attribute` ADD attribute_type VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE_TYPE ON `product_family_attribute` (attribute_type, deleted)");
        $this->exec("CREATE INDEX IDX_PRODUCT_FAMILY ON `product_family_attribute` (product_family_id, deleted)");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE ON `product_family_attribute` (attribute_id, deleted)");
        $this->exec("CREATE INDEX IDX_IS_REQUIRED ON `product_family_attribute` (is_required, deleted)");
        $this->exec("CREATE INDEX IDX_SCOPE ON `product_family_attribute` (scope, deleted)");
        $this->exec("ALTER TABLE `product_family_attribute` ADD locale VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_LOCALE ON `product_family_attribute` (locale, deleted)");

        /**
         * Migrate ProductAttributeValue DB schema
         */
        $this->exec("DROP INDEX IDX_NAME ON `product_attribute_value`");
        $this->exec("ALTER TABLE `product_attribute_value` DROP name");
        $this->exec("ALTER TABLE `product_attribute_value` ADD attribute_type VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE_TYPE ON `product_attribute_value` (attribute_type, deleted)");
        $this->exec("CREATE INDEX IDX_PRODUCT ON `product_attribute_value` (product_id, deleted)");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE ON `product_attribute_value` (attribute_id, deleted)");
        $this->exec("CREATE INDEX IDX_SCOPE ON `product_attribute_value` (scope, deleted)");
        $this->exec("ALTER TABLE `product_attribute_value` ADD locale VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_LOCALE ON `product_attribute_value` (locale, deleted)");

        /**
         * Migrate DATA
         */
        $attributes = $this->fetchAll("SELECT * FROM attribute WHERE deleted=0");
        foreach ($attributes as $attribute) {
            /** @var string $attributeId */
            $attributeId = $attribute['id'];

            /** @var string $type */
            $type = $attribute['type'];

            if ($this->getConfig()->get('isMultilangActive') && !empty($attribute['is_multilang'])) {
                foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                    /** @var string $newAttributeId */
                    $newAttributeId = Util::generateId();

                    $row = $attribute;
                    $row['id'] = $newAttributeId;
                    $row['name'] = $attribute['name_' . Util::toUnderScore(strtolower($locale))];
                    $row['code'] = $attribute['code'] . '_' . strtolower($locale);
                    $row['parent_id'] = $attributeId;
                    $row['locale'] = $locale;
                    $row['type_value'] = $attribute['type_value_' . Util::toUnderScore(strtolower($locale))];
                    $row['is_multilang'] = '0';

                    $this->exec(sprintf("INSERT INTO attribute (%s) VALUES ('%s')", implode(",", array_keys($row)), implode("','", array_values($row))));

                    $pfas = $this->fetchAll(
                        "SELECT *, (SELECT GROUP_CONCAT(channel_id ORDER BY channel_id ASC) FROM product_family_attribute_channel WHERE product_family_attribute.id=product_family_attribute_channel.product_family_attribute_id) AS channels FROM product_family_attribute WHERE deleted=0 AND attribute_id='$attributeId'"
                    );
                    foreach ($pfas as $pfa) {
                        $newPfaId = Util::generateId();
                        $channels = $pfa['channels'];
                        $pfaId = $pfa['id'];

                        unset($pfa['channels']);
                        $newPfa = $pfa;
                        $newPfa['id'] = $newPfaId;
                        $newPfa['attribute_id'] = $newAttributeId;
                        $newPfa['locale'] = $locale;
                        $this->exec(sprintf("INSERT INTO product_family_attribute (%s) VALUES ('%s')", implode(",", array_keys($newPfa)), implode("','", array_values($newPfa))));
                        if (!empty($channels)) {
                            foreach (explode(",", $channels) as $channelId) {
                                $this->exec("INSERT INTO product_family_attribute_channel (channel_id, product_family_attribute_id) VALUES ('$channelId','$newPfaId')");
                            }
                        }

                        $pavs = $this->fetchAll(
                            "SELECT *, (SELECT GROUP_CONCAT(channel_id ORDER BY channel_id ASC) FROM product_attribute_value_channel WHERE product_attribute_value.id=product_attribute_value_channel.product_attribute_value_id) AS channels FROM product_attribute_value WHERE deleted=0 AND product_family_attribute_id='$pfaId'"
                        );
                        foreach ($pavs as $pav) {
                            $channels = $pav['channels'];
                            unset($pav['channels']);

                            $newPav = $pav;
                            $newPav['id'] = Util::generateId();
                            $newPav['attribute_id'] = $newAttributeId;
                            $newPav['locale'] = $locale;
                            $newPav['product_family_attribute_id'] = $newPfaId;
                            $newPav['value'] = $pav['value_' . Util::toUnderScore(strtolower($locale))];

                            $this->exec(
                                sprintf("INSERT INTO product_attribute_value (%s) VALUES ('%s')", implode(",", array_keys($newPav)), implode("','", array_values($newPav)))
                            );
                            if (!empty($channels)) {
                                foreach (explode(",", $channels) as $channelId) {
                                    $this->exec(
                                        "INSERT INTO product_attribute_value_channel (channel_id, product_attribute_value_id) VALUES ('$channelId','" . $newPav['id'] . "')"
                                    );
                                }
                            }
                        }
                    }

                    $pavs = $this->fetchAll(
                        "SELECT *, (SELECT GROUP_CONCAT(channel_id ORDER BY channel_id ASC) FROM product_attribute_value_channel WHERE product_attribute_value.id=product_attribute_value_channel.product_attribute_value_id) AS channels FROM product_attribute_value WHERE deleted=0 AND product_family_attribute_id IS NULL AND attribute_id='$attributeId'"
                    );
                    foreach ($pavs as $pav) {
                        $channels = $pav['channels'];
                        unset($pav['channels']);

                        $newPav = $pav;
                        $newPav['id'] = Util::generateId();
                        $newPav['attribute_id'] = $newAttributeId;
                        $newPav['locale'] = $locale;
                        $newPav['value'] = $pav['value_' . Util::toUnderScore(strtolower($locale))];

                        $this->exec(
                            sprintf("INSERT INTO product_attribute_value (%s) VALUES ('%s')", implode(",", array_keys($newPav)), implode("','", array_values($newPav)))
                        );
                        if (!empty($channels)) {
                            foreach (explode(",", $channels) as $channelId) {
                                $this->exec(
                                    "INSERT INTO product_attribute_value_channel (channel_id, product_attribute_value_id) VALUES ('$channelId','" . $newPav['id'] . "')"
                                );
                            }
                        }
                    }
                }
            }

            $this->exec("UPDATE product_attribute_value SET attribute_type='$type', locale=NULL WHERE attribute_id='$attributeId' AND deleted=0");
            $this->exec("UPDATE product_family_attribute SET attribute_type='$type', locale=NULL WHERE attribute_id='$attributeId' AND deleted=0");
        }
        $this->exec("UPDATE product_attribute_value SET deleted=1 WHERE attribute_type IS NULL");
        $this->exec("UPDATE product_family_attribute SET deleted=1 WHERE attribute_type IS NULL");

        /**
         * Drop multi-lang columns
         */
//        ALTER TABLE `attribute` DROP name_de_de, DROP type_value_de_de;
//        ALTER TABLE `product_attribute_value` DROP value_de_de;
    }

    /**
     * @param string $sql
     */
    protected function exec(string $sql): void
    {
        echo $sql . PHP_EOL;
        try {
            $this->getPDO()->exec($sql);
        } catch (\PDOException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * @param string $sql
     *
     * @return array
     */
    protected function fetchAll(string $sql): array
    {
        $sth = $this->getPDO()->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
