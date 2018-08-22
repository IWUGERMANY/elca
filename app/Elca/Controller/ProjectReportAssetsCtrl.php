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
use Elca\Db\ElcaIndicatorSet;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaProjectReportsNavigationLeftView;
use Elca\View\Report\ElcaReportAssetsView;
use Elca\Controller\ProjectPdfReportsTrait;

/**
 * Assets report controller
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ProjectReportAssetsCtrl extends BaseReportsCtrl
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
     * @return void -
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

        $this->Namespace = $this->Session->getNamespace('report-assets.filter', Session::SCOPE_PERSISTENT);
    }
    // End init


    /**
     * construction action
     */
    protected function constructionAction()
    {
        $View = $this->setView(new ElcaReportAssetsView());
        $View->assign('buildMode', ElcaReportAssetsView::BUILDMODE_CONSTRUCTIONS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        if (!$this->Request->isPost()) {
            $this->addView(new ElcaProjectReportsNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Gebäudekonstruktion'), null, t('Massenbilanz')));
        }
    }
    // End constructionAction


    /**
     * systems action
     */
    protected function systemsAction()
    {
        $View = $this->setView(new ElcaReportAssetsView());
        $View->assign('buildMode', ElcaReportAssetsView::BUILDMODE_SYSTEMS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        if (!$this->Request->isPost()) {
            $this->addView(new ElcaProjectReportsNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Anlagentechnik'), null, t('Massenbilanz')));
        }
    }
    // End systemsAction


    /**
     * operation action
     */
    protected function operationAction()
    {
        $View = $this->setView(new ElcaReportAssetsView());
        $View->assign('buildMode', ElcaReportAssetsView::BUILDMODE_OPERATION);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        if (!$this->Request->isPost()) {
            $this->addView(new ElcaProjectReportsNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Gebäudebetrieb'), null, t('Massenbilanz')));
        }
    }
    // End operationAction


    /**
     * transports action
     */
    protected function transportsAction()
    {
        $View = $this->setView(new ElcaReportAssetsView());
        $View->assign('buildMode', ElcaReportAssetsView::BUILDMODE_TRANSPORTS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        if (!$this->Request->isPost()) {
            $this->addView(new ElcaProjectReportsNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Transporte'), null, t('Massenbilanz')));
        }
    }
    // End operationAction


    /**
     * optimization action
     */
    protected function topAssetsAction()
    {
        if ($this->Request->isPost()) {
            $FilterDO = new \stdClass();
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

                $FilterDO = (object)['limit'   => 20,
                                     'inTotal' => true,
                                     'order'   => 'DESC'];
            }
        }

        $View = $this->setView(new ElcaReportAssetsView());
        $View->assign('buildMode', ElcaReportAssetsView::BUILDMODE_TOP_ASSETS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $View->assign('FilterDO', $FilterDO);

        if (!$this->Request->isPost()) {
            $this->addView(new ElcaProjectReportsNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Ranking Baustoffe'), null, t('Massenbilanz')));
        }
    }
    // End topAssetsAction


    /**
     * nonDefaultLifeTime action
     */
    protected function nonDefaultLifeTimeAction()
    {
        $View = $this->setView(new ElcaReportAssetsView());
        $View->assign('buildMode', ElcaReportAssetsView::BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS);
        $View->assign('projectVariantId', $this->Elca->getProjectVariantId());

        if (!$this->Request->isPost()) {
            $this->addView(new ElcaProjectReportsNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Eigene Nutzungsdauern'), null, t('Auswertung')));
        }
    }
    // End nonDefaultLifeTimeAction

    /**
     * @return SessionNamespace
     */
    protected function getSessionNamespace()
    {
        return $this->Namespace;
    }
    // End getSessionNamespace
}
// End ElcaReportAssetsCtrl
