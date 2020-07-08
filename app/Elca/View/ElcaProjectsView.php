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

use Beibob\Blibs\Config;
use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use DOMElement;
use Elca\Db\ElcaProjectSet;
use Elca\Elca;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectsView extends HtmlView
{
    /**
     * @var object
     */
    protected $filterDO;

    protected $page;

    /**
     * @var ElcaAccess
     */
    protected $access;


    /**
     * Inits the view
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('elca_projects');
        $this->assign('projectLimit', Elca::getInstance()->getProjectLimit());

        $this->filterDO         = $this->get('filterDO', new \stdClass());
        $this->returnResultList = $this->get('returnResultList', false);

        /**
         * Current page (unused so far)
         */
        $this->page = $this->get('page', 0);

        $this->access = ElcaAccess::getInstance();
    }


    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $hasAdminPrivileges = $this->access->hasAdminPrivileges();

        // enable / disable functions
        $canCreateProjects = true;
        if (!ElcaAccess::getInstance()->canCreateProject()) {
            $createProjectFn    = $this->getElementById('createProject', true);
            $importProjectFn    = $this->getElementById('importProject', true);
            $importAssistantProjectFn = $this->getElementById('importAssistantProject', true);

            $createProjectFn->parentNode->removeChild($createProjectFn);
            $importProjectFn->parentNode->removeChild($importProjectFn);
            $importAssistantProjectFn->parentNode->removeChild($importAssistantProjectFn);
            $canCreateProjects = false;
        } else {
            $limitMessage = $this->getElementById('projectLimitMessage', true);
            $limitMessage->parentNode->removeChild($limitMessage);
            $this->getElementById('importAssistantProject', true);
        }

        // enable/disable IFC import Btn
        $environment = Environment::getInstance();
        $config      = $environment->getConfig();
        $ShowIfcProjectBtn = $config->ifcImportActive ? $config->ifcImportActive : 0;
        if(!$ShowIfcProjectBtn) {
            $NoIfcImportBtn = $this->getElementById('createIFCProject');
            $NoIfcImportBtn->parentNode->removeChild($NoIfcImportBtn);
        }

        $NoProjectsElt = $this->getElementById('no-projects');
        $NoProjectsElt->parentNode->removeChild($NoProjectsElt);

        $container = $this->getElementById('elca-projects');

        $sharedProjects = $this->getSharedProjects();
        if ($sharedProjects && $sharedProjects->count()) {
            $listContainer = $container->appendChild($this->getDiv(['class' => 'shared-projects']));
            $listContainer->appendChild($this->getH4(t('Für mich freigegebene Projekte')));
            $this->appendProjectList($sharedProjects, $listContainer, false, false, true);
        }

        if ($hasAdminPrivileges) {
            $this->appendFilterForm($container);
        }

        $ownProjects   = $this->getOwnedProjects();
        $listContainer = $container->appendChild($this->getDiv(['class' => 'my-projects']));
        $listContainer->appendChild($this->getH4(t('Meine Projekte')));
        $this->appendProjectList($ownProjects, $listContainer, $hasAdminPrivileges, $canCreateProjects);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Append Filter form
     *
     * @param  -
     * @return -
     */
    protected function appendFilterForm(DOMElement $Container)
    {
        $FilterContainer = $Container->appendChild($this->getDiv(['class' => 'sheets-filter']));

        $Form = new HtmlForm('projectsFilterForm', '/projects/');
        $Form->setAttribute('id', 'projectsFilterForm');
        $Form->addClass('filter-form');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject($this->filterDO);

        $Filter = $Form->add(new HtmlFormGroup(t('Liste einschränken')));

        $Radio = $Filter->add(new ElcaHtmlFormElementLabel('', new HtmlRadioGroup('scope')));
        $Radio->add(new HtmlRadiobox(t('Alle'), ''));
        $Radio->add(new HtmlRadiobox(t('Eigene'), 'private'));
        $Radio->add(new HtmlRadiobox(t('Referenzprojekte'), 'public'));

        $Filter->add(new ElcaHtmlSubmitButton('refresh', t('Filter aktualisieren'), true));
        $Form->appendTo($FilterContainer);
    }


    /**
     * Returns the project set
     *
     * @return ElcaProjectSet
     */
    protected function getOwnedProjects()
    {
        $filter = [];

        if ($this->access->hasAdminPrivileges()) {
            if (isset($this->filterDO->scope) && $this->filterDO->scope == 'private') {
                $filter['owner_id'] = $this->access->getUserId();
            } elseif (isset($this->filterDO->scope) && $this->filterDO->scope == 'public') {
                $filter['is_reference'] = true;
            }


            $projects = ElcaProjectSet::find($filter, ['name' => 'ASC']);
        } else {
            $projects = ElcaProjectSet::findByOwnerId($this->access->getUserId(), [], ['name' => 'ASC']);
        }

        return $projects;
    }

    /**
     * Returns the project set
     *
     * @return ElcaProjectSet
     */
    protected function getSharedProjects()
    {
        if ($this->access->hasAdminPrivileges()) {
            return null;
        }

        return ElcaProjectSet::findSharedByUserId($this->access->getUserId(), [], ['name' => 'ASC']);
    }

    /**
     * @param $projects
     * @param $container
     * @param $hasAdminPrivileges
     * @param $canCreateProjects
     */
    protected function appendProjectList(
        $projects,
        $container,
        $hasAdminPrivileges = false,
        $canCreateProjects = false,
        $isShared = false
    ) {
        if (!count($projects)) {
            $container->appendChild($this->getP('Keine Projekte'));

            return;
        }

        $Ul = $container->appendChild($this->getUl(['class' => 'project-list']));
        foreach ($projects as $Project) {
            $Li = $Ul->appendChild($this->getLi(['id' => 'project-' . $Project->getId()]));

            $Include = $Li->appendChild($this->createElement('include'));
            $Include->setAttribute('name', 'Elca\View\ElcaProjectSheetView');
            $Include->setAttribute('itemId', $Project->getId());
            $Include->setAttribute('headline', $Project->getName());
            $Include->setAttribute('projectNr', $Project->getProjectNr());
            $Include->setAttribute('hasAdminPrivileges', $hasAdminPrivileges);
            $Include->setAttribute('canCreateProjects', $canCreateProjects);
            $Include->setAttribute('isShared', $isShared);

            $Location = $Project->getProjectLocation();
            $Include->setAttribute('location', trim($Location->getPostcode() . ' ' . $Location->getCity()));
            $Include->setAttribute('constrMeasure', t(Elca::$constrMeasures[$Project->getConstrMeasure()]));
        }
    }
}
// End ElcaProjectsView
