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

namespace Pim\Listeners;

use Espo\ORM\Entity;
use Espo\Core\ServiceFactory;
use Treo\Listeners\AbstractListener;

/**
 * Class AbstractListener
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractEntityListener extends AbstractListener
{
    /**
     * @var string
     */
    public static $codePattern = '/^[a-z0-9_]*$/';

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

        // find
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
        return $this->getContainer()->get('serviceFactory');
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
        return $this->getContainer()->get('language')->translate($key, $label, $scope);
    }
}
