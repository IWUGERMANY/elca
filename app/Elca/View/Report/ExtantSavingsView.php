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
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use DOMElement;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Processing\ExtantSavingsCalculator;
use Elca\Model\Processing\IndicatorResults;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlReportBar;
use Elca\View\helpers\ElcaTranslatorConverter;

/**
 * Builds the summary report for first level element types, life cycle and in total
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ExtantSavingsView extends ElcaReportsView
{
    private $readOnly;

    /**
     * @var ExtantSavingsCalculator
     */
    private $extantSavingsCalculator;


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

        $this->readOnly = $this->get('readOnly', false);

        $this->extantSavingsCalculator = Environment::getInstance()->getContainer()->get(ExtantSavingsCalculator::class);
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

        $projectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $infoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)').': '));
        $infoDl->appendChild(
            $this->getDd([], ElcaNumberFormat::toString($projectConstruction->getNetFloorSpace()).' m²')
        );

        $tdContainer = $this->appendPrintTable($Container);
        $this->buildTotalEffects($infoDl, $projectConstruction, $tdContainer, false);
    }


    protected function buildTotalEffects(
        DOMElement $infoDl,
        ElcaProjectConstruction $ProjectConstruction,
        DOMElement $tdContainer
    ) {
        $processDbId = new ProcessDbId($this->Project->getProcessDbId());
        $isEn15804Compliant = $this->Project->getProcessDb()->isEn15804Compliant();
        $extantComponents = ElcaElementComponentSet::findByProjectVariantId($this->projectVariantId, ['is_extant' => true]);
        $indicators = ElcaIndicatorSet::findWithPetByProcessDbId($processDbId->value(), true, true)->getArrayCopy('id');

        $infoDl->appendChild($this->getDt([], t('Anzahl Bestandsmaterialien').': '));
        $infoDl->appendChild(
            $this->getDd([], ElcaNumberFormat::toString($extantComponents->count()))
        );

        $tdContainer->appendChild(
            $this->getP(
                t(
                    'Für :count: Baustoffe im Bestand wird folgende Ersparnis in der Herstellung erzielt.',
                    null,
                    [':count:' => $extantComponents->count()]
                )
            )
        );

        $results = [];

        foreach ($extantComponents as $component) {

            /**
             * @var IndicatorResults $indicatorResults
             */
            foreach ($this->extantSavingsCalculator->computeElementComponentSavings($component, $processDbId) as $indicatorResults) {
                $module = $indicatorResults->module()->value();
                foreach ($indicatorResults->getIterator() as $indicatorResult) {
                    $indicatorId = (string)$indicatorResult->indicatorId();
                    if (!isset($results[$module])) {
                        $results[$module] = [];
                    }
                    if (!isset($results[$module][$indicatorId])) {
                        $results[$module][$indicatorId] = (object)[
                            'name' => $indicators[$indicatorId]->getName(),
                            'unit' => $indicators[$indicatorId]->getUnit(),
                            'module' => $module,
                            'indicator_id' => $indicatorId,
                            'value' => 0,
                        ];
                    }

                    $results[$module][$indicatorId]->value += $indicatorResult->value();
                }
            }
        }

        if (!$dolist = array_values($results[$isEn15804Compliant ? (string)Module::a13() : (string)Module::production()])) {
            return;
        }

        $this->buildEffects($tdContainer, $dolist);
    }


    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement     $container
     * @param  ElcaReportSet $ReportSet
     * @param bool           $isLifeCycle
     * @param bool           $addBenchmarks
     * @param array          $totalEffects
     *
     * @return void -
     */
    private function buildEffects(
        DOMElement $container,
        array $doList,
        array $totalEffects = null
    ) {
        if (!\count($doList)) {
            return;
        }

        $projectVariant      = ElcaProjectVariant::findById($this->projectVariantId);
        $projectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        /**
         * Normalize values
         *
         * All values per m2 and year
         */
        $m2a = max(1, $projectVariant->getProject()->getLifeTime() * $projectConstruction->getNetFloorSpace());

        $reports = [];
        foreach ($doList as $reportDO) {
            $reportDO->norm_total_value = $reportDO->value / $m2a;
            $key                        = $reportDO->module;

            if ($totalEffects) {
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

        ksort($reports, SORT_STRING);

        $typeUl = $container->appendChild($this->getUl(['class' => 'category']));
        foreach ($reports as $category => $dataSet) {
            $typeLi = $typeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $typeLi->appendChild($this->getH1(t($category)));

            $this->appendEffect($typeLi, $dataSet);
        }

        $this->addClass($typeLi, 'last');
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
        $addBars = false
    ) {
        if (0 === count($dataSet)) {
            return;
        }

        $firstDO = $dataSet[0];

        $table = new HtmlTable('report report-effects');
        $table->addColumn('name', t('Indikator'));
        $table->addColumn('unit', t('Einheit'));
        $table->addColumn('norm_total_value', t('Umweltwirkung').' / m²a');

        if (isset($firstDO->norm_prod_value)) {
            $table->addColumn('norm_prod_value', t('Herstellung').' / m²a');
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
            'norm_prod_value'  => t('Herstellung'),
        ];

        foreach ($phaseColumns as $col => $caption) {
            if (!isset($firstDO->$col)) {
                continue;
            }

            $span = $HeadRow->getColumn($col)->setOutputElement(new HtmlTag('span', $caption.' / m²'));
            $span->add(new HtmlTag('sub', t('NGF')));
            $span->add(new HtmlStaticText('a'));
        }

        $Body = $table->createTableBody();
        $Row  = $Body->addTableRow();
        $Row->getColumn('norm_total_value')->setOutputElement(
            new ElcaHtmlNumericText('norm_total_value', 10, false, '?', null, null, true)
        );

        if (isset($firstDO->norm_prod_value)) {
            $Row->getColumn('norm_prod_value')->setOutputElement(
                new ElcaHtmlNumericText('norm_prod_value', 10, false, '?', null, null, true)
            );
        }

        if ($addBars) {
            $Row->getColumn('percentage')->setOutputElement(new ElcaHtmlNumericText('percentage', 1, true));
            $Row->getColumn('bar')->setOutputElement(new ElcaHtmlReportBar('bar'));
        }

        $Row->getColumn('name')->setOutputElement(new HtmlText('name', new ElcaTranslatorConverter()));
        $Row->getColumn('unit')->setOutputElement(new HtmlText('unit', new ElcaTranslatorConverter()));

        $Body->setDataSet($dataSet);

        $Tables = $Container->appendChild($this->getDiv(['class' => 'tables']));
        $table->appendTo($Tables);
    }
}
