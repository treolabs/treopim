<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Pim\ImportHandlers;

use Espo\Core\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;
use Treo\Core\Container;
use Treo\Core\ServiceFactory;
use Treo\Core\Utils\Metadata;
use Treo\Core\Utils\Util;

/**
 * Class AbstractHandler
 *
 * @author r.zablodskiy@treolabs.com
 */
abstract class AbstractHandler
{
    /**
     * @var null
     */
    protected $container = null;

    /**
     * AbstractHandler constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $fileData
     * @param array $data
     *
     * @return bool
     */
    abstract public function run(array $fileData, array $data): bool;

    /**
     * @param array  $configuration
     * @param string $idField
     *
     * @return array|null
     */
    protected function getIdRow(array $configuration, string $idField = null): ?array
    {
        if (!empty($idField)) {
            foreach ($configuration as $row) {
                if ($row['name'] == $idField) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * @param string $entityType
     * @param string $name
     * @param array  $ids
     *
     * @return mixed
     */
    protected function getExists(string $entityType, string $name, array $ids): array
    {
        // get data
        $data = $this
            ->getEntityManager()
            ->getRepository($entityType)
            ->select(['id', $name])
            ->where([$name => $ids])
            ->find();

        $result = [];

        if (count($data) > 0) {
            foreach ($data as $entity) {
                $result[$entity->get($name)] = $entity->get('id');
            }
        }

        return $result;
    }

    /**
     * @param \stdClass $inputRow
     * @param string    $entityType
     * @param array     $item
     * @param array     $row
     * @param string    $delimiter
     */
    protected function convertItem(\stdClass $inputRow, string $entityType, array $item, array $row, string $delimiter)
    {
        // get metadata
        $metadata = $this->container->get('metadata');

        // get type
        if (empty($type = $metadata->get(['entityDefs', $entityType, 'fields', $item['name'], 'type']))) {
            return null;
        }

        // delegate
        if (!empty($converter = $metadata->get(['import', 'simple', 'fields', $type, 'converter']))) {
            return (new $converter($this->container))->convert($inputRow, $entityType, $item, $row, $delimiter);
        }

        // prepare value
        if (is_null($item['column']) || empty($row[$item['column']])) {
            $value = $item['default'];
            if (!empty($value) && is_string($value)) {
                $value = str_replace("{{hash}}", Util::generateId(), $value);
            }
        } else {
            $value = $row[$item['column']];
        }

        // set
        $inputRow->{$item['name']} = $value;
    }

    /**
     * @param string $entityName
     * @param string $importResultId
     * @param string $type
     * @param string $row
     * @param string $data
     *
     * @return Entity
     *
     * @throws Error
     */
    protected function log(string $entityName, string $importResultId, string $type, string $row, string $data): Entity
    {
        $serviceFactory = $this->getServiceFactory();
        $name = 'ImportTypeSimple';

        if ($serviceFactory->checkExists($name)) {
            $service = $serviceFactory->create($name);

            if (method_exists($service, 'log')) {
                return $service->log($entityName, $importResultId, $type, $row, $data);
            }
        }

        return null;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    /**
     * @param string $entityType
     *
     * @return ServiceFactory
     */
    protected function getServiceFactory(): ServiceFactory
    {
        return $this->container->get('serviceFactory');
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }
}