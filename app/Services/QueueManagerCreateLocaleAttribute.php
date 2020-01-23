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

use Pim\Entities\Attribute;
use Treo\Core\Utils\Util;
use Treo\Services\QueueManagerBase as Base;

/**
 * Class QueueManagerCreateLocaleAttribute
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class QueueManagerCreateLocaleAttribute extends Base
{
    /**
     * @inheritdoc
     */
    public function run(array $data = []): bool
    {
        /** @var Attribute $attribute */
        $attribute = $this->getEntityManager()->getEntity('Attribute', $data['id']);

        // create ProductFamilyAttribute
        $this->createLocaleProductFamilyAttributes($attribute);

        // create ProductAttributeValue
        $this->createLocaleProductAttributeValues($attribute);

        return true;
    }

    /**
     * @param Attribute $attribute
     *
     * @return bool
     */
    protected function createLocaleProductFamilyAttributes(Attribute $attribute): bool
    {
        $pfas = $this
            ->getEntityManager()
            ->getRepository('ProductFamilyAttribute')
            ->where(['attributeId' => $attribute->get('parentId')])
            ->find();

        if (count($pfas) == 0) {
            return false;
        }

        foreach ($pfas as $pfa) {
            $newEntity = $this->getEntityManager()->getRepository('ProductFamilyAttribute')->get();
            $newEntity->set($pfa->toArray());
            $newEntity->id = Util::generateId();
            $newEntity->set('attributeId', $attribute->get('id'));
            $newEntity->set('locale', $attribute->get('locale'));

            $this->getEntityManager()->saveEntity($newEntity, ['skipValidation' => true]);

            if ($pfa->get('scope') == 'Channel') {
                $channels = $pfa->get('channels');
                if (count($channels) > 0) {
                    foreach ($channels as $channel) {
                        $this->getEntityManager()->getRepository('ProductFamilyAttribute')->relate($newEntity, 'channels', $channel);
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param Attribute $attribute
     *
     * @return bool
     */
    protected function createLocaleProductAttributeValues(Attribute $attribute): bool
    {
        $pavs = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['attributeId' => $attribute->get('parentId'), 'productFamilyAttributeId' => null])
            ->find();

        if (count($pavs) == 0) {
            return false;
        }

        foreach ($pavs as $pav) {
            $newEntity = $this->getEntityManager()->getRepository('ProductAttributeValue')->get();
            $newEntity->set($pav->toArray());
            $newEntity->id = Util::generateId();
            $newEntity->set('attributeId', $attribute->get('id'));
            $newEntity->set('locale', $attribute->get('locale'));

            $this->getEntityManager()->saveEntity($newEntity, ['skipValidation' => true]);

            if ($pav->get('scope') == 'Channel') {
                $channels = $pav->get('channels');
                if (count($channels) > 0) {
                    foreach ($channels as $channel) {
                        $this->getEntityManager()->getRepository('ProductAttributeValue')->relate($newEntity, 'channels', $channel);
                    }
                }
            }
        }

        return true;
    }
}
