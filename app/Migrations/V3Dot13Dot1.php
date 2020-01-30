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

/**
 * Migration class for version 3.13.1
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot13Dot1 extends Base
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
        echo ' Update DB table `category`... ';
        $this->exec(
            "ALTER TABLE `category` ADD scope VARCHAR(255) DEFAULT 'Global' COLLATE utf8mb4_unicode_ci;UPDATE category SET scope='Global' WHERE 1"
        );
        echo ' Done!' . PHP_EOL;

        echo ' Create DB table `category_channel_linker`... ';
        $this->exec(
            "CREATE TABLE `category_channel_linker` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `category_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `channel_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_7C5F29FE12469DE2` (category_id), INDEX `IDX_7C5F29FE72F5A1AA` (channel_id), UNIQUE INDEX `UNIQ_7C5F29FE12469DE272F5A1AA` (category_id, channel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;DROP INDEX id ON `category_channel_linker`"
        );
        echo ' Done!' . PHP_EOL;

        echo ' Create DB table `product_category_linker`... ';
        $this->exec(
            "CREATE TABLE `product_category_linker` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `category_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `product_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `sorting` INT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_5E4F9F5B12469DE2` (category_id), INDEX `IDX_5E4F9F5B4584665A` (product_id), UNIQUE INDEX `UNIQ_5E4F9F5B12469DE24584665A` (category_id, product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;DROP INDEX id ON `product_category_linker`"
        );
        echo ' Done!' . PHP_EOL;

        echo ' Migrate DATA: ' . PHP_EOL;
        $pcs = $this->fetchAll("SELECT product_id,category_id,sorting FROM product_category WHERE deleted=0");
        foreach ($pcs as $pc) {
            $this->exec("INSERT INTO product_category_linker (product_id,category_id,sorting) VALUES (:product_id,:category_id,:sorting)", $pc);
        }
        echo ' Done!' . PHP_EOL;

        echo ' DROP DB table `product_category`... ';
        $this->exec("DROP TABLE product_category");
        echo ' Done!' . PHP_EOL;

        echo ' DROP DB table `product_category_channel`... ';
        $this->exec("DROP TABLE product_category_channel");
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
        echo ' Create DB table `product_category`... ';
        $this->exec(
            "CREATE TABLE `product_category` (`id` VARCHAR(24) NOT NULL COLLATE utf8mb4_unicode_ci, `name` VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, `sorting` INT DEFAULT '0' COLLATE utf8mb4_unicode_ci, `scope` VARCHAR(255) DEFAULT 'Global' COLLATE utf8mb4_unicode_ci, `created_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_at` DATETIME DEFAULT NULL COLLATE utf8mb4_unicode_ci, `product_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `category_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `created_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `modified_by_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `owner_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `assigned_user_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, INDEX `IDX_PRODUCT_ID` (product_id), INDEX `IDX_CATEGORY_ID` (category_id), INDEX `IDX_CREATED_BY_ID` (created_by_id), INDEX `IDX_MODIFIED_BY_ID` (modified_by_id), INDEX `IDX_OWNER_USER_ID` (owner_user_id), INDEX `IDX_ASSIGNED_USER_ID` (assigned_user_id), INDEX `IDX_OWNER_USER` (owner_user_id, deleted), INDEX `IDX_ASSIGNED_USER` (assigned_user_id, deleted), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB"
        );
        echo ' Done!' . PHP_EOL;

        echo ' Create DB table `product_category_channel`... ';
        $this->exec(
            "CREATE TABLE `product_category_channel` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `channel_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `product_category_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_EBADAAC572F5A1AA` (channel_id), INDEX `IDX_EBADAAC5BE6903FD` (product_category_id), UNIQUE INDEX `UNIQ_EBADAAC572F5A1AABE6903FD` (channel_id, product_category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB"
        );
        echo ' Done!' . PHP_EOL;

        echo ' Migrate DATA: ' . PHP_EOL;
        $pcs = $this->fetchAll("SELECT id,product_id,category_id,sorting FROM product_category_linker WHERE deleted=0");
        foreach ($pcs as $pc) {
            $this->exec("INSERT INTO product_category (id,product_id,category_id,sorting) VALUES (:id,:product_id,:category_id,:sorting)", $pc);
        }
        echo ' Done!' . PHP_EOL;

        echo ' DROP DB table `product_category_linker`... ';
        $this->exec("DROP TABLE product_category_linker");
        echo ' Done!' . PHP_EOL;

        echo ' DROP DB table `category_channel_linker`... ';
        $this->exec("DROP TABLE category_channel_linker");
        echo ' Done!' . PHP_EOL;

        echo ' Update DB table `category`... ';
        $this->exec("ALTER TABLE `category` DROP scope");
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
            $GLOBALS['log']->error('Migration of PIM (3.13.1): ' . $e->getMessage() . ' | ' . $sql);
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
}
