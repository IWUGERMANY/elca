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
use Elca\Controller\ProcessesCtrl;
use Elca\Controller\Sanity\ProcessConfigsEolCtrl;
use Elca\Controller\Sanity\ProcessesCtrl as SanityProcessesCtrl;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessCategorySet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Elca;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Security\ElcaAccess;

/**
 * Builds the processes navigation view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaProcessesNavigationView extends HtmlView
{
    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_processes_navigation_left');

        $activeCategoryId = $this->get('activeCategoryId');

        // init database navigation
        $baseNavigation = ElcaNavigation::getInstance('processesDbs');

        $dbItems = $baseNavigation->add(t('Datenbanken'));

        /**
         * @var ElcaProcessDb[] $ProcessDbs
         */
        $access = ElcaAccess::getInstance();
        $maySeeInactiveDatabases = $access->hasAdminPrivileges() || $access->hasBetaPrivileges();
        $ProcessDbs = ElcaProcessDbSet::find(null, array('created' => 'ASC'));
        foreach($ProcessDbs as $ProcessDb) {
            if (!$maySeeInactiveDatabases && !$ProcessDb->isActive() ) continue;

            $dbItems->add($ProcessDb->getName(), null, ProcessesCtrl::class, 'databases', array('id' => $ProcessDb->getId()));
        }

        // admin dnavigation
        if ($access->hasAdminPrivileges()) {
            $adminItem = $baseNavigation->add(t('Pflege'));
            $adminItem->add(
                t('Probleme'),
                null,
                SanityProcessesCtrl::class
            );
            $adminItem->add(
                t('Baustoffe EOL'),
                null,
                ProcessConfigsEolCtrl::class
            );
        }

        /**
         * add module navigation
         */
        $Elca = Elca::getInstance();
        foreach($Elca->getAdditionalNavigations() as $ModuleNavigationInterface)
        {
            if(!$ModuleNavigation = $ModuleNavigationInterface->getProcessesNavigation())
                continue;

            $ModuleFirstItem = $ModuleNavigation->getFirstChild();
            $ModuleItem = $baseNavigation->add($ModuleFirstItem->getCaption());

            foreach($ModuleFirstItem->getChildren() as $ChildItem)
                $ModuleItem->add($ChildItem->getCaption(), $ChildItem->getModule(), $ChildItem->getCtrlName(), $ChildItem->getAction(), $ChildItem->getArgs(), $ChildItem->getData());
        }

        $this->assign('processDbs', $baseNavigation);

        // init category navigation
        $CatNavigation = ElcaNavigation::getInstance('processCategories');
        $ProcessCategories = ElcaProcessCategorySet::findByParent(ElcaProcessCategory::findRoot());

        foreach($ProcessCategories as $Category)
        {
            $Item = $CatNavigation->add($Category->getRefNum().' '. t($Category->getName()));
            foreach(ElcaProcessCategorySet::findByParent($Category) as $ChildCategory)
            {
                $ChildItem = $Item->add($ChildCategory->getRefNum().' '. t($ChildCategory->getName()), null, ProcessesCtrl::class, 'list', ['c' => $ChildCategory->getNodeId()]);

                if($ChildCategory->getNodeId() == $activeCategoryId)
                    $ChildItem->setActive();
            }
        }

        $this->assign('processCategories', $CatNavigation);
    }
    // End init
}
// End ElcaProcessesNavigationView
