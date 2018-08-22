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
namespace Soda4Lca\Model\Navigation;

use Elca\Model\Navigation\ElcaNavigationInterface;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Model\Navigation\ElcaTabItem;

class Soda4LcaNavigation implements ElcaNavigationInterface
{
    /**
     * Returns a navigation object for the project data navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProjectDataNavigation()  {}
    // End getProjectDataNavigation

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a navigation object for the project reports navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProjectReportNavigation()  {}
    // End getProjectReportNavigation

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a list of ElcaTabItem instances which will be added
     * to the element editor tab context
     *
     * @param  string $context - ElementsCtrl::CONTEXT or ProjectElementsCtrl::CONTEXT
     * @return array
     */
    public function getElementEditorTabs($context, $elementId) {}
    // End getElementEditorTabs

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a navigation object for the admin navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getAdminNavigation()
    {
        $Navigation = ElcaNavigation::getInstance('soda4lca-admin');
        $Item = $Navigation->add(t('ÖKOBAUDAT'));
        $Item->add(t('Datenabgleich'), 'soda4Lca', 'Soda4Lca\Controller\DatabasesCtrl');

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
    // End getProcessesNavigation
}
// End class Soda4LcaNavigation