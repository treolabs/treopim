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

namespace Espo\Modules\Pim\Core\Hooks;

use Espo\Core\CronManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;

/**
 * AbstractImageHook hook
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractImageHook extends AbstractHook
{
    /**
     * @var string
     */
    protected $entityName = null;

    /**
     * @param $entity
     *
     * @return string
     */
    abstract protected function getCondition(Entity $entity);

    /**
     * Before Save hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        $this->clearUnusedFields($entity);

        // is asset code valid?
        if (!$this->isAssetCodeValid($entity)) {
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
     * After Save hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        if (empty($options['isImageDataSaved'])) {
            $this->setImageData($entity);
        }
    }

    /**
     * Set data for image
     *
     * @param Entity $entity
     */
    protected function setImageData($entity)
    {
        // create job
        if (!is_null($this->entityName)) {
            $job = $this->getEntityManager()->getEntity('Job');
            $job->set(
                [
                    'name'        => 'Set image data',
                    'status'      => CronManager::PENDING,
                    'executeTime' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'serviceName' => 'ImageData',
                    'method'      => 'cron',
                    'data'        => ['entityName' => $this->entityName, 'entityId' => $entity->get('id')],
                ]
            );
            $this->getEntityManager()->saveEntity($job);
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isAssetCodeValid(Entity $entity): bool
    {
        // prepare result
        $result = false;

        if (preg_match(self::$codePattern, $entity->get('name'))) {
            $result = $this->isUnique($entity, 'name');
        }

        return $result;
    }

    /**
     * Return Oldest image
     *
     * @param $entity
     *
     * @return mixed
     */
    protected function getOldestImage(Entity $entity)
    {
        return $this->getEntityManager()
            ->getRepository($entity->getEntityType())
            ->where($this->getCondition($entity))
            ->order('createdAt', 'ASC')
            ->findOne();
    }

    /**
     * Save entity
     *
     * @param Entity $entity
     */
    protected function saveEntity(Entity $entity)
    {
        $this->getEntityManager()->saveEntity($entity, []);
    }

    /**
     * Clean unused fields
     *
     * @param Entity $entity
     */
    protected function clearUnusedFields(Entity $entity)
    {
        if ($entity->isNew()) {
            switch ($entity->get('type')) {
                case 'Link':
                    $image = $entity->get('image');
                    if ($image instanceof Entity) {
                        $this->getEntityManager()->removeEntity($image);
                        $entity->set(['imageId' => null]);
                    }
                    break;
                case 'File':
                    $entity->set(['imageLink' => null]);
                    break;
            }
        }
    }
}
