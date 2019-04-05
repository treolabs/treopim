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

namespace Espo\Modules\Pim\Listeners;

use Espo\Core\ORM\EntityManager;
use Treo\Listeners\AbstractListener;

/**
 * Class Installer
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Composer extends AbstractListener
{

    /**
     * After installation system action
     *
     * @param array $data
     */
    public function afterInstallModule(array $data)
    {
        if (!empty($data['id']) && $data['id'] == 'Pim') {
            $this->createSystemProductFamily();
        }
    }

    /**
     * Create system product family if not exists
     */
    protected function createSystemProductFamily()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        // find system product family
        $systemProductFamily = $entityManager->getRepository('ProductFamily')->where(['isSystem' => true])->findOne();

        if (empty($systemProductFamily)) {
            // if not exists system ProductFamily - create new
            $entity = $entityManager->getEntity('ProductFamily');
            $entity->set(
                [
                    'name'     => 'Default',
                    'code'     => 'default',
                    'isSystem' => true,
                    'isActive' => true
                ]
            );

            $entityManager->saveEntity($entity);
        }
    }
}
