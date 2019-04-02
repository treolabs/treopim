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

namespace Espo\Modules\Pim\Core\Repositories;

use \Espo\Core\Templates\Repositories\Base;
use \Espo\ORM\EntityCollection;
use \Espo\ORM\Entity;

/**
 * AbstractRepositories
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class AbstractRepositories extends Base
{

    /**
     * Find linked Entities with the use of custom relation
     *
     * @param string $link         name of the related entities
     * @param array  $selectParams
     *
     * @return EntityCollection
     */
    public function findCustomLinkedEntities(string $link, array $selectParams = []): EntityCollection
    {
        $pdo = $this->getPDO();
        $query = $this->getEntityManager()->getQuery();
        // get sql query
        $sql = $query->createSelectQuery($link, $selectParams);
        // execute
        $sth = $pdo->query($sql);
        $result = $sth->fetchAll();

        return new EntityCollection($result, $link, $this->entityFactory);
    }


    /**
     * Get the total number of entities using a custom relation
     *
     * @param string $link         name of the related entities
     * @param array  $selectParams
     *
     * @return int
     */
    public function getCustomTotal(string $link, array $selectParams = []): int
    {
        // set select
        $selectParams['select'] = ['COUNT:id'];
        // remove limitation
        unset($selectParams['limit'], $selectParams['offset']);
        $pdo = $this->getPDO();
        $query = $this->getEntityManager()->getQuery();
        // get sql query
        $sql = $query->createSelectQuery($link, $selectParams);
        // execute
        $ps = $pdo->query($sql);
        $result = $ps->fetchColumn();

        return (int)$result;
    }

    /**
     * Call beforeUnrelate hook method
     *
     * @param Entity $entity
     * @param        $relationName
     * @param        $foreign
     * @param array  $options
     */
    protected function beforeUnrelate(Entity $entity, $relationName, $foreign, array $options = array())
    {
        parent::beforeUnrelate($entity, $relationName, $foreign, $options);

        if ($foreign instanceof Entity) {
            $foreignEntity = $foreign;
            if (!$this->hooksDisabled) {
                $hookData = array(
                    'relationName'  => $relationName,
                    'foreignEntity' => $foreignEntity
                );
                $this->getEntityManager()->getHookManager()->process(
                    $this->entityType,
                    'beforeUnrelate',
                    $entity,
                    $options,
                    $hookData
                );
            }
        }
    }
}
