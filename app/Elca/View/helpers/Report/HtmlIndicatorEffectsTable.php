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

namespace Elca\View\helpers\Report;

use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Report\IndicatorEffect;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaTranslatorConverter;

class HtmlIndicatorEffectsTable extends HtmlTable
{
    /**
     * @var int
     */
    private $precision;

    /**
     * @var bool
     */
    private $inScientificNotation;

    /**
     * @var IndicatorEffect[]
     */
    private $indicatorEffects;

    /**
     * @var LifeCycleUsages
     */
    private $lifeCycleUsages;

    /**
     * @var bool
     */
    private $isEn15804Compliant;
    
    /**
     * @var array
     */
    private $lifeCycleReal;

    /**
     * @param                   $tableName
     * @param IndicatorEffect[] $indicatorEffects
     * @param LifeCycleUsages   $lifeCycleUsages
     * @param int               $precision
     * @param bool              $inScientificNotation
     */
    public function __construct($tableName, array $indicatorEffects, LifeCycleUsages $lifeCycleUsages, array $lifeCycleReal = [], $isEn15804Compliant = true, $precision = 10, $inScientificNotation = true)
    {
        parent::__construct(trim('report report-effects '.$tableName));

        $this->precision            = $precision;
        $this->inScientificNotation = $inScientificNotation;
        $this->indicatorEffects     = $indicatorEffects;
        $this->lifeCycleUsages      = $lifeCycleUsages;
        $this->isEn15804Compliant = $isEn15804Compliant;
        $this->lifeCycleReal = $lifeCycleReal;
    }

    /**
     * @param \DOMNode $node
     * @return \DOMNode|void
     * @internal param array $dataSet
     */
    public function appendTo(\DOMNode $node)
    {
        $this->addColumn('name', t('Indikator'));
        $this->addColumn('unit', t('Einheit'));

        $phaseColumns = $this->getPhaseColumns();

        $eolIdents = array_keys($this->lifeCycleUsages->modulesAppliedInEol());

        foreach ($phaseColumns as $phase => $caption) {
            $column = $this->addColumn($phase, $caption);

            if ($phase === ElcaLifeCycle::PHASE_TOTAL) {
                $column->addClass('total');
            }

            if ($phase === ElcaLifeCycle::PHASE_EOL) {
                $column->addClass(implode(' ', $eolIdents));
            }
        }

        $head    = $this->createTableHead();
        $headRow = $head->addTableRow(new HtmlTableHeadRow());
        $headRow->addClass('table-headlines');

        $body = $this->createTableBody();
        $row  = $body->addTableRow();

        foreach ($phaseColumns as $phase => $caption) {
            /**
             * Add m2 Sub to headline
             */
            $span = $headRow->getColumn($phase)->setOutputElement(new HtmlTag('span', $caption . ' / m²'));
            $span->add(new HtmlTag('sub', t('NGF')));
            $span->add(new HtmlStaticText('a'));

            $row->getColumn($phase)
                ->setOutputElement(
                    new ElcaHtmlNumericText(
                        $phase,
                        $this->precision,
                        false,
                        '?',
                        null,
                        null,
                        $this->inScientificNotation
                    )
                );
        }

        $row->getColumn('name')->setOutputElement(new HtmlText('name', new ElcaTranslatorConverter()));
        $row->getColumn('unit')->setOutputElement(new HtmlText('unit', new ElcaTranslatorConverter()));

        $body->setDataSet($this->indicatorEffects);

        $footer = $this->createTableFoot();
        $footerRow = $footer->addTableRow();
        $firstColumn = $footerRow->getColumn('name');
        $firstColumn->setColSpan(2 + count($phaseColumns));
        
        // show lifecycle real values only - 2020-08-14    
        $lifeCycleTotalInclusive ="";

        if(is_array($this->lifeCycleReal) && count($this->lifeCycleReal)>0) {
            sort($this->lifeCycleReal);
            $lifeCycleTotalInclusive = implode(', ', $this->lifeCycleReal);
            
        } else {
            $lifeCycleTotalInclusive = $this->getTotalLifeCycleIdents();
        }
        
        $firstColumn->setOutputElement(
                new HtmlStaticText(
                t('Gesamt inkl.') .' '. $lifeCycleTotalInclusive .'; '.
                t('Instandhaltung inkl.') .' '. $this->getMaintenanceLifeCycleIdents()
            )
        );    
        // show lifecycle real values only - 2020-08-14
        
        return parent::appendTo($node);
    }

    private static $columns = [
        ElcaLifeCycle::PHASE_PROD  => 'Herstellung',
        ElcaLifeCycle::PHASE_EOL   => 'Entsorgung',
        ElcaLifeCycle::PHASE_MAINT => 'Instandhaltung',
        ElcaLifeCycle::PHASE_REC   => 'Rec.potential',
        ElcaLifeCycle::PHASE_TOTAL => 'Gesamt',
    ];

    /**
     * @return string[]
     */
    private function getPhaseColumns()
    {
        $columns = [];

        $phasesUsedInTotal = \array_flip(\array_keys($this->lifeCycleUsages->stagesAppliedInTotal()));
        $remainingPhases = [];

        foreach (self::$columns as $phase => $caption) {
            if (isset($phasesUsedInTotal[$phase])) {
                $columns[$phase] = t($caption);
            } else {
                $remainingPhases[$phase] = $caption;
            }
        }

        $columns[ElcaLifeCycle::PHASE_TOTAL] = t(self::$columns[ElcaLifeCycle::PHASE_TOTAL]);

        foreach ($remainingPhases as $phase => $caption) {
            $columns[$phase] = t($caption);
        }

        if (isset($columns[ElcaLifeCycle::PHASE_EOL])) {
            $eolCaptions = array_keys($this->lifeCycleUsages->modulesAppliedInEol());

            /**
             * If lc ident is identical to the phase name it is not en15804 compliant
             */
            if (count($eolCaptions) === 1 && current($eolCaptions) !== ElcaLifeCycle::PHASE_EOL) {
                $columns[ElcaLifeCycle::PHASE_EOL] = current($eolCaptions);
            }
        }

        /**
         * @var IndicatorEffect $indicator
         */
        $indicator = current($this->indicatorEffects);

        if (!$indicator->indicator()->isEn15804Compliant()) {
            unset($columns[ElcaLifeCycle::PHASE_REC]);
        }

        return $columns;
    }
    // End appendEffect

    /**
     * @return string
     */
    protected function getTotalLifeCycleIdents()
    {
        $parts = [];
        
        foreach ($this->lifeCycleUsages->modulesAppliedInTotal() as $module) {
            $parts[$module->value()] = t($module->name());
        }
        $parts = $this->cleanupLifeCycleIdents($parts);

        sort($parts);
        return implode(', ', $parts);
    }

    /**
     * @return string
     */
    protected function getMaintenanceLifeCycleIdents()
    {
        $parts = [];
        foreach ($this->lifeCycleUsages->modulesAppliedInMaintenance() as $module) {
            $parts[$module->value()] = t($module->name());
        }

        $parts = $this->cleanupLifeCycleIdents($parts);

        sort($parts);
        return implode(', ', $parts);
    }

    /**
     * @param array $parts
     * @return array
     */
    private function cleanupLifeCycleIdents(array $parts)
    {
        if (isset($parts[ElcaLifeCycle::IDENT_A13])) {
            unset(
                $parts[ElcaLifeCycle::IDENT_A1],
                $parts[ElcaLifeCycle::IDENT_A2],
                $parts[ElcaLifeCycle::IDENT_A3]
            );
        }

        return $parts;
    }

}
