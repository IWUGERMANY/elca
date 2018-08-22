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

use Beibob\Blibs\DataObjectSet;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use DOMElement;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlEpdTypeFormatter;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaTranslatorConverter;

class EpdTypesView extends ElcaReportsView
{
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
    }
    // End init


    /**
     * Renders the report
     *
     * @param  DOMElement        $container
     * @param DOMElement         $infoDl
     * @param ElcaProjectVariant $projectVariant
     * @param                    $lifeTime
     */
    protected function renderReports(DOMElement $container, DOMElement $infoDl, ElcaProjectVariant $projectVariant, $lifeTime)
    {
        $this->addClass($container, 'report-summary report-summary-epd-types');

        $projectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $infoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)').': '));
        $infoDl->appendChild(
            $this->getDd([], ElcaNumberFormat::toString($projectConstruction->getNetFloorSpace()).' m²')
        );

        $this->appendEpdTypeStatistic($infoDl);

        $this->buildEpdTypeLifeCycleStatistic(
            $container,
            ElcaReportSet::countEpdSubTypesPerLifeCycle($projectVariant->getProject()->getProcessDbId(), $projectVariant->getId())
        );

        $indicatorEffects = ElcaReportSet::totalIndicatorEffectsPerLifeCycleAndEpdType($this->projectVariantId);
        $this->buildEpdTypeEffects($container, $indicatorEffects, $projectVariant, $projectConstruction);
    }

    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement     $container
     * @param  ElcaReportSet $reportSet
     *
     * @return void -
     */
    private function buildEpdTypeEffects(DOMElement $container, ElcaReportSet $reportSet, ElcaProjectVariant $projectVariant, ElcaProjectConstruction $projectConstruction)
    {
        if (!$reportSet->count())
            return;

        $projectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $projectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        /**
         * Normalize values
         *
         * All values per m2 and year
         */
        $m2a = max(1, $projectVariant->getProject()->getLifeTime() * $projectConstruction->getNetFloorSpace());

        $reports = [];
        $totals = [];
        $prodtotals = [];
        $mainttotals = [];

        $prodTotalDO  = new \stdClass();
        $prodTotalDO->lifeCyclePhase = '';
        $prodTotalDO->lifeCycleIdent = 'prodtotal';

        $maintTotalDO  = new \stdClass();
        $maintTotalDO->lifeCyclePhase = '';
        $maintTotalDO->lifeCycleIdent = 'mainttotal';

        $totalDO  = new \stdClass();
        $totalDO->lifeCyclePhase = '';
        $totalDO->lifeCycleIdent = 'total';

        foreach ($reportSet as $reportDO) {
            $epdType = $reportDO->epd_type;
            $key = $reportDO->life_cycle_phase .'_'. $reportDO->life_cycle_ident;

            if (!isset($reports[$epdType])) {
                $reports[$epdType] = [];
                $prodtotals[$epdType] = clone $prodTotalDO;
                $mainttotals[$epdType] = clone $maintTotalDO;
                $totals[$epdType] = clone $totalDO;
            }

            if (!isset($reports[$epdType][$key])) {
                if (ElcaLifeCycle::PHASE_MAINT === $reportDO->life_cycle_phase) {
                    $reports[$epdType]['prodtotal'] = $prodtotals[$epdType];
                }

                $do  = $reports[$epdType][$key] = new \stdClass();
                $do->lifeCyclePhase = $reportDO->life_cycle_phase;
                $do->lifeCycleIdent = $reportDO->life_cycle_ident;

            } else {
                $do  = $reports[$epdType][$key];
            }

            $colName = 'norm_'. $reportDO->indicator_id;
            $do->$colName = $reportDO->value / $m2a;

            if (!isset($prodtotals[$epdType]->$colName)) {
                $prodtotals[$epdType]->$colName = 0;
            }
            if (!isset($mainttotals[$epdType]->$colName)) {
                $mainttotals[$epdType]->$colName = 0;
            }

            if (ElcaLifeCycle::PHASE_MAINT !== $reportDO->life_cycle_phase) {
                $prodtotals[$epdType]->$colName += $do->$colName;
            } else {
                $mainttotals[$epdType]->$colName += $do->$colName;
            }

            if (!isset($totals[$epdType]->$colName)) {
                $totals[$epdType]->$colName = 0;
            }
            $totals[$epdType]->$colName += $do->$colName ;
        }

        foreach ($reports as $epdType => $dataSet) {
            $reports[$epdType]['mainttotal'] = $mainttotals[$epdType];
            $reports[$epdType]['total'] = $totals[$epdType];
        }

        $typeUl = $container->appendChild($this->getUl(['class' => 'category']));
        $typeLi = null;
        foreach (self::$epdTypeMap as $epdType => $caption) {

            if (!isset($reports[$epdType])) {
                continue;
            }

            $dataSet = $reports[$epdType];
            $typeLi = $typeUl->appendChild($this->getLi(['class' => 'section clearfix']));

            $typeLi->appendChild($this->getH1(t($caption)));

            $this->appendEffect($typeLi, $dataSet);
        }

        if ($typeLi) {
            $this->addClass($typeLi, 'last');
        }
    }
    // End appendEffects


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
    private function appendEffect(DOMElement $Container, array $dataSet)
    {
        $table = new HtmlTable('report report-effects epd-type-effects');
        $table->addColumn('lifeCyclePhase', t('Phase'));
        $table->addColumn('lifeCycleIdent', t('EPD Modul'));

        $indicators = ElcaIndicatorSet::findByProcessDbId($this->Project->getProcessDbId());
        foreach ($indicators as $indicator) {
            $table->addColumn('norm_'. $indicator->getId(), t($indicator->getName()));
        }

        $head = $table->createTableHead();
        $headRow = $head->addTableRow(new HtmlTableHeadRow());
        $headRow->addClass('table-headlines');

        $body = $table->createTableBody();
        $row = $body->addTableRow();
        $row->addAttrFormatter(new ElcaHtmlEpdTypeFormatter('lifeCycleIdent'));

        foreach ($indicators as $indicator) {
            $row->getColumn('norm_'.$indicator->getId())->setOutputElement(
                new ElcaHtmlNumericText('norm_'.$indicator->getId(), 5, false, '?', null, null, true)
            );
        }

        $row->getColumn('lifeCycleIdent')->setOutputElement(new ElcaHtmlEpdTypeFormatter('lifeCycleIdent', new ElcaTranslatorConverter()));
        $row->getColumn('lifeCyclePhase')->setOutputElement(new ElcaHtmlEpdTypeFormatter('lifeCyclePhase', new ElcaTranslatorConverter()));

        $body->setDataSet($dataSet);

        $footer = $table->createTableFoot();
        $footerRow = $footer->addTableRow();
        $firstColumn = $footerRow->getColumn('lifeCyclePhase');
        $firstColumn->setColSpan(2 + count($indicators));
        $firstColumn->setOutputElement(
            new HtmlStaticText(
                t('Gesamt inkl.') .' '. $this->getTotalLifeCycleIdents() .', '.
                t('Instandhaltung inkl.') .' '. $this->getMaintenanceLifeCycleIdents()
            )
        );


        $tables = $Container->appendChild($this->getDiv(['class' => 'tables']));
        $table->appendTo($tables);
    }

    /**
     * @param DOMElement    $container
     * @param DataObjectSet $reportSet
     */
    private function buildEpdTypeLifeCycleStatistic(DOMElement $container, DataObjectSet $reportSet)
    {
        $columns = [
            str_replace(' ', '_', ElcaProcess::EPD_TYPE_GENERIC) => 'Generisch',
            str_replace(' ', '_', ElcaProcess::EPD_TYPE_AVERAGE) => 'Durchschnitt',
            str_replace(' ', '_', ElcaProcess::EPD_TYPE_REPRESENTATIVE) => 'Repräsentativ',
            str_replace(' ', '_', ElcaProcess::EPD_TYPE_SPECIFIC) => 'Spezifisch',
            'total' => 'Summe',
        ];

        $dataSet = [];
        foreach ($reportSet as $reportDO) {

            $key = $reportDO->life_cycle_phase .'_'. $reportDO->life_cycle_ident;

            if (!isset($dataSet[$key])) {
                $do = $dataSet[$key] = new \stdClass();
                $do->lifeCyclePhase = $reportDO->life_cycle_phase;
                $do->lifeCycleIdent = $reportDO->life_cycle_ident;
                $do->total = 0;
            }
            else {
                $do = $dataSet[$key];
            }

            $colName = null === $reportDO->epd_type ? 'na' : str_replace(' ', '_', $reportDO->epd_type);
            $do->$colName = $reportDO->count;

            $do->total += $do->$colName;
        }

        $typeUl = $container->appendChild($this->getUl(['class' => 'category']));
        $typeLi = $typeUl->appendChild($this->getLi(['class' => 'section clearfix']));
        $typeLi->appendChild($this->getH1(t('Verteilung EPD Typen über eingesetzte Datensätze')));

        $this->addClass($typeLi, 'last');

        $table = new HtmlTable('report report-effects report-epd-types');
        $table->addColumn('lifeCyclePhase', t('Phase'));
        $table->addColumn('lifeCycleIdent', t('EPD Modul'));

        foreach ($columns as $epdType => $caption) {
            $table->addColumn($epdType, t($caption));
        }

        $head = $table->createTableHead();
        $headRow = $head->addTableRow(new HtmlTableHeadRow());
        $headRow->addClass('table-headlines');

        $body = $table->createTableBody();
        $row = $body->addTableRow();

        foreach ($columns as $epdType => $caption) {
            $row->getColumn($epdType)->setOutputElement(
                new ElcaHtmlNumericText($epdType)
            );
        }

        $row->getColumn('lifeCyclePhase')->setOutputElement(new ElcaHtmlEpdTypeFormatter('lifeCyclePhase', new ElcaTranslatorConverter()));

        $body->setDataSet($dataSet);
        $tables = $typeLi->appendChild($this->getDiv(['class' => 'tables']));
        $table->appendTo($tables);
    }
    // End appendEffect
}
