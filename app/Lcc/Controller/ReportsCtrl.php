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
namespace Lcc\Controller;

use Beibob\Blibs\SessionNamespace;
use Elca\Controller\BaseReportsCtrl;
use Elca\Db\ElcaProjectVariantAttribute;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaProjectReportsNavigationLeftView;
use Lcc\LccModule;
use Lcc\View\LccReportsView;

/**
 * Reports controller
 *
 * @package lcc
 * @author Tobias Lode <tobias@beibob.de>
 */
class ReportsCtrl extends BaseReportsCtrl
{
    /**
     * @var SessionNamespace
     */
    private $namespace;
    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if(!$this->checkProjectAccess())
            return;

        $this->Osit->clear();

        $this->namespace = $this->getSessionNamespace();
    }
    // End init


    /**
     * Default action
     */
    protected function defaultAction()
    {
        if ($this->Request->has('calcMethod') || !isset($this->namespace->calcMethod)) {
            $this->namespace->calcMethod = $this->Request->has('calcMethod')
                ? $this->Request->get('calcMethod')
                : LccModule::CALC_METHOD_GENERAL;
        }

        $view = $this->setView(new LccReportsView());
        $view->assign('buildMode', LccReportsView::BUILDMODE_SUMMARY);
        $view->assign('calcMethod', $this->namespace->calcMethod);

        if ($this->Request->has('benchmarkVersionId')) {
            $this->namespace->benchmarkVersionId = $this->Request->benchmarkVersionId;
        }

        if ($benchmarkVersionId = $this->Elca->getProject()->getBenchmarkVersionId()) {
            $this->namespace->benchmarkVersionId = $benchmarkVersionId;
        }

        $view->assign('benchmarkVersionId', $this->namespace->benchmarkVersionId ? (int)$this->namespace->benchmarkVersionId : null);

        if ($this->Request->isPost() && $this->Request->has('save')) {
            $comment = \trim($this->Request->comment);

            if (!empty($comment)) {
                ElcaProjectVariantAttribute::updateValue(
                    $this->Elca->getProjectVariantId(),
                    LccModule::ATTRIBUTE_IDENT_LCC_BENCHMARK_COMMENT .'_'. $this->namespace->calcMethod,
                    $comment,
                    true
                );
            }
        }

        $this->Osit->add(new ElcaOsitItem(t('LCC'), null, t('Gebäudebezogen')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());
    }
    // End defaultAction


    /**
     * construction action
     */
    protected function progressAction()
    {
        if ($this->Request->has('calcMethod') || !isset($this->namespace->calcMethod)) {
            $this->namespace->calcMethod = $this->Request->get('calcMethod', LccModule::CALC_METHOD_GENERAL);
        }

        $view = $this->setView(new LccReportsView());
        $view->assign('buildMode', LccReportsView::BUILDMODE_PROGRESSION);
        $view->assign('calcMethod', $this->namespace->calcMethod);

        $this->Osit->add(new ElcaOsitItem(t('LCC'), null, t('Entwicklung')));
        $this->addView(new ElcaProjectReportsNavigationLeftView());
    }


    /**
     * Pie chart data
     */
    protected function pieChartAction()
    {
        if(!$this->isAjax())
            return;

        $reports = [];
        $this->getView()->assign('data', array_values($reports));
    }
    // End pieChartAction


    /**
     * Progress chart data
     */
    protected function progressChartAction()
    {
        if(!$this->isAjax())
            return;

        $reports = [];
        $this->getView()->assign('data', array_values($reports));
    }
    // End progressChartAction


    /**
     * @return SessionNamespace
     */
    protected function getSessionNamespace()
    {
        return $this->Session->getNamespace('lcc.reports', true);
    }
}
// End ReportsCtrl
