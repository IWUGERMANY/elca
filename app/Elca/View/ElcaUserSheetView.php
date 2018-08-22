<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2016 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Elca\View;

use Beibob\Blibs\BlibsDateTime;
use Beibob\Blibs\GroupSet;
use Beibob\Blibs\User;
use Elca\Elca;

/**
 * Builds a user sheet
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaUserSheetView extends ElcaSheetView
{
    /**
     * User
     */
    private $User;


    /**
     * Init
     *
     * @param  array $args
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        // itemId
        $this->User = User::findById($this->get('itemId'));

        if ($this->User->getId() == $this->get('currentUserId'))
            $this->assign('subheadline', t('Ihr Account'));
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     *
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-project-sheet');

        if ($this->get('canEdit', false))
            $this->addFunction('edit', '/users/$$itemId$$/', t('Bearbeiten'), 'default page');

        if ($this->User->getId() != $this->get('currentUserId')) {
            if ($this->get('hasAdminPrivileges', false))
                $this->addFunction('delete', '/users/delete/?id=$$itemId$$', t('Benutzer löschen, ohne die Projektdaten zu löschen'));

            if ($this->get('hasAdminPrivileges', false))
                $this->addFunction('delete-recursive', '/users/delete/?id=$$itemId$$&recursive', t('Benutzer mitsamt seinen Projektdaten löschen'));
        }

        /**
         * Append individual content
         */
        if ($this->User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN))
            $this->addDescription(t('Administrator'));

        $authIdent = $this->User->getEmail();
        if ($this->User->getEmail() != $this->User->getAuthName())
            $authIdent .= ' (' . $this->User->getAuthName() . ')';
        $this->addInfo($authIdent, t('Benutzername'), null, true);


        /**
         * Groups
         */
        $Groups = GroupSet::findByUserId($this->get('itemId'));
        if ($Groups->count())
            $this->addInfo($Groups->join(', ', 'name'), t('Gruppen'));

        /**
         * Last Login
         */
        $LastLogin = BlibsDateTime::factory($this->User->getLoginTime());
        $this->addInfo($LastLogin->isValid() ? $LastLogin->getDateTimeString(t('DATETIME_FORMAT_DMY') . ' ' . t('DATETIME_FORMAT_HI')) : t('nie'), t('Letzte Anmeldung'));

        /**
         * Status
         */
        if ($this->User->isLocked()) {
            $statusInfo = 'Gesperrt';
            $css = 'locked';
        } else {
            switch ($this->User->getStatus()) {
                case User::STATUS_REQUESTED:
                    $statusInfo = 'Beantragt';
                    $css = 'requested';
                    break;
                case User::STATUS_CONFIRMED:
                    $statusInfo = 'Aktiv';
                    $css = 'confirmed';
                    break;
                case User::STATUS_LEGACY:
                    $statusInfo = 'Nicht aktualisiert';
                    $css = 'locked';
                    break;
                default:
                    $statusInfo = 'Unbekannt';
                    $css = 'locked';
                    break;
            }
        }

        $Li = $this->addInfo($statusInfo, 'Status');
        $Li->setAttribute('class', trim($Li->getAttribute('class') . ' ' . $css));
    }
    // End beforeRender
}
// End ElcaUserSheetView
