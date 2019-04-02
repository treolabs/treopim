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

namespace Espo\Modules\Pim\Hooks\ProductFamily;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Modules\Pim\Core\Hooks\AbstractHook;
use Espo\ORM\Entity;

/**
 * ProductFamilyHook hook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductFamilyHook extends AbstractHook
{

    /**
     * Before save action
     *
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        if (!$this->isCodeValid($entity)) {
            throw new BadRequest(
                $this->translate(
                    'Code is invalid',
                    'exceptions',
                    'Global'
                )
            );
        }
    }

    /**
     * After Save Entity hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        $this->setParentAttributes($entity);
    }

    /**
     * Before remove Entity hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeRemove(Entity $entity, $options = [])
    {
        $this->checkIsSystem($entity);
    }

    /**
     * Set attributes from parent ProductFamily
     *
     * @param Entity $entity
     */
    protected function setParentAttributes(Entity $entity)
    {
        if (!$entity->get('isSystem')
            && ($entity->isNew())
        ) {
            // prepare repository
            $repository = $this->getEntityManager()->getRepository('ProductFamily');

            // get parent
            $parent = $repository
                ->where(['id' => $entity->get('productFamilyTemplateId')])
                ->findOne();

            if (!empty($parent) && !empty($attributes = $parent->get('attributes'))) {
                foreach ($attributes as $attribute) {
                    $repository->relate($entity, 'attributes', $attribute);
                }
            }
        }
    }

    /**
     * Check if Entity is system
     *
     * @param Entity $entity
     *
     * @throws Forbidden
     */
    protected function checkIsSystem(Entity $entity)
    {
        if ($entity->get('isSystem')) {
            throw new Forbidden();
        }
    }
}
