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
use Beibob\Blibs\UserStore;
use Elca\Elca;
use Elca\Security\ElcaAccess;

/**
 * Builds the navigation
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaNavigationMetaView extends HtmlView
{
    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_nav_meta');
    }
    // End beforeRender


    /**
     * Builds the content navigation to the left
     *
     * @param  -
     *
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->getElementById('meta');

        $User = UserStore::getInstance()->getUser();
        if ($User->getFirstname() && $User->getLastName())
            $name = trim($User->getFirstname() . ' ' . $User->getLastname());
        elseif ($User->getFirstname() && !$User->getLastname())
            $name = trim($User->getFirstname());
        else
            $name = $User->getAuthName();

        if ($User->isInitialized()) {

            // Handbook
            $Container->appendChild($this->getLi([], $this->getText('|')));
            $Container->appendChild($this->getLi([], $this->getA(['href' => '/handbook/', 'class' => 'no-xhr', 'target' => '_blank'], ucfirst(t('Handbuch')))));

            // Administration
            if ($User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN)) {
                $Container->appendChild($this->getLi([], $this->getText('|')));
                $link = $this->getA(['href' => '/admin/', 'class' => 'page no-xhr'], ucfirst(t('Administration')));
                $Container->appendChild($this->getLi([], $link));
            }

            // Logout
            $Container->appendChild($this->getLi([], $this->getText('|')));
            $Logout = $this->getLi([], $this->getA(['href' => '/login/logout/', 'class' => 'no-xhr'], ucfirst(t('Abmelden'))));
            $Container->appendChild($Logout);

            // My Profile
            $Container->appendChild($this->getLi([], $this->getText('|')));
            if (ElcaAccess::getInstance()->isOrganisation())
            {
                $Li = $this->getLi([], $name);
            }
            else
            {
                $Li = $this->getLi([], t('Hallo') . ' ');
                $UserLink = $this->getA(['href' => '/users/' . $User->getId() . '/', 'class' => 'no-xhr page'], $name);
                $Li->appendChild($UserLink);
            }
            $Container->appendChild($Li);

        } else {

            // Abmelden
            $Container->appendChild($this->getLi([], $this->getText('|')));
            $Container->appendChild($this->getLi([], $this->getA(['href' => '/login/', 'class' => 'no-xhr'], ucfirst(t('Anmelden')))));
        }
    }
    // End beforeRender
}
// End ElcaNavigationMetaView
