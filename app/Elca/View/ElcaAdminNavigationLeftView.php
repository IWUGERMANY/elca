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
use Elca\Controller\Admin\BenchmarksCtrl;
use Elca\Controller\Admin\BauteilkatalogCtrl;
use Elca\Controller\AdminSvgPatternsCtrl;
use Elca\Elca;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Security\ElcaAccess;

/**
 * Builds the user navigation
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaAdminNavigationLeftView extends HtmlView
{
    /**
     * Admin navigation name
     */
    const ADMIN_NAVIGATION_NAME = 'adminNav';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $access = ElcaAccess::getInstance();

        $navigation = ElcaNavigation::getInstance(
            self::ADMIN_NAVIGATION_NAME,
            $this->get('activeCtrlName'),
            $this->get('activeCtrlAction'),
            $this->get('activeCtrlArgs')
        );

        if ($access->hasAdminPrivileges()) {
            $navItem = $navigation->add(t('Nutzerverwaltung'));
			$navItem->add(t('Benutzer'), 'elca', 'Elca\Controller\UsersCtrl');
            $navItem->add(t('Benutzer aktiv'), 'elca', 'Elca\Controller\UsersCtrl',null,['status'=>'confirmed']);
			$navItem->add(t('Benutzer inaktiv'), 'elca', 'Elca\Controller\UsersCtrl',null,['status'=>'requested']);
			$navItem->add(t('Benutzer nicht aktualisiert'), 'elca', 'Elca\Controller\UsersCtrl',null,['status'=>'legacy']);
			$navItem->add(t('Benutzer gesperrt'), 'elca', 'Elca\Controller\UsersCtrl',null,['status'=>'locked']);
			$navItem->add(t('Gruppen'), 'elca', 'Elca\Controller\GroupsCtrl');

			$navItem = $navigation->add(t('Nutzerbenachrichtigung'));
			$navItem->add(t('Benutzer ohne login - Mail'), 'elca', 'Elca\Controller\UsersMailCtrl',null,['status'=>'nologin']);

            $navItem = $navigation->add(t('Schraffuren'));
            $navItem->add(t('Verwaltung'), 'elca', AdminSvgPatternsCtrl::class, 'list');
            $navItem->add(t('Zuordnung'), 'elca', AdminSvgPatternsCtrl::class, 'assignments');

            $navItem = $navigation->add(t('Benchmarks'));
            $navItem->add(t('Systeme'), 'elca', BenchmarksCtrl::class, 'systems');
            
            $navItem = $navigation->add(t('Bauteilkatalog'));
            $navItem->add(t('PDF erstellen'), 'elca', BauteilkatalogCtrl::class );
            
            $this->assign('navigation', $navigation);

            /**
             * add module navigation
             */
            $elca = Elca::getInstance();
            foreach ($elca->getAdditionalNavigations() as $ModuleNavigationInterface) {
                if(!$ModuleNavigation = $ModuleNavigationInterface->getAdminNavigation())
                    continue;

                $moduleFirstItem = $ModuleNavigation->getFirstChild();
                $moduleItem = $navigation->add($moduleFirstItem->getCaption());

                foreach($moduleFirstItem->getChildren() as $childItem)
                    $moduleItem->add(
                        $childItem->getCaption(),
                        $childItem->getModule(),
                        $childItem->getCtrlName(),
                        $childItem->getAction(),
                        $childItem->getArgs(),
                        $childItem->getData()
                    );
            }
        }

        $this->assign('mainNav', $navigation);
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called before render
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'navLeft']));

        $Include = $Container->appendChild($this->createElement('include'));
        $Include->setAttribute('name', 'Elca\View\ElcaNavigationLeftView');
		$Include->setAttribute('navigation', '$$mainNav$$');
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaAdminNavigationLeftView
