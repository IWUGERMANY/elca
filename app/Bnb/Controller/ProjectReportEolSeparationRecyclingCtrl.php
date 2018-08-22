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
namespace Bnb\Controller;

use Beibob\Blibs\Session;
use Beibob\Blibs\SessionNamespace;
use Bnb\Model\Processing\BnbProcessor;
use Bnb\View\BnbProjectReportEolSeparationRecyclingView;
use Elca\Controller\BaseReportsCtrl;
use Elca\Controller\ProjectPdfReportsTrait;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaProjectReportsNavigationLeftView;

/**
 * Report controller for BNB 4.1.4
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ProjectReportEolSeparationRecyclingCtrl extends BaseReportsCtrl
{
    /**
     * Session Namespace
     */
    protected $Namespace;

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
        $this->addView(new ElcaProjectReportsNavigationLeftView());

        $this->Namespace = $this->Session->getNamespace('bnb.report-4.1.4', Session::SCOPE_PERSISTENT);
    }
    // End init


    /**
     * construction action
     */
    protected function defaultAction()
    {
        $projectVariantId = $this->Elca->getProjectVariantId();

        if ($this->Request->isPost()) {
            $this->Namespace->calcMethod = $calcMethod = $this->Request->get('calcMethod', BnbProcessor::BNB414_DEFAULT_CALC_METHOD);
        } else {
            $calcMethod = $this->Namespace->calcMethod ? $this->Namespace->calcMethod : BnbProcessor::BNB414_DEFAULT_CALC_METHOD;
        }

        $View = $this->setView(new BnbProjectReportEolSeparationRecyclingView());
        $View->assign('projectVariantId', $projectVariantId);
        $View->assign('calcMethod', $calcMethod);

        $View->assign('data', $this->container->get('bnb.processor')->computeEolSeparationRecycling($projectVariantId, $calcMethod));

        if (!$this->Request->isPost())
            $this->Osit->add(new ElcaOsitItem(t('Rückbau, Trennung und Verwertung (4.1.4)'), null, t('Technische Qualität')));
    }
    // End defaultAction


    /**
     * @return SessionNamespace
     */
    protected function getSessionNamespace()
    {
        return $this->Namespace;
    }
    // End getSessionNamespace
}
// End ProjectReportEolSeparationRecyclingCtrl
