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
use Beibob\HtmlTools\Interfaces\Formatter;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlSubmitButton;
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
use Elca\Db\ElcaProjectAttribute;
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
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlReportBar;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaTranslatorConverter;

/**
 * Builds the summary report for AVV waste code keys
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaReportSummaryWasteCodeView extends ElcaReportsView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_TOTAL = 'total';

    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * indicatorId
     */
    private $indicatorId;

    private $readOnly;

    private $filterDO;

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
        $this->readOnly           = $this->get('readOnly', false);
        $this->filterDO           = $this->get('filterDO', new \stdClass());
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
        $this->addClass($Container, 'report-summary report-summary-wastecode report-summary-'.$this->buildMode);

        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $infoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)').': '));
        $infoDl->appendChild(
            $this->getDd([], ElcaNumberFormat::toString($ProjectConstruction->getNetFloorSpace()).' m²')
        );

        $tdContainer = $this->appendPrintTable($Container);

        $this->buildTotalEffects($infoDl, $ProjectConstruction, $tdContainer, false);

        $this->buildData($tdContainer, $projectVariant);
        /* 
        switch ($this->buildMode) {

            case self::BUILDMODE_TOTAL:
                // $this->buildTotalEffects($infoDl, $ProjectConstruction, $tdContainer, false);
                break;
        }
        */
        }


    /**
     * Builds the summary
     *
     * @param  DOMElement $Container
     *
     * @return void -
     */
    private function buildData(DOMElement $Container, $ProjectVariant)
    {

		$wastCodeData = ElcaReportSet::findWasteCode($this->projectVariantId);
		$wasteCodeNormalizedData = $this->normalizeData($wastCodeData);
		
	      
        foreach($wasteCodeNormalizedData as $dataKey => $dataSetValue)
		{
            $reportAVV = $Container->appendChild($this->getDiv(['class' => 'report']));  
			if($dataKey == 0) 
			{
				$avvHeadline = $reportAVV->appendChild($this->getH1(t("Ohne Zuordnung")));
			} else  {
				$avvHeadline = $reportAVV->appendChild($this->getH1(t("AVV ". $dataKey))); // ['class' => 'avv-number']
			}
 
            $Table = new HtmlTable('report-avv-waste-code');
            //$Table->addColumn('choose',$dataKey);
            $Table->addColumn('value_dincodeSum', t('KG'));
            $Table->addColumn('din_code');
            $Table->addColumn('mass', t('Masse [Kg]'));
            $Table->addColumn('volume', t('Volumen [m³]')); 
            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');
                  
            
             $Body = $Table->createTableBody();
             $Row = $Body->addTableRow();
             $Row->getColumn('value_dincodeSum')->setOutputElement(new HtmlText('value_dincodeSum'));
             $Row->getColumn('din_code')->setOutputElement(new HtmlText('din_code'));
             $Row->getColumn('mass')->setOutputElement(new ElcaHtmlNumericText('mass', 1, true));
             $Row->getColumn('volume')->setOutputElement(new ElcaHtmlNumericText('volume', 1, true));

             $Row->addAttrFormatter(new class implements Formatter {
                 public function format(HtmlElement $obj, $dataObject = null, $property = null) {
                    if ($dataObject && empty($dataObject->din_code)) {
                        $obj->addClass('summary-row');
                    } 
                    if ($dataObject && !empty($dataObject->din_code)) {
                        $dataObject->value_dincodeSum = '';
                    } 
                 }
            });
             // in flaches array umkopieren (wenn du es nicht schon vorher flach halten kannst)
             $dataSet = [];
             foreach($dataSetValue as $dataKGKey => $dataKGValue) {
                foreach($dataKGValue as $dataKGSingleKey => $dataKGSingleValue) {
                     $dataSet[] = $dataKGSingleValue;
                }  
             }

             $Body->setDataSet($dataSet);
             $Table->appendTo($reportAVV);
           
		}
    }
    // End buildData


/**
     * @param ElcaReportSet $data
     * @return array
     */
    protected function normalizeData(ElcaReportSet $data)
    {
        /**
         * Restructure data to array 
         */
        $report = [];
        foreach($data as $dataObject) {
			$key = 0;
			if(!is_null($dataObject->waste_code))
			{
				$key = $dataObject->waste_code.'-'.(!is_null($dataObject->waste_code_suffix)?:'000');
			}	
			if (!isset($report[$key])) {
                $report[$key] = [];
            }

            $dataObject->value_dincodeSum 	= (floor($dataObject->din_code/10)*10);
			
            // $dataObject->value_dincode 		= $dataObject->din_code;
            // $dataObject->value_mass   		= $dataObject->mass;
			// $dataObject->value_volume   	= $dataObject->volume;
			
            $report[$key][$dataObject->value_dincodeSum][$dataObject->din_code]  = $dataObject;
        }


		// Calculation and totals
		$defaultKGkey = 0;
		foreach($report as $reportKey => $reportData) 
		{
			foreach($reportData as $reportDataKGkey => $reportDataKGvalues) 
			{
				$reportTemp = [];
				$reportTemp[0] = (object)[
				    "project_variant_id"	=> 	0,
					"din_code"				=>  '',
					"element_type_name"		=>	'',
					"process_config_id"		=>	'',
					"name"					=>	'',
					"waste_code"			=>	0,
					"waste_code_suffix"		=>	0,
					"mass"					=>	array_sum(array_column($reportDataKGvalues, 'mass')),
					"volume"				=>	array_sum(array_column($reportDataKGvalues, 'volume')),
					"value_dincodeSum"		=>	$reportDataKGkey
				];
				
				$reportCalculated[$reportKey][$reportDataKGkey] = array_merge($reportTemp,$reportDataKGvalues);
			}
		}	

        return $reportCalculated;
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
        // $this->appendEpdTypeStatistic($infoDl);

        

        // return $TotalEffects->count();
    }
    // End beforeRender
    
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
    
    
}
// End ElcaReportSummaryWasteCodeView
