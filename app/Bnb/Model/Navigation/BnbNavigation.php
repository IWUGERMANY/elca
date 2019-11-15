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
namespace Bnb\Model\Navigation;

use Bnb\Controller\CsvExportCtrl;
use Bnb\Controller\MaterialExportCtrl;
use Bnb\Controller\ProjectExportCtrl;
use Bnb\Controller\XmlExportCtrl;
use Elca\Controller\ExportsCtrl;
use Elca\Controller\ProjectDataCtrl;
use Elca\Controller\ProjectsCtrl;
use Elca\Elca;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Model\Navigation\ElcaNavigationInterface;
use Elca\Model\Navigation\ElcaTabItem;
use Elca\Security\ElcaAccess;

class BnbNavigation implements ElcaNavigationInterface
{
    /**
     * Returns a navigation object for the project data navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProjectDataNavigation()
    {
        if (Elca::getInstance()->getProjectVariant()->getPhase()->getStep() < 1)
            return;

        $Navigation = ElcaNavigation::getInstance('bnb-data');
        $Item = $Navigation->add(t('Rückbau, Trennung und Verwertung'));
        $Item->add(t('Rückbau, Trennung und Verwertung (4.1.4)'), 'bnb', 'Bnb\Controller\EolSeparationRecyclingCtrl');

        $access = ElcaAccess::getInstance();

        $project = Elca::getInstance()->getProject();
        if ($access->isProjectOwnerOrAdmin($project)) {
            $Item = $Navigation->add(t('Export'));
            $Item->add(t('Projekt'), 'bnb', ProjectExportCtrl::class);
            $Item->add(t('eBNB'), 'bnb', XmlExportCtrl::class);
            $Item->add(t('CSV'), 'bnb', CsvExportCtrl::class);
            $Item->add(t('Stoffstromanalyse'), 'bnb', MaterialExportCtrl::class);
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
        $Navigation = ElcaNavigation::getInstance('bnb-reports');
        $Item = $Navigation->add(t('Rückbau, Trennung und Verwertung'));
        $Item->add(t('Rückbau, Trennung und Verwertung (4.1.4)'), 'bnb', 'Bnb\Controller\ProjectReportEolSeparationRecyclingCtrl');

        return $Navigation;
    }
    // End getProjectReportNavigation


    /**
     * Returns a list of ElcaTabItem instances which will be added
     * to the element editor tab context
     *
     * @param  string $context - ElementsCtrl::CONTEXT or ProjectElementsCtrl::CONTEXT
     *
     * @return array
     */
    public function getElementEditorTabs($context, $elementId)
    {
        return null;
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
        return null;
    }

    /**
     * Returns a navigation object for the admin navigation
     * in the benchmark system version editor or an empty array
     *
     * @return ElcaTabItem[]
     */
    public function getAdminBenchmarkVersionTabs($benchmarkVersionId)
    {
        return [];
    }

    /**
     * Returns a navigation object for the processes navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProcessesNavigation()
    {
        return null;
    }
}
// End class BnbNavigation