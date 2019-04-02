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

namespace Espo\Modules\Pim\Core\Hooks;

use Espo\Core\Hooks\Base as BaseHook;
use Espo\ORM\Entity;
use Espo\Core\ServiceFactory;

/**
 * AbstractHook hook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractHook extends BaseHook
{
    /**
     * @var string
     */
    public static $codePattern = '/^[a-z0-9_]*$/';

    /**
     * Init
     */
    protected function init()
    {
        // parent init
        parent::init();

        // add dependecies
        $this->addDependencyList(
            [
                'serviceFactory',
                'language'
            ]
        );
    }


    /**
     * Create service
     *
     * @param string $serviceName
     *
     * @return mixed
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function createService(string $serviceName)
    {
        return $this->getServiceFactory()->create($serviceName);
    }

    /**
     * Is code unique
     *
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isCodeValid(Entity $entity): bool
    {
        // prepare result
        $result = false;

        if (!empty($entity->get('code')) && preg_match(self::$codePattern, $entity->get('code'))) {
            $result = $this->isUnique($entity, 'code');
        }

        return $result;
    }

    /**
     * Entity field is unique?
     *
     * @param Entity $entity
     * @param string $field
     *
     * @return bool
     */
    protected function isUnique(Entity $entity, string $field): bool
    {
        // prepare result
        $result = true;

        // find product
        $fundedEntity = $this->getEntityManager()
            ->getRepository($entity->getEntityName())
            ->where([$field => $entity->get($field)])
            ->findOne();

        if (!empty($fundedEntity) && $fundedEntity->get('id') != $entity->get('id')) {
            $result = false;
        }

        return $result;
    }

    /**
     * Get service factory
     *
     * @return ServiceFactory
     */
    protected function getServiceFactory(): ServiceFactory
    {
        return $this->getInjection('serviceFactory');
    }

    /**
     * Translate
     *
     * @param string $key
     *
     * @param string $label
     * @param string $scope
     *
     * @return string
     */
    protected function translate(string $key, string $label, $scope = ''): string
    {
        return $this->getInjection('language')->translate($key, $label, $scope);
    }
}
