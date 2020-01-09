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

use Pim\Entities\Channel;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class SettingsController
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class SettingsController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeActionUpdate(Event $event): void
    {
        // open session
        session_start();

        // set to session
        $_SESSION['isMultilangActive'] = $this->getConfig()->get('isMultilangActive', false);
        $_SESSION['inputLanguageList'] = $this->getConfig()->get('inputLanguageList', []);
    }

    /**
     * @param Event $event
     */
    public function afterActionUpdate(Event $event): void
    {
        $this->updateChannelsLocales();

        // cleanup
        unset($_SESSION['isMultilangActive']);
        unset($_SESSION['inputLanguageList']);
    }

    /**
     * Update Channel locales field
     */
    protected function updateChannelsLocales(): void
    {
        if (!$this->getConfig()->get('isMultilangActive', false)) {
            $this->getEntityManager()->nativeQuery("UPDATE channel SET locales=NULL WHERE 1");
        } elseif (!empty($_SESSION['isMultilangActive'])) {
            /** @var array $deletedLocales */
            $deletedLocales = array_diff($_SESSION['inputLanguageList'], $this->getConfig()->get('inputLanguageList', []));

            /** @var Channel[] $channels */
            $channels = $this
                ->getEntityManager()
                ->getRepository('Channel')
                ->select(['id', 'locales'])
                ->find();

            if (count($channels) > 0) {
                foreach ($channels as $channel) {
                    if (!empty($locales = $channel->get('locales'))) {
                        $newLocales = [];
                        foreach ($locales as $locale) {
                            if (!in_array($locale, $deletedLocales)) {
                                $newLocales[] = $locale;
                            }
                        }
                        $channel->set('locales', $newLocales);
                        $this->getEntityManager()->saveEntity($channel);
                    }
                }
            }
        }
    }
}