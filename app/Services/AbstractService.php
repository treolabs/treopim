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

namespace Pim\Services;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Treo\Core\EventManager\Event;

/**
 * Class of AbstractService
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractService extends Base
{
    /**
     * @inheritDoc
     */
    public function linkEntity($id, $link, $foreignId)
    {
        if ($link == 'pimImages' && in_array($this->entityName, ['Product', 'Category'])) {
            // prepare repository
            $repository = $this->getEntityManager()->getRepository('PimImage');

            // get foreign
            if (empty($foreign = $repository->where(['id' => $foreignId])->findOne(['withDeleted' => true]))) {
                throw new NotFound();
            }

            // prepare image
            $image = $repository->get();

            // set data
            $image->set('name', $foreign->get('name'));
            $image->set('imageId', $foreign->get('imageId'));
            $image->set('imageName', $foreign->get('imageName'));
            $image->set(lcfirst($this->entityName) . 'Id', $id);

            // save
            $this->getEntityManager()->saveEntity($image);

            return true;
        }

        return parent::linkEntity($id, $link, $foreignId);
    }

    /**
     * Get ACL "where" SQL
     *
     * @param string $entityName
     * @param string $entityAlias
     *
     * @return string
     */
    public function getAclWhereSql(string $entityName, string $entityAlias): string
    {
        // prepare sql
        $sql = '';

        if (!$this->getUser()->isAdmin()) {
            // prepare data
            $userId = $this->getUser()->get('id');

            if ($this->getAcl()->checkReadOnlyOwn($entityName)) {
                $sql .= " AND $entityAlias.assigned_user_id = '$userId'";
            }
            if ($this->getAcl()->checkReadOnlyTeam($entityName)) {
                $sql .= " AND $entityAlias.id IN ("
                    . "SELECT et.entity_id "
                    . "FROM entity_team AS et "
                    . "JOIN team_user AS tu ON tu.team_id=et.team_id "
                    . "WHERE et.deleted=0 AND tu.deleted=0 "
                    . "AND tu.user_id = '$userId' AND et.entity_type='$entityName')";
            }
        }

        return $sql;
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();

        // add dependencies
        $this->addDependency('language');
        $this->addDependency('eventManager');
        $this->addDependency('metadata');
    }

    /**
     * Get translated message
     *
     * @param string $label
     * @param string $category
     * @param string $scope
     * @param null   $requiredOptions
     *
     * @return string
     */
    protected function getTranslate(string $label, string $category, string $scope, $requiredOptions = null): string
    {
        return $this
            ->getInjection('language')
            ->translate($label, $category, $scope, $requiredOptions);
    }

    /**
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    protected function dispatch(string $target, string $action, array $data = []): array
    {
        return $this
            ->getInjection('eventManager')
            ->dispatch($target, $action, new Event($data));
    }
}
