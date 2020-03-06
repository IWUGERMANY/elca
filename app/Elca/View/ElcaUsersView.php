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

use Beibob\Blibs\HtmlView;
use Beibob\Blibs\UserSet;
use Beibob\Blibs\User;
use Elca\Security\ElcaAccess;

/**
 * Builds the users view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaUsersView extends HtmlView
{
    /**
     * Lädt ein Template
     */
	
	//	array where clause Table Users
	private $showStatus = null;
	private $initValues = null;
	 
    public function __construct(array $args = [])
    {
		parent::__construct('elca_users');
		
		if(!is_null($args) && isset($args['status'])) {
			$this->showStatus = $args['status'];
		}
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Renders the view
     */
    protected function beforeRender()
    {
		switch ($this->showStatus) {
			case 'requested': 	$this->initValues['status'] = User::STATUS_REQUESTED; break;
			case 'confirmed': 	$this->initValues['status'] = User::STATUS_CONFIRMED; break;
			case 'legacy': 		$this->initValues['status'] = User::STATUS_LEGACY; break;
			case 'locked':		$this->initValues['is_locked'] = true; break;
			case 'nologin': 	$this->initValues['login_time'] = NULL; break;
        }
		
        $Users = UserSet::find($this->initValues, ['CASE WHEN lastname <> \'\' THEN lower(lastname) ELSE lower(auth_name) END' => 'ASC']);

        if(!count($Users))
            return;

        $NoUsersElt = $this->getElementById('no-users');
        $NoUsersElt->parentNode->removeChild($NoUsersElt);
		
		// user statistics
		$userStatistics = [
			'confirmed' => 0,
			'requested' => 0,
			'locked' => 0,
			'legacy' => 0,
			'count' => 0,
		];
		
        $Access = ElcaAccess::getInstance();
        $hasAdminPrivileges = $Access->hasAdminPrivileges();
        $currentUserId = $Access->getUserId();

        $Ul = $this->getElementById('elca-users')->appendChild($this->getUl());
        foreach($Users as $User)
        {
            $Li = $Ul->appendChild($this->getLi(['id' => 'user-' . $User->getId() ]));

            $Include = $Li->appendChild($this->createElement('include'));
            $Include->setAttribute('name', 'Elca\View\ElcaUserSheetView');
            $Include->setAttribute('itemId', $User->getId());
            $Include->setAttribute('headline', $User->getIdentifier(true));
            $Include->setAttribute('canEdit', $Access->canEditUser($User));
            $Include->setAttribute('hasAdminPrivileges', $hasAdminPrivileges);
            $Include->setAttribute('currentUserId', $currentUserId);
			
			// fill the statistics
			$userStatistics['count']++;
			// count the users by status
            switch ($User->getStatus()) {
                case User::STATUS_REQUESTED:
					$userStatistics['requested']++;
                    break;
                case User::STATUS_CONFIRMED:
					$userStatistics['confirmed']++;
                    break;
                case User::STATUS_LEGACY:
					$userStatistics['legacy']++;
                    break;
            }
			// count the locked user accounts
			if ($User->isLocked()) {
				$userStatistics['locked']++;
			}
        }

		$this->assign('userConfirmed', $userStatistics['confirmed']);
		$this->assign('userRequested', $userStatistics['requested']);
		$this->assign('userLegacy', $userStatistics['legacy']);
		$this->assign('userLocked', $userStatistics['locked']);
		$this->assign('userCount', $userStatistics['count']);
		
    }
    // End render

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaUsersView
