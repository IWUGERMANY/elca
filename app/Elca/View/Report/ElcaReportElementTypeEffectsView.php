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
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use DOMElement;
use DOMNode;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\Model\Common\Transform\ArrayOfObjects;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorId;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Process\Module;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Elca\Model\Report\IndicatorEffect;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\Report\HtmlIndicatorEffectsTable;

/**
 * Builds the summary report for first level element types, life cycle and in total
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaReportElementTypeEffectsView extends ElcaReportsView
{
    /**
     * indicatorId
     */
    private $indicatorId;

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

        $this->indicatorId = $this->get('indicatorId');
    }
    // End init


    /**
     * Renders the report
     *
     * @param  DOMElement        $Container
     * @param DOMElement         $InfoDl
     * @param ElcaProjectVariant $ProjectVariant
     * @param                    $lifeTime
     */
    protected function renderReports(DOMElement $Container, DOMElement $InfoDl, ElcaProjectVariant $ProjectVariant, $lifeTime)
    {
        $this->addClass($Container, 'report-summary report-summary-element-types');

        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $InfoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)') . ': '));
        $InfoDl->appendChild($this->getDd([], ElcaNumberFormat::toString($ProjectConstruction->getNetFloorSpace()) . ' m²'));

        $TdContainer = $this->appendPrintTable($Container);

        $Form = new HtmlForm('reportForm', '/project-reports/summaryElementTypes/');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Indikator'), new HtmlSelectbox('i')));
        foreach (ElcaIndicatorSet::findWithPetByProcessDbId($ProjectVariant->getProject()->getProcessDbId()) as $Indicator) {
            $Select->add($Option = new HtmlSelectOption(t($Indicator->getName()), $Indicator->getId()));
            if ($Indicator->getId() == $this->indicatorId)
                $Option->setAttribute('selected', 'selected');
        }

        $Form->appendTo($Container);

        $this->appendNonDefaultLifeTimeInfo($InfoDl);
        $this->buildElementTypeEffects($TdContainer, ElcaReportSet::findEffectsPerElementType($this->projectVariantId, null, null, null, 2, 0));
    }
    // End beforeRender


    /**
     * Builds the view for element type report
     *
     * @param DOMElement     $Container
     * @param  ElcaReportSet $reportSet
     *
     * @return void -
     */
    private function buildElementTypeEffects(DOMElement $Container, ElcaReportSet $reportSet)
    {
        if (!$reportSet->count()) {
            return;
        }

        $lifeCycleUsages = Environment::getInstance()
                                      ->getContainer()
                                      ->get(LifeCycleUsageService::class)
                                      ->findLifeCycleUsagesForProject(new ProjectId($this->Project->getId()));

        $reports = $this->prepareReportSet($reportSet, $lifeCycleUsages);

        $elementTypes = [];

        $typeUl = $Container->appendChild($this->getUl(['class' => 'category']));
        foreach ($reports as $dinCode => $dataSet) {

            $indicatorItem = $dataSet[$this->indicatorId];
            $headline = $indicatorItem->category === ElcaElementType::ROOT_NODE ? t('Gesamt / Konstruktion') : $dinCode .' '. $indicatorItem->category;
            $elementTypes[ $indicatorItem->element_type_node_id ] = $indicatorItem->phases[ElcaLifeCycle::PHASE_TOTAL];

            foreach ($dataSet as $indicatorId => $indicator) {
                $dataSet[$indicatorId] = new IndicatorEffect(
                    $indicator->indicator,
                    $indicator->phases
                );
            }

            $typeLi = $typeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $H1 = $typeLi->appendChild($this->getH1($headline));

            if (isset($elementTypes[ $indicatorItem->parent_element_type_node_id ])) {
                $indicatorPercent = ElcaNumberFormat::toString(
                    $indicatorItem->phases[ElcaLifeCycle::PHASE_TOTAL] / max(1, $elementTypes[$indicatorItem->parent_element_type_node_id]),
                    2,
                    true
                );
                $H1->appendChild($this->getSpan(t($indicatorItem->indicator->name()) . ' ' . $indicatorPercent . '%'));
            }

            $dataSet = array_values($dataSet);

            $effectsTable = new HtmlIndicatorEffectsTable('element-type-effects', $dataSet, $lifeCycleUsages);
            $effectsTable->appendTo($typeLi);

            $this->buildStackedBarChart($typeLi, $this->projectVariantId, $indicatorItem->element_type_node_id);
        }
    }
    // End buildElementTypeEffects


    /**
     * Appends the container for the stacked bar chart
     *
     * @param  DOMNode $Container
     * @param          $projectVariantId
     * @param          $elementTypeNodeId
     */
    protected function buildStackedBarChart(DOMNode $Container, $projectVariantId, $elementTypeNodeId)
    {
        $args = [
            'v' => $projectVariantId,
            't' => $elementTypeNodeId
        ];

        if ($this->indicatorId) {
            $args['i'] = $this->indicatorId;
        }

        $attributes = ['class'    => 'chart stacked-bar-chart',
                       'data-url' => Url::factory('/elca/project-reports/elementTypeChart/', $args)];;

        $Container->appendChild($this->getDiv($attributes));
    }

    /**
     * @param ElcaReportSet $reportSet
     * @param               $lifeCycleUsages
     * @return array
     */
    private function prepareReportSet(ElcaReportSet $reportSet, LifeCycleUsages $lifeCycleUsages)
    {
        $ProjectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);
        $isEn15804Compliant = $this->Project->getProcessDb()->isEn15804Compliant();

        /**
         * All values per m2 and year
         */
        $m2a = max(1, $ProjectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());

        $list    = new ArrayOfObjects($reportSet->getArrayCopy());
        $reports = $list->groupBy(
            [
                'din_code',
                'indicator_id',
            ],
            function ($current, $item) use ($m2a, $isEn15804Compliant, $lifeCycleUsages) {
                $current->category                    = $item->category;
                $current->element_type_node_id        = $item->element_type_node_id;
                $current->parent_element_type_node_id = $item->parent_element_type_node_id;

                if (!isset($current->indicator)) {
                    $current->indicator = new Indicator(
                        new IndicatorId($item->indicator_id),
                        $item->name,
                        new IndicatorIdent($item->name),
                        $item->unit,
                        $isEn15804Compliant
                    );
                }

                if (!isset($current->phases)) {
                    $current->phases = [
                        ElcaLifeCycle::PHASE_PROD  => 0,
                        ElcaLifeCycle::PHASE_MAINT => 0,
                        ElcaLifeCycle::PHASE_EOL   => 0,
                        ElcaLifeCycle::PHASE_REC   => 0,
                        ElcaLifeCycle::PHASE_TOTAL => 0,
                    ];
                }

                if ($item->life_cycle_phase !== ElcaLifeCycle::PHASE_TOTAL &&
                    $item->life_cycle_phase !== ElcaLifeCycle::PHASE_MAINT &&
                    $item->life_cycle_phase !== ElcaLifeCycle::PHASE_REC &&
                    !$lifeCycleUsages->moduleIsAppliedInConstruction(new Module($item->life_cycle_ident))
                ) {
                    return;
                }

                $current->phases[$item->life_cycle_phase] += $item->value / $m2a;
            }
        );

        return $reports;
    }
}
