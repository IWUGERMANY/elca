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
            //$Row->getColumn('choose')->setOutputElement(new HtmlTag('span',null, ['class' => 'arrowrow']));
            $Row->getColumn('value_dincodeSum')->setOutputElement(new HtmlText('value_dincodeSum'));
            $Row->getColumn('din_code')->setOutputElement(new HtmlText('din_code'));
            $Row->getColumn('mass')->setOutputElement(new ElcaHtmlNumericText('mass', 1, true));
            $Row->getColumn('volume')->setOutputElement(new ElcaHtmlNumericText('volume', 1, true));

			
			foreach($dataSetValue as $dataKGKey => $dataKGValue)
			{
				foreach($dataKGValue as $dataKGSingleKey => $dataKGSingleValue)
				{
					// First = summary 
					if($dataKGSingleKey==0) 
					{
						// $Body->setDataSet($dataKGValue);
					}
					else
					{
						$Body->setDataSet($dataKGValue);
					}	
				}	
				
                $Table->appendTo($reportAVV);
			}
           
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

    
}
// End ElcaReportSummaryWasteCodeView
