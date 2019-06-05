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

namespace Espo\Modules\Pim\Hooks\Catalog;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\BadRequest;

/**
 * Class CatalogHook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class CatalogHook extends \Espo\Modules\Pim\Core\Hooks\AbstractHook
{
    /**
     * Before save
     *
     * @param Entity $entity
     * @param array $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, array $options = [])
    {
        if (!$this->isCodeValid($entity)) {
            throw new BadRequest(
                $this->translate('Code is invalid', 'exceptions', 'Global')
            );
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
        if ($hookData['relationName'] == 'categories' && !empty($hookData['foreignEntity']->get('categoryParent'))) {
            throw new BadRequest($this->exception('Only root category can be linked with catalog'));
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
        if ($hookData['relationName'] == 'categories') {
            $this->catalogCategoryUnrelateValidation($entity, $hookData['foreignEntity']);
        }
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterRemove(Entity $entity, $options = [])
    {
        // get products
        $products = $entity->get('products');

        // delete products
        if (count($products) > 0) {
            foreach ($products as $product) {
                $this->getEntityManager()->removeEntity($product);
            }
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'Catalog');
    }
}
