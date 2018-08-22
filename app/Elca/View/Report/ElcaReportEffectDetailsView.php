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
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use DOMElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaReportSet;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorId;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Elca\Model\Report\IndicatorEffect;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\View\helpers\Report\HtmlIndicatorEffectsTable;

/**
 * Builds the report for construction elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaReportEffectDetailsView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_WRAPPER = 'wrapper';
    const BUILDMODE_DEFAULT = 'default';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * @var string $buildMode
     */
    private $buildMode;

    /**
     * @var int $elementId
     */
    private $elementId;

    /**
     * @var float $m2a
     */
    private $m2a;

    /**
     * @var boolean $aggregated
     */
    private $aggregated;

    /** @var  boolean $addPhaseRec */
    private $addPhaseRec;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->elementId = $this->get('elementId');
        $this->aggregated = $this->get('aggregated', false);
        $this->m2a = $this->get('m2a', 1);
        $this->addPhaseRec = $this->get('addPhaseRec');
    }
    // End init

    /**
     * Renders the report details
     */
    protected function afterRender()
    {
        switch($this->buildMode)
        {
            case self::BUILDMODE_WRAPPER:
                $Container = $this->appendChild($this->getDiv(['class' => 'element-details-wrapper']));

                $Url = Url::factory('/project-report-effects/elementDetails/',
                    ['e' => $this->elementId,
                          'm2a' => $this->m2a,
                          'a' => (int)$this->aggregated,
                          'rec' => (int)$this->addPhaseRec
                    ]
                );

                $Container->appendChild($this->getH3(t('Ergebnisse für Baustoffe'), ['data-url' => (string)$Url]));
                $Container->appendChild($this->getDiv(['id' => 'element-details-'. $this->elementId, 'class' => 'element-details']));
                break;

            case self::BUILDMODE_DEFAULT:
                $details = $this->appendChild($this->getDiv(['id' => 'element-details-'. $this->elementId, 'class' => 'element-details']));
                $this->appendDetails($details, $this->elementId, $this->m2a, (bool)$this->aggregated, (bool)$this->addPhaseRec);
                break;
        }
    }
    // End afterRender


    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement $Container
     * @param            $elementId
     * @param            $m2a
     * @param            $aggregated
     * @param bool       $addPhaseRec
     * @internal param \ElcaReportSet $ReportSet
     * @return void -
     */
    private function appendDetails(DOMElement $Container, $elementId, $m2a, $aggregated, $addPhaseRec = false)
    {
        $element = ElcaElement::findById($elementId);

        if($element->isComposite())
            $reportSet = ElcaReportSet::findCompositeElementProcessConfigEffects($elementId, null, $aggregated, true);
        else
            $reportSet = ElcaReportSet::findElementProcessConfigEffects($elementId, null, $aggregated, true);

        if(!$reportSet->count())
            return;

        $project = $element->getProjectVariant()->getProject();
        $lifeCycleUsages = Environment::getInstance()
                                      ->getContainer()
                                      ->get(LifeCycleUsageService::class)
                                      ->findLifeCycleUsagesForProject(new ProjectId($project->getId()));

        $isEn15804Compliant = $project->getProcessDb()->isEn15804Compliant();

        $reports = [];
        foreach ($reportSet as $DO) {
            $name = $DO->process_config_name;

            if (!$aggregated && ($DO->layer_position) && $DO->layer_position)
                $name = $DO->layer_position.'. '. $name;

            if (!$aggregated && isset($DO->composite_element_id) && $DO->composite_element_id)
                $name = '['. $DO->element_id.'] '. $name;

            if ($DO->is_extant) {
                $name .= ' [' . t('Altsubstanz') . ']';
            }
            $reports[$name][$DO->life_cycle_phase][] = $DO;
        }

        $ul = $Container->appendChild($this->getUl());
        foreach ($reports as $processConfigName => $data)
        {
            $li = $ul->appendChild($this->getLi(['class' => 'section clearfix']));
            $li->appendChild($this->getH4($processConfigName));

            $this->appendEffect($li, $data, $m2a, $lifeCycleUsages, $isEn15804Compliant);
        }
    }
    // End buildEffects


    /**
     * Appends a table for one effect
     *
     * @param  DOMElement     $container
     * @param  array          $data
     * @param                 $m2a
     * @param LifeCycleUsages $lifeCycleUsages
     * @param bool            $isEn15804Compliant
     */
    private function appendEffect(DOMElement $container, array $data, $m2a, LifeCycleUsages $lifeCycleUsages, $isEn15804Compliant = false)
    {
        /**
         * Normalize indicators
         */
        $dataSet = [];
        foreach($data as $phase => $indicators)
        {
            foreach($indicators as $do)
            {
                if(!isset($dataSet[$do->indicator_id])) {

                    $dataSet[$do->indicator_id] = $item = new \stdClass();

                    $item->indicator = new Indicator(
                        new IndicatorId($do->indicator_id),
                        $do->indicator_name,
                        new IndicatorIdent($do->indicator_ident),
                        $do->indicator_unit,
                        $isEn15804Compliant
                    );
                    $item->phases = [
                        ElcaLifeCycle::PHASE_PROD  => 0,
                        ElcaLifeCycle::PHASE_MAINT => 0,
                        ElcaLifeCycle::PHASE_EOL   => 0,
                        ElcaLifeCycle::PHASE_REC   => 0,
                        ElcaLifeCycle::PHASE_TOTAL => 0,
                    ];
                }
                else {
                    $item = $dataSet[$do->indicator_id];
                }

                $item->phases[$phase] += $do->indicator_value / $m2a;
            }
        }

        foreach ($dataSet as $index => $item) {
            $dataSet[$index] = new IndicatorEffect(
                $item->indicator,
                $item->phases
            );
        }

        $table = new HtmlIndicatorEffectsTable('element-details', $dataSet, $lifeCycleUsages);
        $table->appendTo($container);
    }
    // End appendEffect

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaReportEffectDetailsView
