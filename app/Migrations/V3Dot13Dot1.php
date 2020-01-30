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
        echo ' Create DB table `product_category_linker`... ';
        $this->exec(
            "CREATE TABLE `product_category_linker` (`id` INT AUTO_INCREMENT NOT NULL UNIQUE COLLATE utf8mb4_unicode_ci, `category_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `product_id` VARCHAR(24) DEFAULT NULL COLLATE utf8mb4_unicode_ci, `sorting` INT DEFAULT NULL COLLATE utf8mb4_unicode_ci, `deleted` TINYINT(1) DEFAULT '0' COLLATE utf8mb4_unicode_ci, INDEX `IDX_5E4F9F5B12469DE2` (category_id), INDEX `IDX_5E4F9F5B4584665A` (product_id), UNIQUE INDEX `UNIQ_5E4F9F5B12469DE24584665A` (category_id, product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB"
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