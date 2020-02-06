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
     * @var int
     */
    private $errors = 0;

    /**
     * @inheritdoc
     */
    public function up(): void
    {
        echo ' Update scheduled jobs... ';
        $this->getPDO()->exec("DELETE FROM scheduled_job WHERE job='PimCleanup'");
        $this->getPDO()->exec("DELETE FROM job WHERE name='PimCleanup'");
        echo ' Done!' . PHP_EOL;

        echo ' Delete custom layouts for Attribute entity... ';
        Util::removeDir('custom/Espo/Custom/Resources/layouts/Attribute');
        echo ' Done!' . PHP_EOL;

        echo ' Migrate Attribute DB schema... ';
        $this->exec("ALTER TABLE attribute ENGINE=InnoDB");
        $this->exec("ALTER TABLE attribute DROP is_system");
        $this->exec("ALTER TABLE attribute ADD locale VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_LOCALE ON `attribute` (locale, deleted)");
        $this->exec("ALTER TABLE attribute ADD parent_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_PARENT_ID ON attribute (parent_id)");
        echo ' Done!' . PHP_EOL;

        echo ' Migrate ProductFamilyAttribute DB schema... ';
        $this->exec("ALTER TABLE `product_family_attribute` ENGINE=InnoDB");
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
        $this->exec("ALTER TABLE `product_family_attribute` ADD locale_parent_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_LOCALE_PARENT_ID ON `product_family_attribute` (locale_parent_id)");
        echo ' Done!' . PHP_EOL;

        echo ' Migrate ProductAttributeValue DB schema... ';
        $this->exec("ALTER TABLE `product_attribute_value` ENGINE=InnoDB");
        $this->exec("DROP INDEX IDX_NAME ON `product_attribute_value`");
        $this->exec("ALTER TABLE `product_attribute_value` DROP name");
        $this->exec("ALTER TABLE `product_attribute_value` ADD attribute_type VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE_TYPE ON `product_attribute_value` (attribute_type, deleted)");
        $this->exec("CREATE INDEX IDX_PRODUCT ON `product_attribute_value` (product_id, deleted)");
        $this->exec("CREATE INDEX IDX_ATTRIBUTE ON `product_attribute_value` (attribute_id, deleted)");
        $this->exec("CREATE INDEX IDX_SCOPE ON `product_attribute_value` (scope, deleted)");
        $this->exec("ALTER TABLE `product_attribute_value` ADD locale VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_LOCALE ON `product_attribute_value` (locale, deleted)");
        $this->exec("ALTER TABLE `product_attribute_value` ADD locale_parent_id VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_LOCALE_PARENT_ID ON `product_attribute_value` (locale_parent_id)");
        echo ' Done!' . PHP_EOL;

        echo ' Migrate DATA: ' . PHP_EOL;
        $attributes = $this->fetchAll("SELECT * FROM attribute WHERE deleted=0");

        foreach ($attributes as $attribute) {
            /** @var string $attributeId */
            $attributeId = $attribute['id'];

            /** @var string $type */
            $type = $attribute['type'];

            echo "  Migrate attribute '{$attribute['name']}' ({$attribute['id']})... ";

            $this->exec("UPDATE product_attribute_value SET attribute_type='$type', locale=NULL WHERE attribute_id='$attributeId' AND deleted=0");
            $this->exec("UPDATE product_family_attribute SET attribute_type='$type', locale=NULL WHERE attribute_id='$attributeId' AND deleted=0");

            if ($this->getConfig()->get('isMultilangActive') && !empty($attribute['is_multilang'])) {
                foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                    /** @var string $newAttributeId */
                    $newAttributeId = Util::generateId();

                    $row = $this->cpRow($attribute);
                    $row['id'] = $newAttributeId;
                    $row['name'] = $attribute['name_' . Util::toUnderScore(strtolower($locale))];
                    if (empty($row['name']) || (!empty($row['name']) && $row['name'] == $attribute['name'])) {
                        $row['name'] = $attribute['name'] . ' › ' . $locale;
                    }
                    $row['code'] = $attribute['code'] . '_' . strtolower($locale);
                    $row['parent_id'] = $attributeId;
                    $row['locale'] = $locale;
                    $row['type_value'] = $attribute['type_value_' . Util::toUnderScore(strtolower($locale))];
                    $row['is_multilang'] = '0';

                    $this->exec(
                        sprintf("INSERT INTO attribute (%s) VALUES (:%s)", implode(",", array_keys($row)), implode(",:", array_keys($row))), $row
                    );

                    $pfas = $this->fetchAll(
                        "SELECT *, (SELECT GROUP_CONCAT(channel_id ORDER BY channel_id ASC) FROM product_family_attribute_channel WHERE product_family_attribute.id=product_family_attribute_channel.product_family_attribute_id) AS channels FROM product_family_attribute WHERE deleted=0 AND attribute_id='$attributeId'"
                    );
                    foreach ($pfas as $pfa) {
                        $newPfaId = Util::generateId();
                        $channels = $pfa['channels'];
                        $pfaId = $pfa['id'];

                        unset($pfa['channels']);
                        $newPfa = $this->cpRow($pfa);
                        $newPfa['id'] = $newPfaId;
                        $newPfa['attribute_id'] = $newAttributeId;
                        $newPfa['locale'] = $locale;
                        $newPfa['locale_parent_id'] = $pfa['id'];
                        $this->exec(
                            sprintf("INSERT INTO product_family_attribute (%s) VALUES (:%s)", implode(",", array_keys($newPfa)), implode(",:", array_keys($newPfa))), $newPfa
                        );
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

                            $newPav = $this->cpRow($pav);
                            $newPav['id'] = Util::generateId();
                            $newPav['attribute_id'] = $newAttributeId;
                            $newPav['locale'] = $locale;
                            $newPav['locale_parent_id'] = $pav['id'];
                            $newPav['product_family_attribute_id'] = $newPfaId;
                            $newPav['value'] = $pav['value_' . Util::toUnderScore(strtolower($locale))];

                            $this->exec(
                                sprintf("INSERT INTO product_attribute_value (%s) VALUES (:%s)", implode(",", array_keys($newPav)), implode(",:", array_keys($newPav))), $newPav
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

                        $newPav = $this->cpRow($pav);
                        $newPav['id'] = Util::generateId();
                        $newPav['attribute_id'] = $newAttributeId;
                        $newPav['locale'] = $locale;
                        $newPav['locale_parent_id'] = $pav['id'];
                        $newPav['value'] = $pav['value_' . Util::toUnderScore(strtolower($locale))];

                        $this->exec(
                            sprintf("INSERT INTO product_attribute_value (%s) VALUES (:%s)", implode(",", array_keys($newPav)), implode(",:", array_keys($newPav))), $newPav
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
            echo '  Done!' . PHP_EOL;
        }
        $this->exec("UPDATE product_attribute_value SET deleted=1 WHERE attribute_type IS NULL");
        $this->exec("UPDATE product_family_attribute SET deleted=1 WHERE attribute_type IS NULL");
        echo ' Done!' . PHP_EOL;

        echo ' Drop multi-lang columns... ';
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $key = Util::toUnderScore(strtolower($locale));
                $this->exec("ALTER TABLE `attribute` DROP name_$key, DROP type_value_$key");
                $this->exec("ALTER TABLE `product_attribute_value` DROP value_$key");
            }
        }
        echo ' Done!' . PHP_EOL;

        if (!empty($this->errors)) {
            echo ' ' . $this->errors . ' requests failed. Please, refer to the log file for details.' . PHP_EOL;
        }
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        echo ' Delete custom layouts for Attribute entity... ';
        Util::removeDir('custom/Espo/Custom/Resources/layouts/Attribute');
        echo ' Done!' . PHP_EOL;

        echo ' Add multi-lang columns... ';
        if ($this->getConfig()->get('isMultilangActive')) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $key = Util::toUnderScore(strtolower($locale));
                $this->exec("ALTER TABLE `attribute` ADD name_$key VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
                $this->exec("ALTER TABLE `attribute` ADD type_value_$key MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
                $this->exec("ALTER TABLE `product_attribute_value` ADD value_$key MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
            }
        }
        echo ' Done!' . PHP_EOL;

        echo ' Migrate DATA: ' . PHP_EOL;
        $attributes = $this->fetchAll("SELECT id,name,parent_id,type_value,locale FROM attribute WHERE deleted=0 AND locale IS NOT NULL");
        foreach ($attributes as $attribute) {
            echo "  Migrate attribute '{$attribute['name']}' ({$attribute['id']})... ";

            /** @var string $key */
            $key = Util::toUnderScore(strtolower($attribute['locale']));

            $this->exec("UPDATE attribute SET name_$key='{$attribute['name']}', type_value_$key='{$attribute['type_value']}' WHERE id='{$attribute['parent_id']}'");

            // cleanup
            $this->exec("DELETE FROM attribute WHERE id='{$attribute['id']}'");
            $this->exec(
                "DELETE FROM product_family_attribute_channel WHERE product_family_attribute_id IN (SELECT id FROM product_family_attribute WHERE attribute_id='{$attribute['id']}')"
            );
            $this->exec("DELETE FROM product_family_attribute WHERE attribute_id='{$attribute['id']}'");

            // get product attribute values
            $pavs = $this->fetchAll(
                "SELECT id, product_id, locale, locale_parent_id, value FROM product_attribute_value WHERE attribute_id='{$attribute['id']}' AND locale_parent_id IS NOT NULL"
            );
            foreach ($pavs as $pav) {
                $pavKey = Util::toUnderScore(strtolower($pav['locale']));
                $this->exec(
                    "UPDATE product_attribute_value SET value_$pavKey='{$pav['value']}' WHERE id='{$pav['locale_parent_id']}' AND product_id='{$pav['product_id']}'"
                );

                // cleanup
                $this->exec("DELETE FROM product_attribute_value WHERE id='{$pav['id']}'");
                $this->exec("DELETE FROM product_attribute_value_channel WHERE product_attribute_value_id='{$pav['id']}'");
            }
            echo '  Done!' . PHP_EOL;
        }

        echo ' Done!' . PHP_EOL;

        echo ' Migrate Attribute DB schema... ';
        $this->exec("DROP INDEX IDX_LOCALE ON `attribute`");
        $this->exec("DROP INDEX IDX_PARENT_ID ON `attribute`");
        $this->exec("ALTER TABLE `attribute` DROP locale, DROP parent_id, ADD is_system TINYINT(1) DEFAULT '0' NOT NULL COLLATE utf8mb4_unicode_ci");
        echo ' Done!' . PHP_EOL;

        echo ' Migrate ProductAttributeValue DB schema... ';
        $this->exec("DROP INDEX IDX_ATTRIBUTE_TYPE ON `product_attribute_value`");
        $this->exec("DROP INDEX IDX_PRODUCT ON `product_attribute_value`");
        $this->exec("DROP INDEX IDX_ATTRIBUTE ON `product_attribute_value`");
        $this->exec("DROP INDEX IDX_SCOPE ON `product_attribute_value`");
        $this->exec("DROP INDEX IDX_LOCALE ON `product_attribute_value`");
        $this->exec("ALTER TABLE `product_attribute_value` DROP attribute_type, DROP locale, ADD name VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_NAME ON `product_attribute_value` (name, deleted)");
        $this->exec("DROP INDEX IDX_LOCALE_PARENT_ID ON `product_attribute_value`");
        $this->exec("ALTER TABLE `product_attribute_value` DROP locale_parent_id");
        echo ' Done!' . PHP_EOL;

        echo ' Migrate ProductFamilyAttribute DB schema... ';
        $this->exec("DROP INDEX IDX_ATTRIBUTE_TYPE ON `product_family_attribute`");
        $this->exec("DROP INDEX IDX_PRODUCT_FAMILY ON `product_family_attribute`");
        $this->exec("DROP INDEX IDX_ATTRIBUTE ON `product_family_attribute`");
        $this->exec("DROP INDEX IDX_IS_REQUIRED ON `product_family_attribute`");
        $this->exec("DROP INDEX IDX_SCOPE ON `product_family_attribute`");
        $this->exec("DROP INDEX IDX_LOCALE ON `product_family_attribute`");
        $this->exec("ALTER TABLE `product_family_attribute` DROP attribute_type, DROP locale, ADD name VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci");
        $this->exec("CREATE INDEX IDX_NAME ON `product_family_attribute` (name, deleted)");
        $this->exec("DROP INDEX IDX_LOCALE_PARENT_ID ON `product_family_attribute`");
        $this->exec("ALTER TABLE `product_family_attribute` DROP locale_parent_id");
        echo ' Done!' . PHP_EOL;

        if (!empty($this->errors)) {
            echo ' ' . $this->errors . ' requests failed. Please, refer to the log file for details.' . PHP_EOL;
        }
    }

    /**
     * @param string $sql
     * @param array  $inputParams
     */
    protected function exec(string $sql, array $inputParams = []): void
    {
        // prepare params
        $params = null;
        if (!empty($inputParams)) {
            $params = [];
            foreach ($inputParams as $key => $value) {
                $params[':' . $key] = $value;
            }
        }

        try {
            $sth = $this
                ->getPDO()
                ->prepare($sql);
            $sth->execute($params);
        } catch (\PDOException $e) {
            $GLOBALS['log']->error('Migration of PIM (3.13.0): ' . $e->getMessage() . ' | ' . $sql);
            $this->errors++;
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

    /**
     * @param array $row
     *
     * @return array
     */
    protected function cpRow(array $row): array
    {
        $result = [];
        foreach ($row as $name => $value) {
            if (!empty($value)) {
                $result[$name] = $value;
            }
        }

        return $result;
    }
}
