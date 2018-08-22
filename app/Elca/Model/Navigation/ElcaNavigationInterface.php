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
namespace Elca\Model\Navigation;

use Elca\Model\Navigation\ElcaNavigation;
use Elca\Model\Navigation\ElcaTabItem;

/**
 * Navigation interface
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.de>
 * @copyright  BEIBOB
 */
interface ElcaNavigationInterface
{
    /**
     * Returns a navigation object for the project data navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProjectDataNavigation();

    /**
     * Returns a navigation object for the project reports navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProjectReportNavigation();

    /**
     * Returns a list of ElcaTabItem instances which will be added
     * to the element editor tab navigation
     *
     * @param  string $context - ElementsCtrl::CONTEXT or ProjectElementsCtrl::CONTEXT
     * @param         $elementId
     * @return ElcaTabItem[]
     */
    public function getElementEditorTabs($context, $elementId);

    /**
     * Returns a navigation object for the admin navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getAdminNavigation();

    /**
     * Returns a navigation object for the admin navigation
     * in the benchmark system version editor or an empty array
     *
     * @return ElcaTabItem[]
     */
    public function getAdminBenchmarkVersionTabs($benchmarkVersionId);

    /**
     * Returns a navigation object for the processes navigation
     * or null if nothing to contribute
     *
     * @return ElcaNavigation
     */
    public function getProcessesNavigation();
}
// End ElcaNavigationInterface