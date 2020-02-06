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

namespace Elca\Controller;

use Beibob\Blibs\Session;
use Beibob\Blibs\SessionNamespace;
use Elca\Db\ElcaBenchmarkRefConstructionValueSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantAttribute;
use Elca\Db\ElcaReportSet;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Model\Process\Module;
use Elca\Model\Project\ProjectId;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\Service\Project\Processing\BenchmarkService;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaProjectNavigationView;
use Elca\View\ElcaProjectReportsNavigationLeftView;
use Elca\View\Report\ElcaReportEffectsView;
use Elca\View\Report\ElcaReportElementTypeEffectsView;
use Elca\View\Report\ElcaReportSummaryComparisonView;
use Elca\View\Report\ElcaReportSummaryPerResidentView;
use Elca\View\Report\ElcaReportSummaryView;
use Elca\View\Report\ElcaReportSummaryWasteCodeView;

/**
 * Reports controller
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ProjectReportsCtrl extends BaseReportsCtrl
{
    /**
     * lc phase Colors
     */
    public static $lcPhaseColors = [
        ElcaLifeCycle::PHASE_PROD  => '#3b486b',
        ElcaLifeCycle::PHASE_MAINT => '#ff8c00',
        ElcaLifeCycle::PHASE_EOL   => '#93cd00',
        ElcaLifeCycle::PHASE_REC   => '#94258C',
    ];

    public static $lcIdentColors = [
        ElcaLifeCycle::PHASE_PROD  => '#3b486b',
        ElcaLifeCycle::IDENT_A13   => '#3b486b',
        ElcaLifeCycle::IDENT_A1    => '#5C698E',
        ElcaLifeCycle::IDENT_A2    => '#506190',
        ElcaLifeCycle::IDENT_A3    => '#172857',
        ElcaLifeCycle::IDENT_A4    => '#07153A',
        ElcaLifeCycle::PHASE_MAINT => '#ff8c00',
        ElcaLifeCycle::PHASE_EOL   => '#93cd00',
        ElcaLifeCycle::IDENT_C3    => '#77A700',
        ElcaLifeCycle::IDENT_C4    => '#587B00',
        ElcaLifeCycle::PHASE_REC   => '#94258C',
        ElcaLifeCycle::IDENT_D     => '#94258C',
    ];

    private static $peColors = [
        ElcaIndicator::IDENT_PE_EM   => '#7A98C4',
        ElcaIndicator::IDENT_PERT    => '#7A98C4',
        ElcaIndicator::IDENT_PE_N_EM => '#627699',
        ElcaIndicator::IDENT_PENRT   => '#627699',
        ElcaIndicator::IDENT_PET     => '#88A9E0',
    ];


    /**
     * SessionNamespace
     */
    private $Namespace;

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

        $projectId       = $this->Elca->getProjectId();
        $this->Namespace = $this->Session->getNamespace('elca.reports.'.$projectId, Session::SCOPE_PERSISTENT);
    }
    // End init


    /**
     * Default action
     */
    protected function defaultAction()
    {
        $this->summaryAction();
    }
    // End defaultAction


    /**
     * systems action
     */
    protected function summaryAction()
    {
        $View = $this->setView(new ElcaReportSummaryView());
        $View->assign('buildMode', ElcaReportSummaryView::BUILDMODE_TOTAL);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        $this->Osit->add(new ElcaOsitItem(t('Gesamtbilanz'), null, t('Auswertung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());

        /**
         * Summary is the default action, highlight current nav item in project navigation view
         */
        $View = $this->addView(new ElcaProjectNavigationView());
        $View->assign('activeCtrlName', get_class());
    }
    // End systemsAction

    /**
     * systems action
     */
    protected function summaryPerResidentAction()
    {
        $filterDO = new \stdClass();
        $filterDO->residents = $this->Request->has('residents') ? $this->Request->get('residents') : 1;


        $view = $this->setView(new ElcaReportSummaryPerResidentView());
        $view->assign('buildMode', ElcaReportSummaryView::BUILDMODE_TOTAL);
        $view->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $view->assign('filterDO', $filterDO);

        $this->Osit->add(new ElcaOsitItem(t('Gesamtbilanz pro Person und Jahr'), null, t('Auswertung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());

        /**
         * Summary is the default action, highlight current nav item in project navigation view
         */
        $view = $this->addView(new ElcaProjectNavigationView());
        $view->assign('activeCtrlName', get_class());
    }

/**
     * waste code action
     */
    protected function summaryWasteCodeAction()
    {
        $view = $this->setView(new ElcaReportSummaryWasteCodeView());
        $view->assign('buildMode', ElcaReportSummaryView::BUILDMODE_TOTAL);
        $view->assign('projectVariantId', $this->Elca->getProjectVariantId());

        $this->Osit->add(new ElcaOsitItem(t('Abfallschlüssel'), null, t('Auswertung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());

        /**
         * Summary is the default action, highlight current nav item in project navigation view
         */
        $view = $this->addView(new ElcaProjectNavigationView());
        $view->assign('activeCtrlName', get_class());
    }

    /**
     *
     */
    protected function summaryAdditionalIndicatorsAction()
    {
        $View = $this->setView(new ElcaReportSummaryView());
        $View->assign('buildMode', ElcaReportSummaryView::BUILDMODE_TOTAL_ADDITIONAL_INDICATORS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        $this->Osit->add(new ElcaOsitItem(t('Gesamtbilanz für zusätzliche Indikatoren'), null, t('Auswertung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());

        /**
         * Summary is the default action, highlight current nav item in project navigation view
         */
        $View = $this->addView(new ElcaProjectNavigationView());
        $View->assign('activeCtrlName', get_class());
    }

    /**
     *
     */
    protected function lcaProcessingAction()
    {
        $project = $this->Elca->getProject();

        $view = $this->addView(new ElcaModalProcessingView());
        $view->assign(
            'action',
            $this->FrontController->getUrlTo(
                ProjectDataCtrl::class,
                'lcaProcessing',
                [
                    'id'     => $project->getId(),
                    'reload' => true,
                ]
            )
        );
        $view->assign('headline', t('Neuberechnung'));
        $view->assign(
            'description',
            t('Das Projekt "%project%" wird neu berechnet.', null, ['%project%' => $project->getName()])
        );
    }


    /**
     * systems action
     */
    protected function benchmarksAction()
    {
        $view = $this->setView(new ElcaReportSummaryView());
        $view->assign('buildMode', ElcaReportSummaryView::BUILDMODE_BENCHMARKS);
        $view->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $view->assign('readOnly', !$this->Access->canEditProject($this->Elca->getProject()));

        if (is_numeric($this->Request->benchmarkVersionId)) {
            $this->Namespace->benchmarkVersionId = $this->Request->benchmarkVersionId;
        }

        if ($benchmarkVersionId = $this->Elca->getProject()->getBenchmarkVersionId()) {
            $this->Namespace->benchmarkVersionId = $benchmarkVersionId;
        }

        $view->assign('benchmarkVersionId', $this->Namespace->benchmarkVersionId);

        if ($this->Request->isPost() && $this->Request->has('save')) {
            $comment = \trim($this->Request->comment);

            if (!empty($comment)) {
                ElcaProjectVariantAttribute::updateValue(
                    $this->Elca->getProjectVariantId(),
                    ElcaProjectVariantAttribute::IDENT_LCA_BENCHMARK_COMMENT,
                    $comment,
                    true
                );
            }
        }

        $this->Osit->add(new ElcaOsitItem(t('Benchmarks'), null, t('Auswertung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());
    }
    // End systemsAction


    /**
     * compositeElements action
     */
    protected function elementsAction()
    {
        $View = $this->setView(new ElcaReportEffectsView());
        $View->assign('buildMode', ElcaReportEffectsView::BUILDMODE_ELEMENTS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        $this->Osit->add(new ElcaOsitItem(t('Bauteilkatalog'), null, t('Auswertung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());
    }
    // End compositeElementAction


    /**
     * systems action
     */
    protected function summaryElementTypesAction()
    {
        $View = $this->setView(new ElcaReportElementTypeEffectsView());
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        if (is_numeric($this->Request->i)) {
            $this->Namespace->indicatorId = $this->Request->i;
        }

        if (!$this->Namespace->indicatorId) {
            $Indicators                   = ElcaIndicatorSet::findWithPetByProcessDbId(
                $this->Elca->getProject()->getProcessDbId(),
                false,
                false,
                ['p_order' => 'ASC'],
                1
            );
            $this->Namespace->indicatorId = $Indicators[0]->getId();
        }
        $View->assign('indicatorId', $this->Namespace->indicatorId);

        if (!$this->Request->isPost()) {
            $this->Osit->add(new ElcaOsitItem(t('Bilanz nach Bauteilgruppen'), null, t('Auswertung')));
            $this->addView(new ElcaProjectReportsNavigationLeftView());
        }
    }
    // End summaryElementTypesAction


    /**
     * compare summary
     */
    protected function compareSummaryAction()
    {
        $currentVariantId     = $this->Elca->getProjectVariantId();
        $compareWithVariantId = ($this->Request->projectVariantId ? $this->Request->projectVariantId
            : $this->Namespace->compareWithVariantId);

        if ($compareWithVariantId == $currentVariantId) {
            $this->Namespace->compareWithVariantId = null;
            $compareWithVariantId                  = null;
        } else {
            $this->Namespace->compareWithVariantId = $compareWithVariantId;
        }

        $View = $this->setView(new ElcaReportSummaryComparisonView());
        $View->assign('buildMode', ElcaReportSummaryComparisonView::BUILDMODE_TOTAL);
        $View->assign('projectVariantId', $currentVariantId);
        $View->assign('compareWithProjectVariantId', $compareWithVariantId);

        $this->Osit->add(new ElcaOsitItem(t('Gesamtbilanz'), null, t('Variantenvergleich')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());

        /**
         * Summary is the default action, highlight current nav item in project navigation view
         */
        $View = $this->addView(new ElcaProjectNavigationView());
        $View->assign('activeCtrlName', get_class());
    }
    // End compareSummaryAction


    /**
     * systems action
     */
    protected function compareElementTypesAction()
    {
        $currentVariantId     = $this->Elca->getProjectVariantId();
        $compareWithVariantId = ($this->Request->projectVariantId ? $this->Request->projectVariantId
            : $this->Namespace->compareWithVariantId);

        if ($compareWithVariantId == $currentVariantId) {
            $this->Namespace->compareWithVariantId = null;
            $compareWithVariantId                  = null;
        } else {
            $this->Namespace->compareWithVariantId = $compareWithVariantId;
        }

        $View = $this->setView(new ElcaReportSummaryComparisonView());
        $View->assign('buildMode', ElcaReportSummaryComparisonView::BUILDMODE_ELEMENT_TYPES);
        $View->assign('projectVariantId', $currentVariantId);
        $View->assign('compareWithProjectVariantId', $compareWithVariantId);

        if (is_numeric($this->Request->i)) {
            $View->assign('indicatorId', $this->Request->i);
        } else {
            $Indicators = ElcaIndicatorSet::findWithPetByProcessDbId(
                $this->Elca->getProject()->getProcessDbId(),
                false,
                false,
                ['p_order' => 'ASC'],
                1
            );
            $View->assign('indicatorId', $Indicators[0]->getId());
        }

        if (!$this->Request->isPost()) {
            $this->Osit->add(new ElcaOsitItem(t('Bilanz nach Bauteilgruppen'), null, t('Variantenvergleich')));
            $this->addView(new ElcaProjectReportsNavigationLeftView());
        }
    }
    // End compareSummaryElementTypesAction

     public function testpdfvarAction()
	 {
		$pdfCreated = false;
		
		if (!$this->isAjax() && !$this->Request) {
            return;
        }
		$data = $this->Request;
		if(isset($data->id))
		{
			$reportPDF = ElcaReportSet::findPdfInQueue(
			$data->id, 
			$data->pvid,
			$data->uid, 
			$data->action
			);
			
			//var_dump($reportPDF);
			if($reportPDF->ready)
			{
				$pdfCreated = true;
			}
			else
			{
				var_dump( $reportPDF->ready );
			}		
		} 
			
		$this->getView()->assign('created', $pdfCreated);
	 }

    /**
     * Benchmark chart data
     */
    protected function xBenchmarkChartAction()
    {
        if (!$this->isAjax() || !$this->Request->v) {
            return;
        }

        $projectVariant = ElcaProjectVariant::findById($this->Request->v);
        $Project        = $projectVariant->getProject();

        /**
         * Without specification of benchmark version no benchmark chart
         */
        if (!isset($this->Request->bv) && !$Project->getBenchmarkVersionId()) {
            return;
        }

        $benchmarkVersion = ElcaBenchmarkVersion::findById(isset($this->Request->bv) ? $this->Request->bv : $Project->getBenchmarkVersionId());

        /**
         * Bar config
         */
        $Conf         = new \stdClass();
        $Conf->yAxis  = (object)['caption' => t('BNB Benchmark'), 'refUnit' => '%', 'wordingIn' => t('in')];
        $Conf->height = 230;
        $Conf->margin = (object)['top' => 5, 'bottom' => 50];

        /**
         * Compute benchmarks
         */
        $benchmarks = $this->container->get(BenchmarkService::class)->compute($benchmarkVersion, $projectVariant);

        $indicators = ElcaIndicatorSet::findWithPetByProcessDbId($Project->getProcessDbId())->getArrayBy('name', 'ident');
        $indicators['pe'] = t('PE');
        $data = [];
        foreach ($benchmarks as $ident => $value) {
            if (!$value)
                continue;
            $DO = $data[] = new \stdClass();
            $DO->name = t($indicators[$ident]);
            $DO->value = $value;
        }

        $this->getView()->assign('data', $data);
        $this->getView()->assign('config', $Conf);
    }
    // End benchmarkChartAction

    /**
     * Benchmark chart data
     */
    protected function benchmarkChartAction()
    {
        if (!$this->isAjax() || !$this->Request->v) {
            return;
        }

        $projectVariant = ElcaProjectVariant::findById($this->Request->v);
        $project        = $projectVariant->getProject();
        $dbIsEn15804Compliant = $project->getProcessDb()->isEn15804Compliant();

        /**
         * Without specification of benchmark version no benchmark chart
         */
        if (!isset($this->Request->bv) && !$project->getBenchmarkVersionId()) {
            return;
        }

        $benchmarkVersion = ElcaBenchmarkVersion::findById(
            $this->Request->bv ?? $project->getBenchmarkVersionId()
        );

        /**
         * Bar config
         */
        $Conf         = new \stdClass();
        $Conf->yAxis  = (object)['caption' => t('Bewertungspunkte'), 'refUnit' => '', 'wordingIn' => '', 'atLeastMaxValue' => 100];
        $Conf->height = 230;
        $Conf->margin = (object)['top' => 5, 'bottom' => 50];

        /**
         * Compute benchmarks
         */
        $benchmarkResults = $this->container->get(BenchmarkService::class)->compute($benchmarkVersion, $projectVariant);

        $benchmarks = $peStack = [];

        foreach ($benchmarkResults as $ident => $result) {
            if (IndicatorIdent::PE === $ident || null === $result) {
                continue;
            }
            $indicatorIdent = new IndicatorIdent($ident);

            if ($dbIsEn15804Compliant) {
                $isStackedPrimaryEnergy = $indicatorIdent->isPrimaryEnergyIndicator();
            } else {
                $isStackedPrimaryEnergy = $indicatorIdent->isPrimaryEnergyIndicator() &&
                                          false === $indicatorIdent->isNotRenewablePrimaryEnergy();
            }

            if ($isStackedPrimaryEnergy) {
                $peStack[$ident] = $result;
            }

            $benchmarks[$ident] = $result;
        }

        $benchmarks[IndicatorIdent::PE] = $peStack;


        $indicators       = ElcaIndicatorSet::findWithPetByProcessDbId($project->getProcessDbId())->getArrayBy(
            'name',
            'ident'
        );
        $indicators[IndicatorIdent::PE] = $dbIsEn15804Compliant ? t('KSB 1.2.1') : t('KSB 1.2.2');
        $data             = [];

        foreach ($benchmarks as $ident => $value) {
            if (!$value) {
                continue;
            }

            if (is_array($value)) {
                $DO         = $data[] = new \stdClass();
                $DO->name   = $indicators[IndicatorIdent::PE];
                $DO->values = [];

                foreach ($value as $indicatorIdent => $val) {
                    $DO->values[] = (object)[
                        'name'  => t($indicators[$indicatorIdent]),
                        'value' => $val,
                        'fill'  => self::$peColors[$indicatorIdent],
                    ];
                }
            } else {
                $DO         = $data[] = new \stdClass();
                $DO->name   = t($indicators[$ident]);
                $DO->values = [
                    (object)[
                        'name'  => t($indicators[$ident]),
                        'value' => $value,
                        'fill'  => self::$peColors[$ident] ?? self::$lcIdentColors[ElcaLifeCycle::PHASE_PROD],
                    ],
                ];
            }
        }

        $this->getView()->assign('data', $data);
        $this->getView()->assign('config', $Conf);
    }
    // End benchmarkChartAction

    /**
     * Element type chart data
     */
    protected function elementTypeChartAction()
    {
        if (!$this->isAjax() || !$this->Request->t || !$this->Request->v) {
            return;
        }

        $elementTypeId       = $this->Request->t;
        $ProjectVariant      = ElcaProjectVariant::findById($this->Request->v);
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($ProjectVariant->getId());

        $lifeCycleUsages = $this->container
            ->get(LifeCycleUsageService::class)
            ->findLifeCycleUsagesForProject(new ProjectId($ProjectVariant->getProjectId()));

        if (!$indicatorId = $this->Request->i) {
            $Indicators = ElcaIndicatorSet::findByProcessDbId(
                $ProjectVariant->getProject()->getProcessDbId(),
                false,
                false,
                ['p_order' => 'ASC'],
                1
            );
            $Indicator  = $Indicators[0];
        } else {
            $Indicator = ElcaIndicator::findById($indicatorId);
        }

        /**
         * Bar config
         */
        $Conf        = new \stdClass();
        $Conf->yAxis = (object)[
            'caption'   => t($Indicator->getName()),
            'refUnit'   => t($Indicator->getUnit()),
            'wordingIn' => t('in'),
        ];


        /**
         * All values per m2 and year
         */
        $m2a = max(1, $ProjectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());

        /**
         * Calculate benchmark
         */
        $reports      = [];
        $ElementTypes = ElcaElementTypeSet::findByParentType(ElcaElementType::findByNodeId($elementTypeId));
        $ReportSet    = ElcaReportSet::findEffectsPerElementType(
            $ProjectVariant->getId(),
            [ElcaLifeCycle::PHASE_PROD, ElcaLifeCycle::PHASE_MAINT, ElcaLifeCycle::PHASE_EOL, ElcaLifeCycle::PHASE_REC],
            $Indicator->getId(),
            $elementTypeId
        );

        foreach ($ElementTypes as $ElementType) {
            $category           = $ElementType->getDinCode().' '.t($ElementType->getName());
            $reports[$category] = (object)[
                'name'   => $ElementType->getDinCode(),
                'values' => [],
            ];
        }

        foreach ($ReportSet as $Report) {
            if (Module::fromValue($Report->life_cycle_ident)->isA1A2OrA3()) {
                continue;
            }

            if ($Report->life_cycle_phase === ElcaLifeCycle::PHASE_REC ||
                $lifeCycleUsages->moduleIsAppliedInConstruction(new Module($Report->life_cycle_ident))) {
                $DO = $reports[$Report->din_code.' '.t($Report->category)];

                $DO->values[] = (object)[
                    'name'  => $Report->life_cycle_name,
                    'value' => $Report->value / max(1, $m2a),
                    'fill'  => self::$lcIdentColors[$Report->life_cycle_ident],
                ];
            }
        }

        $this->getView()->assign('data', array_values($reports));
        $this->getView()->assign('config', $Conf);
    }
    // End elementTypeChartAction


    /**
     * Element type chart data
     */
    protected function compareElementTypesChartAction()
    {
        if (!$this->isAjax() || !$this->Request->k || !$this->Request->v1 || !$this->Request->v2) {
            return;
        }

        $parentElementTypeNodeId = $this->Request->k;
        $ProjectVariant          = ElcaProjectVariant::findById($this->Request->v1);
        $CompareVariant          = ElcaProjectVariant::findById($this->Request->v2);

        $lifeCycleUsages = $this->container
            ->get(LifeCycleUsageService::class)
            ->findLifeCycleUsagesForProject(new ProjectId($ProjectVariant->getProjectId()));

        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->Request->v1);
        $CompareConstruction = ElcaProjectConstruction::findByProjectVariantId($this->Request->v2);

        if (!$indicatorId = $this->Request->i) {
            $Indicators = ElcaIndicatorSet::findByProcessDbId(
                $ProjectVariant->getProject()->getProcessDbId(),
                false,
                false,
                ['p_order' => 'ASC'],
                1
            );
            $Indicator  = $Indicators[0];
        } else {
            $Indicator = ElcaIndicator::findById($indicatorId);
        }

        /**
         * Bar config
         */
        $Conf        = new \stdClass();
        $Conf->yAxis = (object)[
            'caption'   => t($Indicator->getName()),
            'wordingIn' => t('in'),
            'refUnit'   => t($Indicator->getUnit()),
        ];


        /**
         * All values per m2 and year
         */
        $m2aA = max(1, $ProjectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());
        $m2aB = max(1, $CompareVariant->getProject()->getLifeTime() * $CompareConstruction->getNetFloorSpace());

        /**
         * Calculate benchmark
         */
        $reports   = [];
        $ReportSet = ElcaReportSet::findComparisonEffectsPerElementTypes(
            $ProjectVariant->getId(),
            $CompareVariant->getId(),
            [ElcaLifeCycle::PHASE_PROD, ElcaLifeCycle::PHASE_MAINT, ElcaLifeCycle::PHASE_EOL, ElcaLifeCycle::PHASE_REC],
            $Indicator->getId(),
            $parentElementTypeNodeId
        );
        foreach ($ReportSet as $Report) {
            if (Module::fromValue($Report->life_cycle_ident)->isA1A2OrA3()) {
                continue;
            }

            $category = $Report->category;
            if (!isset($reports[$category])) {
                $reports[$category] = (object)[
                    'name'   => $Report->din_code,
                    'groups' => [
                        (object)['name' => 'A', 'stacks' => []],
                        (object)['name' => 'B', 'stacks' => []],
                    ],
                ];
            }

            if ($Report->life_cycle_phase === ElcaLifeCycle::PHASE_REC ||
                $lifeCycleUsages->moduleIsAppliedInConstruction(new Module($Report->life_cycle_ident))) {

                $DO                      = $reports[$Report->category];
                $DO->groups[0]->stacks[] = (object)[
                    'name'  => t($Report->life_cycle_name),
                    'value' => $Report->value_a / $m2aA,
                    'fill'  => self::$lcIdentColors[$Report->life_cycle_ident],
                ];
                $DO->groups[1]->stacks[] = (object)[
                    'name'  => t($Report->life_cycle_name),
                    'value' => $Report->value_b / $m2aB,
                    'fill'  => self::$lcIdentColors[$Report->life_cycle_ident],
                ];
            }
        }

        $this->getView()->assign('data', array_values($reports));
        $this->getView()->assign('config', $Conf);
    }
    // End elementTypeChartAction


    /**
     * Reference model chart data
     */
    protected function refModelChartAction()
    {
        if (!$this->isAjax() || !$this->Request->v || !$this->Request->i) {
            return;
        }

        $ProjectVariant      = ElcaProjectVariant::findById($this->Request->v);
        $ProjectConstruction = $ProjectVariant->getProjectConstruction();
        $BenchmarkVersion    = $ProjectVariant->getProject()->getBenchmarkVersion();

        if (!$BenchmarkVersion->getUseReferenceModel()) {
            return;
        }

        $Indicator   = ElcaIndicator::findById($this->Request->i);
        $indicatorId = $Indicator->getId();

        /**
         * All values per m2 and year
         */
        $m2a = max(1, $ProjectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());

        /**
         * Bar config
         */
        $Conf         = new \stdClass();
        $Conf->width  = 250;
        $Conf->height = 220;
        $Conf->margin = (object)['bottom' => 50];
        $Conf->yAxis  = (object)['caption' => $Indicator->getName(), 'refUnit' => $Indicator->getUnit()];

        /**
         * Calculate benchmark
         */
        $constrValues = ElcaReportSet::findConstructionTotalEffects($ProjectVariant->getId(), $indicatorId)->getArrayBy(
            'value',
            'indicator_id'
        );
        $opValues     = ElcaReportSet::findTotalEffectsPerLifeCycle(
            $ProjectVariant->getId(),
            ['life_cycle_phase' => ElcaLifeCycle::PHASE_OP, 'indicator_id' => $indicatorId]
        )->getArrayBy('value', 'indicator_id');

        $refConstrValues = ElcaBenchmarkRefConstructionValueSet::findByBenchmarkVersionId(
            $BenchmarkVersion->getId()
        )->getArrayBy('value', 'indicatorId');
        $refOpValues     = ElcaReportSet::findFinalEnergyRefModelEffects(
            $ProjectVariant->getId(),
            $indicatorId
        )->getArrayBy('value', 'indicator_id');

        if (!count($constrValues) ||
            !count($opValues) ||
            !count($refConstrValues) ||
            !count($refOpValues)
        ) {
            return;
        }

        $constrVal = $constrValues[$indicatorId] / $m2a;
        $opVal     = $opValues[$indicatorId] / $m2a;

        $refConstrVal = (float)$refConstrValues[$indicatorId];
        $refOpVal     = $refOpValues[$indicatorId] / $m2a;

        $reports = [];
        $DO      = $reports[] = (object)[
            'name'   => $Indicator->getName(),
            'groups' => [
                (object)['name' => 'IST', 'stacks' => []],
                (object)['name' => 'REF', 'stacks' => []],
            ],
        ];

        $DO->groups[0]->stacks[] = (object)[
            'name'  => 'Konstruktionsanteil IST',
            'value' => $constrVal,
            'fill'  => self::$lcIdentColors[ElcaLifeCycle::IDENT_A13],
        ];
        $DO->groups[0]->stacks[] = (object)[
            'name'  => 'Nutzungsanteil IST',
            'value' => $opVal,
            'fill'  => self::$lcIdentColors[ElcaLifeCycle::PHASE_MAINT],
        ];

        $DO->groups[1]->stacks[] = (object)[
            'name'  => 'Konstruktionsanteil REF',
            'value' => $refConstrVal,
            'fill'  => self::$lcIdentColors[ElcaLifeCycle::IDENT_A13],
        ];
        $DO->groups[1]->stacks[] = (object)[
            'name'  => 'Nutzungsanteil REF',
            'value' => $refOpVal,
            'fill'  => self::$lcIdentColors[ElcaLifeCycle::PHASE_MAINT],
        ];

        $this->getView()->assign('data', array_values($reports));
        $this->getView()->assign('config', $Conf);
    }
    // End elementTypeChartAction


    /**
     * @return SessionNamespace
     */
    protected function getSessionNamespace()
    {
        return $this->Namespace;
    }
}
// End ElcaReportsCtrl
