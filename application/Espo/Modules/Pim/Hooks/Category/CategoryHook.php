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

namespace Espo\Modules\Pim\Hooks\Category;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;

/**
 * Class CategoryHook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CategoryHook extends \Espo\Modules\Pim\Hooks\Product\ProductHook
{
    /**
     * @param Entity $entity
     * @param array  $params
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $params = [])
    {
        // is code valid
        if (!$this->isCodeValid($entity)) {
            throw new BadRequest($this->translate('Code is invalid', 'exceptions', 'Global'));
        }

        if ($entity->isAttributeChanged('categoryParentId') && count($entity->getTreeProducts()) > 0) {
            throw new BadRequest($this->exception('Category has linked products'));
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     * @param array  $hookData
     *
     * @throws BadRequest
     */
    public function beforeRelate(Entity $entity, array $options, array $hookData)
    {
        if ($hookData['relationName'] == 'products') {
            $this->productCategoryBeforeRelateValidation($hookData['foreignEntity'], $entity);
        }
        if ($hookData['relationName'] == 'catalogs' && !empty($entity->get('categoryParent'))) {
            throw new BadRequest($this->translate('Only root category can be linked with catalog', 'exceptions', 'Catalog'));
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     * @param array  $hookData
     *
     * @throws BadRequest
     */
    public function beforeUnrelate(Entity $entity, array $options, array $hookData)
    {
        if ($hookData['relationName'] == 'catalogs') {
            $this->catalogCategoryUnrelateValidation($hookData['foreignEntity'], $entity);
        }
    }

    /**
     * @param Entity $entity
     * @param array  $params
     *
     * @throws BadRequest
     */
    public function beforeRemove(Entity $entity, $params = [])
    {
        if (count($entity->get('categories')) > 0) {
            throw new BadRequest($this->exception("Category has child category and can't be deleted"));
        }
    }

    /**
     * @inheritdoc
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'Category');
    }
}
