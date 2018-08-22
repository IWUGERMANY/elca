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
use Beibob\Blibs\StringFactory;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaReportSet;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Model\Process\Module;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\View\ElcaProjectReportsNavigationLeftView;
use Elca\View\Report\ElcaReportEffectDetailsView;
use Elca\View\Report\ElcaReportEffectsView;

/**
 * Assets report controller
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ProjectReportEffectsCtrl extends BaseReportsCtrl
{
    /**
     * Session Namespace
     */
    private $Namespace;


    /**
     * Will be called on initialization.
     *
     * @param  array $args
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess())
            return;

        $this->Osit->clear();

        $this->Namespace = $this->Session->getNamespace('report-effects.filter', Session::SCOPE_PERSISTENT);
    }
    // End init


    /**
     * construction action
     */
    protected function constructionAction()
    {
        $View = $this->setView(new ElcaReportEffectsView());
        $View->assign('buildMode', ElcaReportEffectsView::BUILDMODE_CONSTRUCTIONS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $View->assign('FilterDO', $this->getChartConfig());

        if (!$this->Request->isPost()) {
            $this->Osit->add(new ElcaOsitItem(t('Gebäudekonstruktion'), null, t('Wirkungsabschätzung')));
            $this->addView(new ElcaProjectReportsNavigationLeftView());
        }
    }
    // End constructionAction


    /**
     * systems action
     */
    protected function systemsAction()
    {
        $View = $this->setView(new ElcaReportEffectsView());
        $View->assign('buildMode', ElcaReportEffectsView::BUILDMODE_SYSTEMS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $View->assign('FilterDO', $this->getChartConfig());

        $this->Osit->add(new ElcaOsitItem(t('Anlagentechnik'), null, t('Wirkungsabschätzung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());
    }
    // End systemsAction


    /**
     * operation action
     */
    protected function operationAction()
    {
        $View = $this->setView(new ElcaReportEffectsView());
        $View->assign('buildMode', ElcaReportEffectsView::BUILDMODE_OPERATION);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        $this->Osit->add(new ElcaOsitItem(t('Gebäudebetrieb'), null, t('Wirkungsabschätzung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());
    }
    // End operationAction


    /**
     * transports action
     */
    protected function transportsAction()
    {
        $View = $this->setView(new ElcaReportEffectsView());
        $View->assign('buildMode', ElcaReportEffectsView::BUILDMODE_TRANSPORTS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        $this->Osit->add(new ElcaOsitItem(t('Transporte'), null, t('Wirkungsabschätzung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());
    }
    // End operationAction


    /**
     * optimization action
     */
    protected function topElementsAction()
    {
        if ($this->Request->isPost()) {
            $FilterDO = new \stdClass();
            $FilterDO->indicatorId = $this->Request->getNumeric('indicatorId');
            $FilterDO->limit = $this->Request->getNumeric('limit');
            $FilterDO->inTotal = (bool)$this->Request->getNumeric('inTotal');

            if (in_array(\utf8_strtoupper($this->Request->get('order')), ['ASC', 'DESC']))
                $FilterDO->order = \utf8_strtoupper($this->Request->get('order'));
            else
                $FilterDO->order = 'DESC';

            $this->Namespace->Filter = $FilterDO;
        } else {
            if (isset($this->Namespace->Filter))
                $FilterDO = $this->Namespace->Filter;
            else {
                $Indicators = ElcaIndicatorSet::findByProcessDbId($this->Elca->getProject()->getProcessDbId(), false, false, ['p_order' => 'ASC'], 1);
                $Indicator = $Indicators[0];

                $FilterDO = (object)['indicatorId' => $Indicator->getId(),
                                     'limit'       => 20,
                                     'inTotal'     => true,
                                     'order'       => 'DESC'];
            }
        }

        $View = $this->setView(new ElcaReportEffectsView());
        $View->assign('buildMode', ElcaReportEffectsView::BUILDMODE_TOP_ELEMENTS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $View->assign('FilterDO', $FilterDO);

        if (!$this->Request->isPost()) {
            $this->Osit->add(new ElcaOsitItem(t('Ranking Bauteile'), null, t('Wirkungsabschätzung')));
            $this->addView(new ElcaProjectReportsNavigationLeftView());
        }
    }
    // End topElementsAction


    /**
     * optimization action
     */
    protected function topProcessesAction()
    {
        if ($this->Request->isPost()) {
            $FilterDO = new \stdClass();
            $FilterDO->indicatorId = $this->Request->getNumeric('indicatorId');
            $FilterDO->limit = $this->Request->getNumeric('limit');
            $FilterDO->inTotal = (bool)$this->Request->getNumeric('inTotal');

            if (in_array(\utf8_strtoupper($this->Request->get('order')), ['ASC', 'DESC']))
                $FilterDO->order = \utf8_strtoupper($this->Request->get('order'));
            else
                $FilterDO->order = 'DESC';

            $this->Namespace->Filter = $FilterDO;
        } else {
            if (isset($this->Namespace->Filter))
                $FilterDO = $this->Namespace->Filter;
            else {
                $Indicators = ElcaIndicatorSet::findByProcessDbId($this->Elca->getProject()->getProcessDbId(), false, false,  ['p_order' => 'ASC'], 1);
                $Indicator = $Indicators[0];

                $FilterDO = (object)['indicatorId' => $Indicator->getId(),
                                     'limit'       => 20,
                                     'inTotal'     => true,
                                     'order'       => 'DESC'];
            }
        }

        $View = $this->setView(new ElcaReportEffectsView());
        $View->assign('buildMode', ElcaReportEffectsView::BUILDMODE_TOP_PROCESSES);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $View->assign('FilterDO', $FilterDO);

        if (!$this->Request->isPost()) {
            $this->Osit->add(new ElcaOsitItem(t('Ranking Baustoffe'), null, t('Wirkungsabschätzung')));
            $this->addView(new ElcaProjectReportsNavigationLeftView());
        }
    }
    // End topElementsAction


    /**
     * element details action
     */
    protected function elementDetailsAction()
    {
        if (!$this->Request->has('e') || !$this->Request->get('e'))
            return;

        $View = $this->setView(new ElcaReportEffectDetailsView());
        $View->assign('elementId', $this->Request->e);
        $View->assign('aggregated', $this->Request->get('a', false));
        $View->assign('m2a', $this->Request->get('m2a', 1));
        $View->assign('addPhaseRec', $this->Request->get('rec', $this->Elca->getProject()->getProcessDb()->isEn15804Compliant()));
    }
    // End elementDetailsAction


    /**
     * Element type chart data
     */
    protected function elementChartAction()
    {
        if (!$this->isAjax() || !$this->Request->e || !$this->Request->i)
            return;

        $Element = ElcaElement::findById($this->Request->e);
        $ProjectVariant = $Element->getProjectVariant();
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($ProjectVariant->getId());

        $lifeCycleUsages = $this->container
            ->get(LifeCycleUsageService::class)
            ->findLifeCycleUsagesForProject(new ProjectId($ProjectVariant->getProjectId()));

        if (!$indicatorId = $this->Request->i) {
            $Indicators = ElcaIndicatorSet::findByProcessDbId($ProjectVariant->getProject()->getProcessDbId(), false, false, ['p_order' => 'ASC'], 1);
            $Indicator = $Indicators[0];
        } else
            $Indicator = ElcaIndicator::findById($indicatorId);

        $aggregated = (bool)$this->Request->get('a');

        /**
         * Bar config
         */
        $Conf = new \stdClass();
        $Conf->width = 600;
        $Conf->height = 300;

        $Conf->margin = (object)['bottom' => 90];
        $Conf->yAxis = (object)['caption' => t($Indicator->getName()), 'wordingIn' => t('in'), 'refUnit' => t($Indicator->getUnit())];


        /**
         * All values per m2 and year
         */
        $m2a = max(1, $ProjectVariant->getProject()->getLifeTime() * $ProjectConstruction->getNetFloorSpace());

        /**
         * Calculate benchmark
         */
        $reports = [];

        if ($isComposite = $Element->isComposite())
            $ReportSet = ElcaReportSet::findCompositeElementProcessConfigEffects($Element->getId(), $indicatorId, $aggregated);
        else
            $ReportSet = ElcaReportSet::findElementProcessConfigEffects($Element->getId(), $indicatorId, $aggregated);

        foreach ($ReportSet as $index => $Report) {
            if ($Report->life_cycle_phase !== ElcaLifeCycle::PHASE_REC &&
                !$lifeCycleUsages->moduleIsAppliedInConstruction(new Module($Report->life_cycle_ident))) {
                continue;
            }

            if (Module::fromValue($Report->life_cycle_ident)->isA1A2OrA3()) {
                continue;
            }

            $category = $info = '';

            if (isset($Report->is_layer) && $Report->is_layer)
                $category = $info = $Report->layer_position . '.';

            $category .= $key = $Report->process_config_name;
            $key = $aggregated ? $Report->process_config_name : $Report->element_component_id;

            $info .= t($Report->life_cycle_name) . "\n\"" . $Report->process_config_name . "\"";

            if (!$aggregated && $isComposite)
                $category = '[' . $Report->element_id . '] ' . $category;

            if (!isset($reports[$key]))
                $DO = $reports[$key] = (object)['name'     => StringFactory::stringCut($category, 20, '..'),
                                                'info'     => $category,
                                                'cssClass' => !$aggregated && $Report->is_extant ? 'is-extant' : '',
                                                'values'   => []];
            else
                $DO = $reports[$key];

            $DO->values[] = (object)['name'  => $info,
                                     'value' => $Report->indicator_value / max(1, $m2a),
                                     'fill'  => ProjectReportsCtrl::$lcPhaseColors[$Report->life_cycle_phase]
            ];
        }

        $this->getView()->assign('data', array_values($reports));
        $this->getView()->assign('config', $Conf);
    }
    // End elementChartAction


    /**
     * Returns the chart config
     *
     * @return object|\StdClass -
     */
    private function getChartConfig()
    {
        if ($this->Request->isPost()) {
            $FilterDO = new \stdClass();
            $FilterDO->indicatorId = $this->Request->getNumeric('indicatorId');
            $FilterDO->aggregated = $this->Request->aggregated;

            $this->Namespace->ChartConfig = $FilterDO;
        } else {
            if (isset($this->Namespace->ChartConfig))
                $FilterDO = $this->Namespace->ChartConfig;
            else {
                $Indicators = ElcaIndicatorSet::findByProcessDbId($this->Elca->getProject()->getProcessDbId(), false, false, ['p_order' => 'ASC'], 1);
                $Indicator = $Indicators[0];

                $FilterDO = (object)['indicatorId' => $Indicator->getId(),
                                     'aggregated'  => 0];
            }
        }

        return $FilterDO;
    }
    // End getChartConfig

    /**
     * @return SessionNamespace
     */
    protected function getSessionNamespace()
    {
        return $this->Namespace;
    }
    // End getSessionNamespace
}

// End ElcaReportEffectsCtrl
