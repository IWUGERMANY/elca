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

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Elca\Controller\ProjectData\LifeCycleUsageCtrl;
use Elca\Controller\ProjectData\ProjectAccessCtrl;
use Elca\Controller\ProjectDataCtrl;
use Elca\Elca;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Security\ElcaAccess;
use Elca\Service\Admin\BenchmarkSystemsService;

/**
 * Builds the project content head with title, phases and variants
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaProjectNavigationLeftView extends HtmlView
{
    /**
     * Additional navigations
     */
    private $additionalNavigations = [];

    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $benchmarkSystemsService = Environment::getInstance()->getContainer()->get(BenchmarkSystemsService::class);

        $projectVariant = Elca::getInstance()->getProjectVariant();
        $project = $projectVariant->getProject();
        $phaseStep = $projectVariant->getPhase()->getStep();

        $navigation = ElcaNavigation::getInstance('projectData');
        $data = $navigation->add(t('Stammdaten'));
        $data->add(t('Allgemein'), 'elca', ProjectDataCtrl::class, 'general');
        $data->add(t('Berechnungsgrundlage'), 'elca', LifeCycleUsageCtrl::class, 'general');

        $data->add(t($phaseStep > 0? 'Endenergiebilanz' : 'Prognose'), 'elca', ProjectDataCtrl::class, 'enEv');

        $item = $data->add(t('Varianten'), 'elca', ProjectDataCtrl::class, 'variants');
        $activeAction = FrontController::getInstance()->getAction();
        if ($activeAction === 'replaceProcesses' || $activeAction === 'replaceComponents' || $activeAction === 'replaceElements') {
            $item->setActive();
        }

        $data->add(t('Zielwerte'), 'elca', ProjectDataCtrl::class, 'benchmarks');

        if ($project->getOwnerId() === ElcaAccess::getInstance()->getUserId()) {
            $data->add(t('Freigaben'), 'elca', ProjectAccessCtrl::class, 'tokens');
        }

        if ($project->getBenchmarkVersionId()) {
            $benchmarkSystemModel = $benchmarkSystemsService->benchmarkSystemModelByVersionId($project->getBenchmarkVersionId());

            if (null !== $benchmarkSystemModel && !empty($benchmarkSystemModel->waterCalculator())) {
                $waterConfig = $benchmarkSystemModel->waterCalculator();
                $calculators = $navigation->add(t('Rechenhilfen'));
                $calculators->add(
                    $waterConfig['caption'],
                    $waterConfig['module'],
                    $waterConfig['controller'],
                    $waterConfig['action'] ?? null,
                    $waterConfig['args'] ?? [],
                    $waterConfig['data'] ?? []
                );
            }
        }

        /**
         * Add module navigation
         */
        $Elca = Elca::getInstance();
        foreach($Elca->getAdditionalNavigations() as $ModuleNavigation)
        {
            if(!$ProjectNavigation = $ModuleNavigation->getProjectDataNavigation())
                continue;

            if(!$ProjectNavigation->hasChildren())
                continue;

            foreach ($ProjectNavigation->getChildren() as $ParentChildItem)
            {
                $ModuleItem = $navigation->add($ParentChildItem->getCaption());

                foreach($ParentChildItem->getChildren() as $ChildItem)
                    $ModuleItem->add($ChildItem->getCaption(), $ChildItem->getModule(), $ChildItem->getCtrlName(), $ChildItem->getAction(), $ChildItem->getArgs(), $ChildItem->getData());
            }
        }

        $data = $navigation->add(t('Transporte'));
        $data->add(t('Transportrechner'), 'elca', ProjectDataCtrl::class, 'transports');

        $this->assign('mainNav', $navigation);
    }
    // End init


    /**
     * Called before render
     *
     * @return void -
     */
    protected function beforeRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'navLeft']));

        $Include = $Container->appendChild($this->createElement('include'));
        $Include->setAttribute('name', ElcaNavigationLeftView::class);
        $Include->setAttribute('navigation', '$$mainNav$$');
    }
    // End beforeRender
}
// End ElcaProjectNavigationLeftView
