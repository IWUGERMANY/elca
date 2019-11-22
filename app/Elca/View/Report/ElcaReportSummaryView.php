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

namespace Elca\View\Report;

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\HtmlTextArea;
use DOMElement;
use Elca\Controller\ProjectReportsCtrl;
use Elca\Db\ElcaBenchmarkRefConstructionValueSet;
use Elca\Db\ElcaBenchmarkSystemSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaBenchmarkVersionSet;
use Elca\Db\ElcaCacheElementType;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectIndicatorBenchmarkSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantAttribute;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Process\Module;
use Elca\Model\Project\ProjectId;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\Service\Project\Processing\BenchmarkService;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlReportBar;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaTranslatorConverter;

/**
 * Builds the summary report for first level element types, life cycle and in total
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaReportSummaryView extends ElcaReportsView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_TOTAL = 'total';
    const BUILDMODE_ELEMENT_TYPES = 'element-types';
    const BUILDMODE_BENCHMARKS = 'benchmarks';
    const BUILDMODE_TOTAL_ADDITIONAL_INDICATORS = 'additional-indicators';


    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * indicatorId
     */
    private $indicatorId;

    /**
     * benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * Just update the charts
     */
    private $updateCharts = false;

    private $readOnly;

    // protected


    /**
     * Init
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->buildMode          = $this->get('buildMode', self::BUILDMODE_TOTAL);
        $this->indicatorId        = $this->get('indicatorId');
        $this->benchmarkVersionId = $this->get('benchmarkVersionId');
        $this->updateCharts       = $this->get('updateCharts', false);
        $this->readOnly           = $this->get('readOnly', false);
    }
    // End init


    /**
     * Renders the report
     *
     * @param  DOMElement $Container
     */
    protected function renderReports(
        DOMElement $Container,
        DOMElement $infoDl,
        ElcaProjectVariant $projectVariant,
        $lifeTime
    ) {
        $this->addClass($Container, 'report-summary report-summary-'.$this->buildMode);

        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $infoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)').': '));
        $infoDl->appendChild(
            $this->getDd([], ElcaNumberFormat::toString($ProjectConstruction->getNetFloorSpace()).' m²')
        );

        $tdContainer = $this->appendPrintTable($Container);

        switch ($this->buildMode) {
            case self::BUILDMODE_TOTAL_ADDITIONAL_INDICATORS:
                $totalEffectsCount = $this->buildTotalEffects($infoDl, $ProjectConstruction, $tdContainer, true);

                if (0 === $totalEffectsCount) {
                    $pElt = $tdContainer->appendChild(
                        $this->getP(t('Sollten Sie hier keine Ergebnisse sehen, dann').' ')
                    );
                    $pElt->appendChild(
                        $this->getA(
                            [
                                'href' => FrontController::getInstance()
                                                         ->getUrlTo(
                                                             ProjectReportsCtrl::class,
                                                             'lcaProcessing'
                                                         ),
                            ],
                            t('berechnen Sie das Projekt bitte neu')
                        )
                    );
                    $pElt->appendChild($this->getText('.'));
                }
                break;

            case self::BUILDMODE_TOTAL:
                $this->buildTotalEffects($infoDl, $ProjectConstruction, $tdContainer, false);
                break;

            case self::BUILDMODE_BENCHMARKS:
            default:
                $this->appendBenchmark($infoDl, $projectVariant, $tdContainer);
                break;
        }
    }

    /**
     * @param DOMElement              $infoDl
     * @param ElcaProjectConstruction $ProjectConstruction
     * @param DOMElement              $TdContainer
     * @param bool                    $onlyHiddenIndicators
     */
    protected function buildTotalEffects(
        DOMElement $infoDl,
        ElcaProjectConstruction $ProjectConstruction,
        DOMElement $TdContainer,
        $onlyHiddenIndicators = false
    ) {
        $RootElementType = ElcaElementType::findRoot();
        $CElementType    = ElcaCacheElementType::findByProjectVariantIdAndElementTypeNodeId(
            $this->projectVariantId,
            $RootElementType->getNodeId()
        );
        $mass            = $CElementType->getMass();

        $infoDl->appendChild($this->getDt([], t('Masse gesamt').': '));
        $infoDl->appendChild($this->getDd([], ElcaNumberFormat::toString($mass / 1000, 3).' t'));

        $infoDl->appendChild($this->getDt([], t('Masse NGF').': '));
        $Dd = $infoDl->appendChild(
            $this->getDd(
                [],
                ElcaNumberFormat::toString($mass / max(1, $ProjectConstruction->getNetFloorSpace()), 2).' kg/ m²'
            )
        );
        $Dd->appendChild($this->getSub(t('NGF')));

        if ($bgf = $ProjectConstruction->getGrossFloorSpace()) {
            $infoDl->appendChild($this->getDt([], t('Masse BGF').': '));
            $Dd = $infoDl->appendChild($this->getDd([], ElcaNumberFormat::toString($mass / $bgf, 2).' kg/ m²'));
            $Dd->appendChild($this->getSub(t('BGF')));
        }

        $this->appendNonDefaultLifeTimeInfo($infoDl);
        $this->appendEpdTypeStatistic($infoDl);

        $projectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $lcUsages = Environment::getInstance()
                               ->getContainer()
                               ->get(LifeCycleUsageService::class)
                               ->findLifeCycleUsagesForProject(new ProjectId($projectVariant->getProjectId()));

        if ($lcUsages->hasStageRec()) {
            $infoDl->appendChild($this->getDt([], t('Hinweis').': '));
            $infoDl->appendChild(
                $this->getDd(['class' => 'warning'], t('Die Verrechnung von Modul D ist nicht Normkonform!'))
            );
        }

        $TotalEffects = ElcaReportSet::findTotalEffects($this->projectVariantId, $onlyHiddenIndicators);

        $LifeCycleEffects = ElcaReportSet::findTotalEffectsPerLifeCycle(
            $this->projectVariantId,
            ['is_hidden' => $onlyHiddenIndicators]
        );

        foreach (
            ElcaReportSet::findTotalEnergyRecyclingEffects(
                $this->projectVariantId,
                $onlyHiddenIndicators
            ) as $do
        ) {
            $LifeCycleEffects->add($do);
        }
        foreach (
            ElcaReportSet::findTotalConstructionRecyclingEffects(
                $this->projectVariantId,
                $onlyHiddenIndicators
            ) as $do
        ) {
            $LifeCycleEffects->add($do);
        }

		// ermittle nur enthaltene Reports (Anzaige: Gesamt inkl. )
		$realCategories = null;
		$excludeArray = array('Gesamt',t('Instandhaltung'),'D','D energetisch','D stofflich');
		$tempReportsCategories = $this->getTotalLifeCycleIdentsReal($LifeCycleEffects,$excludeArray);
		if($tempReportsCategories!="")  
		{
			$realCategories .= $tempReportsCategories;
		}		

        $this->buildEffects($TdContainer, $TotalEffects,false,false,null,$realCategories);
		
        $this->buildEffects(
            $TdContainer,
            $LifeCycleEffects,
            true,
            false,
            $TotalEffects->getArrayBy('value', 'indicator_id')
        );

        return $TotalEffects->count();
    }
    // End beforeRender

    /**
     * Appends the container for the benchmark diagram
     *
     * @param  DOMElement $Container
     * @param  int        $projectVariantId
     *
     * @return void -
     */
    protected function buildBenchmarkChart(DOMElement $Container, $projectVariantId)
    {
        $attributes = [
            'class'    => 'chart stacked-bar-chart benchmark-chart',
            'data-url' => Url::factory(
                '/elca/project-reports/benchmarkChart/',
                ['v' => $projectVariantId, 'bv' => $this->benchmarkVersionId]
            ),
        ];

        $Container->appendChild($this->getDiv($attributes));
    }
    // End appendEffects

    /**
     * Appends the container for the refModel diagram
     *
     * @param  DOMElement $Container
     * @param  int        $projectVariantId
     *
     * @return void -
     */
    protected function buildRefModelChart(DOMElement $Container, $projectVariantId, array $indicators)
    {
        $Cycler = $Container->appendChild($this->getDiv(['class' => 'cycler']));

        $attributes = ['class' => 'chart grouped-stacked-bar-chart ref-model-chart'];

        foreach ($indicators as $indicatorId) {
            $attributes['data-url']                  = Url::factory(
                '/elca/project-reports/refModelChart/',
                ['v' => $projectVariantId, 'i' => $indicatorId]
            );
            $attributes['data-cycle-pager-template'] = '<a href="#" rel="cycle">'.ElcaIndicator::findById(
                    $indicatorId
                )->getName().'</a>';
            $Cycler->appendChild($this->getDiv($attributes));
        }
    }

    protected function appendBenchmark(DOMElement $infoDl, ElcaProjectVariant $projectVariant, $tdContainer): void
    {
        $project = $projectVariant->getProject();
        if (!$project->getBenchmarkVersionId()) {
            $form = new HtmlForm('reportForm', '/project-reports/benchmarks/');
            $form->setRequest(FrontController::getInstance()->getRequest());
            $select = $form->add(
                new ElcaHtmlFormElementLabel(t('Benchmarksystem'), new HtmlSelectbox('benchmarkVersionId'))
            );
            $select->add(new HtmlSelectOption('-- '.t('Kein Benchmark').' --', ''));

            $isEn15804Compliant = $project->getProcessDb()->isEn15804Compliant();

            $benchmarkSystems = ElcaBenchmarkSystemSet::find(['is_active' => true], ['name' => 'ASC']);
            foreach ($benchmarkSystems as $benchmarkSystem) {
                /** @var ElcaBenchmarkVersion $benchmarkVersion */
                foreach (
                    ElcaBenchmarkVersionSet::find(
                        ['benchmark_system_id' => $benchmarkSystem->getId(), 'is_active' => true],
                        ['name' => 'ASC', 'id' => 'ASC']
                    ) as $benchmarkVersion
                ) {
                    $processDb = $benchmarkVersion->getProcessDb();

                    /** offer only compliant benchmarks */
                    if ($processDb->isEn15804Compliant(
                        ) !== $isEn15804Compliant || $benchmarkVersion->getUseReferenceModel()
                    ) {
                        continue;
                    }

                    $opt = $select->add(
                        new HtmlSelectOption(
                            $benchmarkSystem->getName().' - '.$benchmarkVersion->getName(),
                            $benchmarkVersion->getId()
                        )
                    );

                    if ($this->benchmarkVersionId == $benchmarkVersion->getId()) {
                        $opt->setAttribute('selected', 'selected');
                        $infoDl->appendChild($this->getDt(['class' => 'print'], t('Benchmarksystem').': '));
                        $infoDl->appendChild(
                            $this->getDd(
                                ['class' => 'print'],
                                $benchmarkSystem->getName().' - '.$benchmarkVersion->getName()
                            )
                        );
                    }
                }
            }

            $form->appendTo($tdContainer);
        } else {
            $benchmarkVersion = $projectVariant->getProject()->getBenchmarkVersion();
            $benchmarkSystem  = $benchmarkVersion->getBenchmarkSystem();
            $infoDl->appendChild($this->getDt([], t('Benchmarksystem').': '));
            $infoDl->appendChild(
                $this->getDd(['class' => 'benchmark-version'], $benchmarkSystem->getName().' - '.$benchmarkVersion->getName())
            );
        }

        $this->appendNonDefaultLifeTimeInfo($infoDl);
        $this->buildBenchmark(
            $tdContainer,
            ElcaReportSet::findTotalEffects($this->projectVariantId)
        );

        /**
         * Add ref model report only when this project has a selected benchmark version
         */
        if ($projectVariant->getProject()->getBenchmarkVersion()->getUseReferenceModel()) {
            $this->buildRefModelEffects($tdContainer);
        }

        $include = $tdContainer->appendChild($this->createElement('include'));
        $include->setAttribute('name', 'Elca\View\ElcaProjectProcessConfigSanityView');
        $include->setAttribute('readOnly', $this->readOnly);
    }
    // End appendEffect

    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement     $Container
     * @param  ElcaReportSet $ReportSet
     * @param bool           $isLifeCycle
     * @param bool           $addBenchmarks
     * @param array          $totalEffects
	 * @param string         $realCategories
     *
     * @return void -
     */
    private function buildEffects(
        DOMElement $Container,
        ElcaReportSet $ReportSet,
        $isLifeCycle = false,
        $addBenchmarks = false,
        array $totalEffects = null,
		$realCategories = null
    ) {
        if (!$ReportSet->count()) {
            return;
        }

        $projectVariant      = ElcaProjectVariant::findById($this->projectVariantId);
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);
        $addPhaseRec         = $projectVariant->getProject()->getProcessDb()->isEn15804Compliant();
        $lcUsages = Environment::getInstance()
                                      ->getContainer()
                                      ->get(LifeCycleUsageService::class)
                                      ->findLifeCycleUsagesForProject(new ProjectId($projectVariant->getProjectId()));

        /**
         * Normalize values
         *
         * All values per m2 and year
         */
        $m2a = max(1, $projectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());

        $reports = [];
        foreach ($ReportSet as $reportDO) {
            $reportDO->norm_total_value = $reportDO->value / $m2a;
            $key                        = $reportDO->category;

            if ($isLifeCycle && $totalEffects &&
                $lcUsages->moduleIsAppliedInTotals(new Module($reportDO->life_cycle_ident))
            ) {
                // @todo: if total value eq 0 what percentage should be shown?

                $reportDO->percentage = $totalEffects[$reportDO->indicator_id] == 0 ? null
                    : $reportDO->value / $totalEffects[$reportDO->indicator_id];
                $reportDO->bar        = round($reportDO->percentage * 100);
            }

            /**
             * Restructure
             */
            if (!isset($reports[$key])) {
                $reports[$key] = [];
            }
            $reports[$key][] = $reportDO;
        }

        if ($isLifeCycle && $this->Project->getProcessDb()->isEn15804Compliant()) {
            ksort($reports, SORT_STRING);
        }

        /**
         * Compute Benchmark
         */
        if ($addBenchmarks && $this->benchmarkVersionId) {
            $benchmarkVersion = ElcaBenchmarkVersion::findById($this->benchmarkVersionId);

            /**
             * Get indicator benchmarks
             */
            $indicatorBenchmarks = ElcaProjectIndicatorBenchmarkSet::find(
                ['project_variant_id' => $this->projectVariantId]
            )->getArrayBy('benchmark', 'indicatorId');

            /**
             * Compute benchmarks
             */
            $benchmarks = Environment::getInstance()->getContainer()->get(BenchmarkService::class)->compute(
                $benchmarkVersion,
                $projectVariant
            );
            foreach ($ReportSet as $reportDO) {
                $reportDO->benchmark     = isset($benchmarks[$reportDO->ident]) ? ElcaNumberFormat::toString(
                    $benchmarks[$reportDO->ident],
                    2
                ) : null;
                $reportDO->initBenchmark = $indicatorBenchmarks[$reportDO->indicator_id] ?? null;
            }

            if (isset($benchmarks['pe'])) {
                $reports['Gesamt'][] = (object)[
                    'ident'     => 'pe',
                    'name'      => t('Primärenergie'),
                    'benchmark' => ElcaNumberFormat::toString(
                        $benchmarks['pe'],
                        2
                    ),
                ];
            }
        }

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category']));


		
		foreach ($reports as $category => $dataSet) {
            $TypeLi = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
			
            $H1 = $TypeLi->appendChild($this->getH1(t($category)));
            if ($category === 'Gesamt') {
			   if($realCategories) {
				   $showCategories = $realCategories;
			   }  
			   else
			   {
				    $showCategories = $this->getTotalLifeCycleIdents();
			   }		
			   $H1->appendChild($this->getSpan(t('inkl.').' '.$showCategories));
			   // $H1->appendChild($this->getSpan(t('inkl.').' '.$this->getTotalLifeCycleIdents()));
			   // $H1->appendChild($this->getSpan(t('inkl.').' '. $realCategories));
            } elseif ($category === t('Instandhaltung')) {
                $H1->appendChild($this->getSpan(t('inkl. ').' '.$this->getMaintenanceLifeCycleIdents()));
            } elseif ($isLifeCycle && $category === 'D') {
                $H1->appendChild($this->getSpan(t('Gesamt (energetisch und stofflich)')));
            } elseif ($isLifeCycle && $category === 'D energetisch') {
                $H1->textContent = t('D');
                $H1->appendChild($this->getSpan(t('energetisch (gemäß DIN EN 15978)')));
            } elseif ($isLifeCycle && $category === 'D stofflich') {
                $H1->textContent = t('D');
                $H1->appendChild($this->getSpan(t('stofflich (gemäß DIN EN 15804)')));
            }


            $this->appendEffect($TypeLi, $dataSet, $addBenchmarks, $isLifeCycle && $totalEffects, $addPhaseRec);

            if ($addBenchmarks && $this->benchmarkVersionId) {
                $this->buildBenchmarkChart($TypeLi, $this->projectVariantId);
            }
        }

        $this->addClass($TypeLi, 'last');
    }

    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement     $Container
     * @param  ElcaReportSet $ReportSet
     */
    private function buildBenchmark(
        DOMElement $Container,
        ElcaReportSet $ReportSet
    ) {
        if (!$ReportSet->count()) {
            return;
        }

        /**
         * Normalize values
         * All values per m2 and year
         */
        $projectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $dbIsEn15804Compliant = $projectVariant->getProject()->getProcessDb()->isEn15804Compliant();
        $addPhaseRec    = $dbIsEn15804Compliant;
        $hasGroup = $hasGroupBenchmark = $hasInitBenchmark = false;
        $benchmarkModel = null;

        /**
         * Compute Benchmark
         */
        if ($this->benchmarkVersionId) {
            $benchmarkModel = Environment::getInstance()->getContainer()->get(BenchmarkSystemsService::class)
                ->benchmarkSystemModelByVersionId($this->benchmarkVersionId);

            $benchmarkVersion = ElcaBenchmarkVersion::findById($this->benchmarkVersionId);

            /**
             * Get indicator benchmarks
             */
            $indicatorBenchmarks = ElcaProjectIndicatorBenchmarkSet::find(
                ['project_variant_id' => $this->projectVariantId]
            )->getArrayBy('benchmark', 'indicatorId');

            /**
             * Compute benchmarks
             */
            $benchmarkService = Environment::getInstance()->getContainer()->get(BenchmarkService::class);
            $benchmarks       = $benchmarkService->compute(
                $benchmarkVersion,
                $projectVariant
            );

            $groupBenchmarks = $benchmarkService->groupBenchmark($benchmarkVersion, $benchmarks);


            foreach ($ReportSet as $reportDO) {
                $reportDO->benchmark     = isset($benchmarks[$reportDO->ident]) ? ElcaNumberFormat::toString(
                    $benchmarks[$reportDO->ident],
                    2
                ) : null;
                $reportDO->initBenchmark = $indicatorBenchmarks[$reportDO->indicator_id] ?? null;
                $reportDO->group = isset($groupBenchmarks[$reportDO->ident]) ? $groupBenchmarks[$reportDO->ident]->name : null;
                $reportDO->groupBenchmark = isset($groupBenchmarks[$reportDO->ident]) ? $groupBenchmarks[$reportDO->ident]->caption : null;

                if ($reportDO->group) {
                    $hasGroup = true;
                }
                if ($reportDO->groupBenchmark) {
                    $hasGroupBenchmark = true;
                }
                if ($reportDO->initBenchmark) {
                    $hasInitBenchmark = true;
                }
            }

            if (isset($benchmarks['pe'])) {
                $ReportSet->add(
                    (object)[
                        'ident'     => 'pe',
                        'name'      => $dbIsEn15804Compliant ? t('KSB 1.2.1') : t('KSB 1.2.2'),
                        'benchmark' => ElcaNumberFormat::toString(
                            $benchmarks[IndicatorIdent::PE],
                            2
                        ),
                        'group'=> null,
                        'groupBenchmark' => null,
                    ]
                );
            }
        }

        $projectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);
        $m2AndYear                 = max(
            1,
            $projectVariant->getProject()->getLifeTime() * $projectConstruction->getNetFloorSpace()
        );

        $m2LivingSpaceAndYear = $projectConstruction->getLivingSpace()
            ? $projectVariant->getProject()->getLifeTime() * $projectConstruction->getLivingSpace()
            : null;

        $reports = [
            'indicators'   => [],
            'peIndicators' => [],
        ];
        foreach ($ReportSet as $reportDO) {
            $reportDO->norm_total_value = isset($reportDO->value) ? $reportDO->value / $m2AndYear : null;

            if ($m2LivingSpaceAndYear) {
                $reportDO->norm_living_space_total_value = isset($reportDO->value)
                    ? $reportDO->value / $m2LivingSpaceAndYear : null;
            }

            $ident = new IndicatorIdent($reportDO->ident);
            if ($ident->isPrimaryEnergyIndicator()) {
                $key = 'peIndicators';

                if (IndicatorIdent::PE !== (string)$ident) {
                    // display kWh instead of MJ
                    $reportDO->norm_total_value /= 3.6;
                    $reportDO->unit             = 'kWh';
                }

            } else {
                $key = 'indicators';
            }

            if (isset($reportDO->benchmark)) {
                $reports[$key][] = $reportDO;
            }
        }

        $typeUl = $Container->appendChild($this->getUl(['class' => 'category']));
        $typeLi = $typeUl->appendChild($this->getLi(['class' => 'section clearfix']));

        $h1 = $typeLi->appendChild($this->getH1(t('Gesamt')));
        $h1->appendChild($this->getSpan(t('inkl.').' '.$this->getTotalLifeCycleIdents()));

        $this->appendEffect(
            $typeLi,
            $reports['indicators'],
            true,
            false, $addPhaseRec,
            $reports['peIndicators'],
            null !== $m2LivingSpaceAndYear,
            $hasInitBenchmark,
            $hasGroup,
            $hasGroupBenchmark,
            null !== $benchmarkModel ? !$benchmarkModel->displayScores() : false
        );

        $this->addClass($typeLi, 'last');

        if ($this->benchmarkVersionId) {
            $this->buildBenchmarkChart($typeLi, $this->projectVariantId);

            $this->appendBenchmarkComment($typeLi);
        }
    }

    /**
     * Appends a table for one effect
     *
     * @param  DOMElement $Container
     * @param array       $dataSet
     * @param bool        $addBenchmarks
     * @param bool        $addBars
     * @param bool        $addPhaseRec
     *
     * @return void -
     */
    private function appendEffect(
        DOMElement $Container,
        array $dataSet,
        $addBenchmarks = false,
        $addBars = false,
        $addPhaseRec = false,
        array $secondDataSet = null,
        $addLivingSpaceAndYear = false,
        $hasBenchmarkInit = false,
        $hasBenchmarkGroup = false,
        $hasBenchmarkGroupBenchmark = false,
        $hideTotalScores = false
    ) {
        if (0 === count($dataSet)) {
            return;
        }

        $FirstDO = $dataSet[0];

        $table = new HtmlTable('report report-effects');
        $table->addColumn('name', t('Indikator'));
        $table->addColumn('unit', t('Einheit'));
        $table->addColumn('norm_total_value', t('Umweltwirkung').' / m²a');

        if ($addLivingSpaceAndYear) {
            $table->addColumn('norm_living_space_total_value', t('Umweltwirkung').' / m²WFa');
        }

        if (isset($FirstDO->norm_prod_value)) {
            $table->addColumn('norm_prod_value', t('Herstellung').' / m²a');
            $table->addColumn('norm_maint_value', t('Instandhaltung').' / m²a');
            $table->addColumn('norm_eol_value', t('Entsorgung').' / m²a');

            if ($addPhaseRec) {
                $table->addColumn('norm_rec_value', t('Rec.potential').' / m²a');
            }
        }

        if ($addBenchmarks) {
            if ($hasBenchmarkInit) {
                $table->addColumn('initBenchmark', t('Zielwert'));
            }

            if (!$hideTotalScores) {
                $table->addColumn('benchmark', t('Punktwert'));
            }

            if ($hasBenchmarkGroup) {
                $table->addColumn('group', t('Kriterium'));
            }
            if ($hasBenchmarkGroupBenchmark) {
                $table->addColumn('groupBenchmark', t('Bewertung'));
            }
        }

        if ($addBars) {
            $table->addColumn('percentage', '%');
            $table->addColumn('bar', '');
        }

        $Head    = $table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        /**
         * Add m2 Sub
         */
        $phaseColumns = [
            'norm_total_value' => t('Gesamt'),
            'norm_prod_value'  => t('Herstellung'),
            'norm_maint_value' => t('Instandhaltung'),
            'norm_eol_value'   => t('Entsorgung'),
        ];
        if ($addPhaseRec) {
            $phaseColumns['norm_rec_value'] = t('Rec.potential');
        }

        foreach ($phaseColumns as $col => $caption) {
            if (!isset($FirstDO->$col)) {
                continue;
            }

            $span = $HeadRow->getColumn($col)->setOutputElement(new HtmlTag('span', $caption.' / m²'));
            $span->add(new HtmlTag('sub', t('NGF')));
            $span->add(new HtmlStaticText('a'));
        }

        if ($addLivingSpaceAndYear) {
            $span = $HeadRow->getColumn('norm_living_space_total_value')->setOutputElement(new HtmlTag('span', 'Gesamt / m²'));
            $span->add(new HtmlTag('sub', t('WF')));
            $span->add(new HtmlStaticText('a'));
        }

        $Body = $table->createTableBody();
        $Row  = $Body->addTableRow();
        $Row->getColumn('norm_total_value')->setOutputElement(
            new ElcaHtmlNumericText('norm_total_value', 10, false, '?', null, null, true)
        );

        if ($addLivingSpaceAndYear) {
            $Row->getColumn('norm_living_space_total_value')->setOutputElement(
                new ElcaHtmlNumericText('norm_living_space_total_value', 10, false, '?', null, null, true)
            );
        }

        if (isset($FirstDO->norm_prod_value)) {
            $Row->getColumn('norm_prod_value')->setOutputElement(
                new ElcaHtmlNumericText('norm_prod_value', 10, false, '?', null, null, true)
            );
            $Row->getColumn('norm_maint_value')->setOutputElement(
                new ElcaHtmlNumericText('norm_maint_value', 10, false, '?', null, null, true)
            );
            $Row->getColumn('norm_eol_value')->setOutputElement(
                new ElcaHtmlNumericText('norm_eol_value', 10, false, '?', null, null, true)
            );

            if ($addPhaseRec) {
                $Row->getColumn('norm_rec_value')->setOutputElement(
                    new ElcaHtmlNumericText('norm_rec_value', 10, false, '?', null, null, true)
                );
            }
        }

        if ($addBars) {
            $Row->getColumn('percentage')->setOutputElement(new ElcaHtmlNumericText('percentage', 1, true));
            $Row->getColumn('bar')->setOutputElement(new ElcaHtmlReportBar('bar'));
        }

        $Row->getColumn('name')->setOutputElement(new HtmlText('name', new ElcaTranslatorConverter()));
        $Row->getColumn('unit')->setOutputElement(new HtmlText('unit', new ElcaTranslatorConverter()));

        $Body->setDataSet($dataSet);

        if ($secondDataSet) {
            $Body = $table->createTableBody();
            $Body->addClass('second-data-set');
            $Body->setDataSet($secondDataSet);
        }

        $Tables = $Container->appendChild($this->getDiv(['class' => 'tables']));
        $table->appendTo($Tables);
    }

    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement $Container
     *
     * @return void -
     */
    private function buildRefModelEffects(DOMElement $Container)
    {
        $ProjectVariant      = ElcaProjectVariant::findById($this->projectVariantId);
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);
        $BenchmarkVersion    = $ProjectVariant->getProject()->getBenchmarkVersion();

        /**
         * Normalize values
         *
         * All values per m2 and year
         */
        $m2a = max(1, $ProjectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());

        $ReportSet   = ElcaReportSet::findTotalEffects($ProjectVariant->getId(), false, true);
        $totalValues = $ReportSet->getArrayBy('value', 'ident');

        $refConstrValues = ElcaBenchmarkRefConstructionValueSet::findByBenchmarkVersionId(
            $BenchmarkVersion->getId()
        )->getArrayBy('value', 'indicatorId');
        $refOpValues     = ElcaReportSet::findFinalEnergyRefModelEffects($ProjectVariant->getId())->getArrayBy(
            'value',
            'indicator_id'
        );

        $usedIndicators = [];
        foreach ($ReportSet as $Report) {
            $indicatorId    = $Report->indicator_id;
            $indicatorIdent = $Report->ident;

            $Report->norm_total_value = $totalValues[$indicatorIdent] / $m2a;

            if (isset($refConstrValues[$indicatorId], $refOpValues[$indicatorId]) &&
                $refConstrValues[$indicatorId] && $refOpValues[$indicatorId]
            ) {
                $Report->norm_total_ref_value = $refConstrValues[$indicatorId] + $refOpValues[$indicatorId] / $m2a;
                $Report->ratio                = $Report->norm_total_value / $Report->norm_total_ref_value;

                $usedIndicators[] = $indicatorId;
            }
        }

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category']));
        $TypeLi = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
        $TypeLi->appendChild($this->getH1(t('Vergleich Referenzgebäude')));

        $this->appendRefModelEffect($TypeLi, $ReportSet);

        $ChartsContainer = $TypeLi->appendChild($this->getDiv(['class' => 'indicator-charts']));
        $this->buildRefModelChart($ChartsContainer, $this->projectVariantId, $usedIndicators);
    }
    // End buildBenchmarkChart

    /**
     * Appends a table for one effect
     *
     * @param  DOMElement $Container
     * @param array       $dataSet
     *
     * @return void -
     */
    private function appendRefModelEffect(DOMElement $Container, $dataSet)
    {
        $Table = new HtmlTable('report report-effects');
        $Table->addColumn('name', t('Indikator'));
        $Table->addColumn('unit', t('Einheit'));
        $Table->addColumn('norm_total_value', '');
        $Table->addColumn('norm_total_ref_value', '');
        $Table->addColumn('ratio', t('IST / REF'));

        $Head    = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        /**
         * Add m2 Sub
         */
        foreach (
            [
                'norm_total_value'     => t('Gesamt IST'),
                'norm_total_ref_value' => t('Gesamt REF'),
            ] as $col => $caption
        ) {
            $Span = $HeadRow->getColumn($col)->setOutputElement(new HtmlTag('span', $caption.' / m²'));
            $Span->add(new HtmlTag('sub', t('NGF')));
            $Span->add(new HtmlStaticText('a'));
        }

        $Body = $Table->createTableBody();
        $Row  = $Body->addTableRow();
        $Row->getColumn('norm_total_value')->setOutputElement(
            new ElcaHtmlNumericText('norm_total_value', 10, false, '?', null, null, true)
        );
        $Row->getColumn('norm_total_ref_value')->setOutputElement(
            new ElcaHtmlNumericText('norm_total_ref_value', 10, false, '?', null, null, true)
        );
        $Row->getColumn('ratio')->setOutputElement(new ElcaHtmlNumericText('ratio', 2, false));

        $Body->setDataSet($dataSet);
        $Tables = $Container->appendChild($this->getDiv(['class' => 'tables']));
        $Table->appendTo($Tables);
    }

    private function appendBenchmarkComment(DOMElement $container)
    {
        $comment = ElcaProjectVariantAttribute::findValue(
            $this->projectVariantId,
            ElcaProjectVariantAttribute::IDENT_LCA_BENCHMARK_COMMENT
        );

        if (!empty($comment)) {
            $printContainer = $container->appendChild($this->getDiv(['class' => 'benchmark-print-comment clear']));
            $printContainer->appendChild($this->getH3(t('Anmerkungen')));

            $this->appendMultilineAsPTags($printContainer, $comment, true);
        }

        $form = new HtmlForm('reportForm', '/project-reports/benchmarks/');
        $form->addClass('clear');
        $form->setRequest(FrontController::getInstance()->getRequest());

        $form->add(
            new ElcaHtmlFormElementLabel(
                t('Anmerkungen'),
                new HtmlTextArea(
                    'comment',
                    $comment
                )
            )
        );

        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('clear buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

        $form->appendTo($container);
    }

    // End buildRefModelChart
}
// End ElcaReportEffectsView
