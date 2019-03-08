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

namespace Elca\Controller\Report;

use Beibob\Blibs\Session;
use Beibob\Blibs\SessionNamespace;
use Elca\Controller\BaseReportsCtrl;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaProjectNavigationView;
use Elca\View\ElcaProjectReportsNavigationLeftView;
use Elca\View\Report\EpdTypesView;
use Elca\View\Report\ExtantSavingsView;

class ExtantSavingsCtrl extends BaseReportsCtrl
{
    /**
     * SessionNamespace
     */
    private $sessionNamespace;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->checkProjectAccess()) {
            return;
        }

        $this->Osit->clear();

        $projectId              = $this->Elca->getProjectId();
        $this->sessionNamespace = $this->Session->getNamespace('elca.reports.'.$projectId, Session::SCOPE_PERSISTENT);
    }
    // End init


    /**
     * Default action
     */
    protected function defaultAction()
    {
        $this->savingsAction();
    }
    // End defaultAction

    /**
     * systems action
     */
    protected function savingsAction()
    {
        $view = $this->setView(new ExtantSavingsView());
        $view->assign('projectVariantId', $this->Elca->getProjectVariantId());

        $this->Osit->add(new ElcaOsitItem(t('Eingesparte Umweltwirkungen'), null, t('Auswertung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());

        /**
         * Summary is the default action, highlight current nav item in project navigation view
         */
        $view = $this->addView(new ElcaProjectNavigationView());
        $view->assign('activeCtrlName', get_class());
    }
    // End systemsAction

    /**
     * @return SessionNamespace
     */
    protected function getSessionNamespace()
    {
        return $this->sessionNamespace;
    }
}
