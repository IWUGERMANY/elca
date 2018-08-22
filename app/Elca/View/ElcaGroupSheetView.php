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

use Beibob\Blibs\Group;
use Beibob\Blibs\UserSet;
use Elca\Elca;

/**
 * Builds a group sheet
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaGroupSheetView extends ElcaSheetView
{
    /**
     * Group
     */
    private $Group;

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        // itemId
        $this->Group = Group::findById($this->get('itemId'));
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-project-sheet');

        if($this->get('hasAdminPrivileges', false))
        {
            $this->addFunction('edit', '/groups/$$itemId$$/', t('Bearbeiten'), 'default');
            $this->addFunction('delete', '/groups/delete/?id=$$itemId$$', t('Löschen'));
        }

        /**
         * Append individual content
         */
        if($this->Group->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN))
            $this->addDescription(t('Administratoren'));

        $UserSet = UserSet::findByGroupId($this->get('itemId'));

        $users = [];
        foreach($UserSet as $User)
            $users[] = $User->getIdentifier();

        $this->addInfo(join(', ', $users), t('Mitglieder'));
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaGroupSheetView
