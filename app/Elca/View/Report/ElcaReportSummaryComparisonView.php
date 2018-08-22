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

use Beibob\Blibs\FrontController;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use DOMElement;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\Model\Process\Module;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlReportBar;

/**
 * Builds the summary report for first level element types, life cycle and in total
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaReportSummaryComparisonView extends ElcaReportsView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_TOTAL  = 'compareSummary';
    const BUILDMODE_ELEMENT_TYPES  = 'compareElementTypes';

    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * indicatorId
     */
    private $indicatorId;

    /**
     * project variant id to compare with
     */
    private $compareWithVariantId;

    /**
     * Just update the charts
     */
    private $updateCharts = false;


    // protected


    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_TOTAL);
        $this->indicatorId = $this->get('indicatorId');
        $this->compareWithVariantId = $this->get('compareWithProjectVariantId');
        $this->updateCharts = $this->get('updateCharts', false);
    }
    // End init


    /**
     * Renders the report
     *
     * @param DOMElement         $Container
     * @param DOMElement         $InfoDl
     * @param ElcaProjectVariant $ProjectVariant
     * @param                    $lifeTime
     */
    protected function renderReports(DOMElement $Container, DOMElement $InfoDl, ElcaProjectVariant $ProjectVariant, $lifeTime)
    {
        $this->addClass($Container, 'report-compare report-'. $this->buildMode);

        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $InfoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)') .': '));
        $InfoDl->appendChild($this->getDd([], ElcaNumberFormat::toString($ProjectConstruction->getNetFloorSpace()) .' m²'));

        $Form = new HtmlForm('reportForm', '/project-reports/'. $this->buildMode .'/');
        $Form->setRequest(FrontController::getInstance()->getRequest());

        $Form->add(new ElcaHtmlFormElementLabel(t('Variante'). ' A (100%) ', new HtmlStaticText($ProjectVariant->getName() .' ['. $ProjectVariant->getPhase()->getName() .']')))->addClass('projectVariantId');

        $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Variante'). ' B: ', new HtmlSelectbox('projectVariantId', $this->compareWithVariantId)));
        $Select->add(new HtmlSelectOption('-- '. t('Bitte wählen') .' --', null));

        /** @var ElcaProjectVariant $Variant */
        foreach(ElcaProjectVariantSet::findByProjectId($ProjectVariant->getProjectId()) as $Variant) {
            if ($this->projectVariantId != $Variant->getId()) {
                $Opt = $Select->add(new HtmlSelectOption($Variant->getName().' ['. t($Variant->getPhase()->getName()).']', $Variant->getId()));
            }
        }
        if ($this->buildMode == self::BUILDMODE_ELEMENT_TYPES) {
            $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Indikator'), new HtmlSelectbox('i')));

            foreach(ElcaIndicatorSet::findWithPetByProcessDbId($ProjectVariant->getProject()->getProcessDbId()) as $Indicator)
                $Select->add(new HtmlSelectOption($Indicator->getName(), $Indicator->getId()));
        }

        $Form->appendTo($Container);
        $this->appendNonDefaultLifeTimeInfo($InfoDl);


        $TdContainer = $this->appendPrintTable($Container);

        if ($this->compareWithVariantId) {

            $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

            $CompareVariant = ElcaProjectVariant::findById($this->compareWithVariantId);
            $CompareConstruction = ElcaProjectConstruction::findByProjectVariantId($this->compareWithVariantId);

            /**
             * Normalize values
             *
             * All values per m2 and year
             */
            $m2aA = max(1, $ProjectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());
            $m2aB = max(1, $CompareVariant->getProject()->getLifeTime() * $CompareConstruction->getNetFloorSpace());

            switch($this->buildMode)
            {
                case self::BUILDMODE_ELEMENT_TYPES:
	                $Effects = ElcaReportSet::findComparisonEffectsPerElementTypes($this->projectVariantId, $this->compareWithVariantId, [ElcaLifeCycle::PHASE_TOTAL], null, null, 2);
                    $reports = $this->normalizeEffects($Effects, $m2aA, $m2aB);
					ksort($reports);

                    $this->buildElementTypeEffects($TdContainer, $reports);
                    break;

                default:
                case self::BUILDMODE_TOTAL:
                    $Effects = ElcaReportSet::findComparisonTotalEffectsPerLifeCycle($this->projectVariantId, $this->compareWithVariantId);
                    $reports = $this->normalizeEffects($Effects, $m2aA, $m2aB);

                    $this->buildEffects($TdContainer, $reports);
                    break;
            }
        }
    }
    // End beforeRender


    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement $Container
     * @param array      $reports
     * @return void -
     */
    private function buildEffects(DOMElement $Container, array $reports)
    {
        if(!count($reports))
            return;

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category']));
        foreach($reports as $category => $dataSet)
        {
            if (Module::fromValue($category)->isA1A2OrA3()) {
                continue;
            }

            $TypeLi = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $TypeLi->appendChild($this->getH1(t($category)));
            $this->appendEffect($TypeLi, $dataSet);
        }
    }
    // End beforeRender


    /**
     * Builds the view for element type report
     *
     * @param DOMElement $Container
     * @param array      $reports
     * @return void -
     */
    private function buildElementTypeEffects(DOMElement $Container, array $reports)
    {
        if(!count($reports))
            return;

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category']));
        foreach($reports as $category => $data)
        {
            $TypeLi = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $TypeLi->appendChild($this->getH1(t($category)));

            $this->appendEffect($TypeLi, $data);
            $FirstDO = $data[0];
            $this->buildCompareChart($TypeLi, 'compareElementTypesChart', $this->projectVariantId, $this->compareWithVariantId, $FirstDO->element_type_node_id);
        }
    }
    // End buildElementTypeEffects


    /**
     * Appends a table for one effect
     *
     * @param  DOMElement $Container
     * @param array       $dataSet
     * @return void -
     */
    private function appendEffect(DOMElement $Container, array $dataSet)
    {
        //$FirstDO = $dataSet[10];

        $Table = new HtmlTable('report report-effects');
        $Table->addColumn('name', t('Indikator'));
        $Table->addColumn('unit', t('Einheit'));
        $Table->addColumn('value_a', t('Variante') .' A / m²a');
        $Table->addColumn('value_b', t('Variante') . ' B / m²a');
        $Table->addColumn('value_delta', t('Abweichung'));
        $Table->addColumn('value_perc', '%');
        $Table->addColumn('value_bar', '');

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();
        $Row->getColumn('value_a')->setOutputElement(new ElcaHtmlNumericText('value_a', 10, false, ',', null, null, true));
        $Row->getColumn('value_b')->setOutputElement(new ElcaHtmlNumericText('value_b', 10, false, ',', null, null, true));
        $Row->getColumn('value_delta')->setOutputElement(new ElcaHtmlNumericText('value_delta', 10, false, ',', null, null, true));
        $Row->getColumn('value_perc')->setOutputElement(new ElcaHtmlNumericText('value_perc', 1, true));
        $Row->getColumn('value_bar')->setOutputElement(new ElcaHtmlReportBar('value_bar'));

        $Body->setDataSet($dataSet);
        $Tables = $Container->appendChild($this->getDiv(['class' => 'tables']));
        $Table->appendTo($Tables);
    }
    // End appendEffect


    /**
     * Appends the container for the stacked bar chart
     *
     * @param  DOMElement $Container
     * @param             $projectVariantAId
     * @param             $projectVariantBId
     * @param             $relKey
     * @return void -
     */
    protected function buildCompareChart(DOMElement $Container, $action, $projectVariantAId, $projectVariantBId, $relKey)
    {
        $args = ['v1' => $projectVariantAId, 'v2' => $projectVariantBId, 'k' => $relKey];

        if($this->indicatorId)
            $args['i'] = $this->indicatorId;

        $attributes = ['class' => 'chart grouped-stacked-bar-chart',
                            'data-url' => Url::factory('/elca/project-reports/'. $action .'/', $args)];;

        $Container->appendChild($this->getDiv($attributes));
    }
    // End buildCompareChart


    /**
     * @param ElcaReportSet $effects
     * @param               $m2aA
     * @param               $m2aB
     * @return array
     */
    protected function normalizeEffects(ElcaReportSet $effects, $m2aA, $m2aB)
    {
        /**
         * Restructure
         */
        $reports = [];
        foreach($effects as $dataObject) {
            $key = $dataObject->din_code.' '.$dataObject->category;
            if (!isset($reports[$key])) {
                $reports[$key] = [];
            }

            $dataObject->value_a     /= $m2aA;
            $dataObject->value_b     /= $m2aB;
            $dataObject->value_delta = $dataObject->value_b - $dataObject->value_a;
            $dataObject->value_perc  = $dataObject->value_a? $dataObject->value_delta / abs($dataObject->value_a) : null;
            $dataObject->value_bar   = $dataObject->value_perc? round($dataObject->value_perc * 100) : null;
            $reports[$key][]         = $dataObject;
        }

        return $reports;
    }
}

