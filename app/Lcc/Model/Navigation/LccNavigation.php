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
namespace Lcc\Model\Navigation;

use Elca\Elca;
use Elca\Model\Navigation\ElcaNavigationInterface;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Model\Navigation\ElcaTabItem;
use Elca\Security\ElcaAccess;
use Lcc\Controller\Admin\BenchmarksCtrl;
use Lcc\Db\LccProjectTotalSet;
use Lcc\LccModule;

/**
 * LccNavigation
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class LccNavigation implements ElcaNavigationInterface
{
    private $access;

    /**
     * LccNavigation constructor.
     *
     * @param ElcaAccess $access
     */
    public function __construct(ElcaAccess $access)
    {
        $this->access = $access;
    }

    /**
     * Returns a navigation object for the project data navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProjectDataNavigation()
    {
        if(Elca::getInstance()->getProjectVariant()->getPhase()->getStep() < 1) {
            return null;
        }

        $Navigation = ElcaNavigation::getInstance('lcc-data');
        $Item = $Navigation->add(t('LCC'));
        $Item->add(t('Vereinfachtes Verfahren'), 'lcc', 'Lcc\Controller\GeneralCtrl');

        if ($this->access->hasAdminPrivileges() || $this->access->hasRole(LccModule::ROLE)) {
            $Item->add(t('Ausführliches Verfahren'), 'lcc', 'Lcc\Controller\DetailedCtrl');
        }

        return $Navigation;
    }
    // End getProjectDataNavigation


    /**
     * Returns a navigation object for the project reports navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProjectReportNavigation()
    {
        if(!LccProjectTotalSet::dbCount(['project_variant_id' => Elca::getInstance()->getProjectVariantId()]))
            return null;

        $Navigation = ElcaNavigation::getInstance('lcc-reports');
        $Item = $Navigation->add(t('Lebenszykluskosten (2.1.1)'));
        $Item->add(t('Gebäudebezogen'), 'lcc', 'Lcc\Controller\ReportsCtrl');
        $Item->add(t('Entwicklung'), 'lcc', 'Lcc\Controller\ReportsCtrl', 'progress');

        return $Navigation;
    }
    // End getProjectReportNavigation


    /**
     * Returns a list of ElcaTabItem instances which will be added
     * to the element editor tab context
     *
     * @param  string $context - ElementsCtrl::CONTEXT or ProjectElementsCtrl::CONTEXT
     * @param         $elementId
     * @return array
     */
    public function getElementEditorTabs($context, $elementId)
    {
        if (false === ($this->access->hasAdminPrivileges() || $this->access->hasRole(LccModule::ROLE))) {
            return [];
        }

        return [
            new ElcaTabItem('lcc', t('LCC'), 'lcc', 'Lcc\Controller\ElementsCtrl', 'default', ['e' => $elementId, 'context' => $context])
        ];
    }
    // End getElementEditorTabs


    /**
     * Returns a navigation object for the admin navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getAdminNavigation()
    {
        $Navigation = ElcaNavigation::getInstance('lcc-admin');
        $Item = $Navigation->add(t('LCC Versionen'));
        $Item->add(t('Einfaches Verfahren'), 'lcc', 'Lcc\Controller\VersionsCtrl', null, ['calcMethod' => LccModule::CALC_METHOD_GENERAL]);
        $Item->add(t('Ausführliches Verfahren'), 'lcc', 'Lcc\Controller\VersionsCtrl', null, ['calcMethod' => LccModule::CALC_METHOD_DETAILED]);

        return $Navigation;
    }

    /**
     * Returns a navigation object for the admin navigation
     * in the benchmark system version editor or an empty array
     *
     * @return ElcaTabItem[]
     */
    public function getAdminBenchmarkVersionTabs($benchmarkVersionId)
    {
        return [
            new ElcaTabItem(
                'lcc',
                t('LCC Punktwerte'),
                'lcc',
                BenchmarksCtrl::class,
                'editVersionLcc',
                ['id' => $benchmarkVersionId]
            ),
            new ElcaTabItem(
                'lcc-groups',
                t('LCC Bewertung'),
                'lcc',
                BenchmarksCtrl::class,
                'editVersionLccGroups',
                ['id' => $benchmarkVersionId]
            ),
        ];
    }

    /**
     * Returns a navigation object for the processes navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProcessesNavigation() { return null; }
}
// End LccNavigation
