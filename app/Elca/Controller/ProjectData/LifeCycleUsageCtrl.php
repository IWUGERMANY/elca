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

namespace Elca\Controller\ProjectData;

use Elca\Controller\AppCtrl;
use Elca\Controller\ProjectDataCtrl;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Db\ElcaProjectLifeCycleUsageSet;
use Elca\Elca;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaProjectNavigationLeftView;
use Elca\View\ElcaProjectNavigationView;
use Elca\View\ProjectData\ProjectLifeCycleUsageView;

class LifeCycleUsageCtrl extends AppCtrl
{
    /**
     * @param bool          $addNavigationViews
     * @param ElcaValidator $validator
     */
    public function generalAction($addNavigationViews = true, ElcaValidator $validator = null)
    {
        if (!$this->checkProjectAccess()) {
            return;
        }

        $projectId = Elca::getInstance()->getProjectId();
        $data = new \stdClass();

        $lcUsages = $this->container
            ->get(LifeCycleUsageService::class)
            ->findLifeCycleUsagesForProject(new ProjectId($projectId));

        foreach ($lcUsages as $module => $usage) {
            $data->construction[$module] = $usage->applyInConstruction() || $usage->applyInEnergyDemand();
            $data->maintenance[$module] = $usage->applyInMaintenance();
        }

        $view = $this->setView(new ProjectLifeCycleUsageView());
        $view->assign('projectId', $projectId);
        $view->assign('data', $data);
        $view->assign('Validator', $validator);
        $view->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));
        $view->assign('hasRecPot', $lcUsages->hasStageRec());


        $this->Osit->add(new ElcaOsitItem(t('Berechnungsgrundlage'), null, t('Stammdaten')));

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $view = $this->addView(new ElcaProjectNavigationView());
            $view->assign('activeCtrlName', get_class());

            $this->addView(new ElcaProjectNavigationLeftView());
        }
    }

    /**
     *
     */
    public function saveAction()
    {
        if (!$this->Request->id || !$this->isAjax()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        if ($this->Request->has('save')) {
            $projectId = $this->Request->id;
            $project = ElcaProject::findById($projectId);

            /**
             * Check permissions
             */
            if (!$this->checkProjectAccess($project)) {
                return;
            }

            if (null !== $project->getBenchmarkVersionId()) {
                return;
            }

            $constrSettings = $this->Request->getArray('construction');
            $constrSettings = $this->validateSettings($constrSettings);

            $maintSettings = $this->Request->getArray('maintenance');
            $maintSettings = $this->validateSettings($maintSettings);

            $validator = new ElcaValidator($this->Request);

            $lifeCycles = ElcaLifeCycleSet::findByProcessDbId($project->getProcessDbId())
                                          ->getArrayCopy('ident');

            $allLcIdents = array_merge(
                ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
                ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults,
                ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults
            );


            if ($validator->isValid()) {
                foreach ($allLcIdents as $lcIdent => $foo) {
                    if (!isset($lifeCycles[$lcIdent])) {
                        continue;
                    }

                    $lifeCycleUsage = ElcaProjectLifeCycleUsage::findByProjectIdAndLifeCycleIdent(
                        $projectId,
                        $lcIdent
                    );

                    $useInConstr = isset(ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent]) &&
                                   isset($constrSettings[$lcIdent]) && $constrSettings[$lcIdent];
                    $useInMaint  = isset(ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent])  &&
                                   isset($maintSettings[$lcIdent]) && $maintSettings[$lcIdent];
                    $useInEnergy = isset(ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent]) &&
                                   isset($constrSettings[$lcIdent]) && $constrSettings[$lcIdent];

                    if ($lifeCycleUsage->isInitialized()) {
                        $lifeCycleUsage->setUseInConstruction($useInConstr);
                        $lifeCycleUsage->setUseInMaintenance($useInMaint);
                        $lifeCycleUsage->setUseInEnergyDemand($useInEnergy);
                        $lifeCycleUsage->update();
                    }
                    else {
                        ElcaProjectLifeCycleUsage::create(
                            $projectId,
                            $lcIdent,
                            $useInConstr,
                            $useInMaint,
                            $useInEnergy
                        );
                    }
                }

                $this->messages->add(t('Die Daten wurden gespeichert'));

                $view = $this->addView(new ElcaModalProcessingView());
                $view->assign(
                    'action',
                    $this->FrontController->getUrlTo(
                        ProjectDataCtrl::class,
                        'lcaProcessing',
                        [
                            'id' => $this->Request->id,
                            'stay' => true,
                        ]
                    )
                );
                $view->assign('headline', t('Neuberechnung'));
                $view->assign('description', t('Das Projekt "%project%" wird neu berechnet.', null, ['%project%' => $project->getName()]));

            } else {
                foreach ($validator->getErrors() as $error)
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);

                $this->generalAction(false, $validator);
                return;
            }
        }

        $this->generalAction(false);
    }

    /**
     * @param $settings
     * @return array
     */
    protected function validateSettings($settings)
    {
        $a1Isset  = isset($settings[ElcaLifeCycle::IDENT_A1]);
        $a2Isset  = isset($settings[ElcaLifeCycle::IDENT_A2]);
        $a3Isset  = isset($settings[ElcaLifeCycle::IDENT_A3]);
        $a13Isset = isset($settings[ElcaLifeCycle::IDENT_A13]);

        if ($a13Isset) {
            $settings[ElcaLifeCycle::IDENT_A1] = true;
            $settings[ElcaLifeCycle::IDENT_A2] = true;
            $settings[ElcaLifeCycle::IDENT_A3] = true;

            return $settings;
        }

        if ($a1Isset && $a2Isset && $a3Isset) {
            $settings[ElcaLifeCycle::IDENT_A13] = true;

            return $settings;
        }

        return $settings;
    }
}
