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

use Beibob\Blibs\CssLoader;
use Beibob\Blibs\JsLoader;
use Elca\View\ElcaAdminNavigationLeftView;
use Elca\View\ElcaAdminView;

/**
 * Admin section
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class AdminCtrl extends AppCtrl
{
    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->isAjax()) {
            $jsLoader = JsLoader::getInstance();
            $jsLoader->register('DataTables', 'datatables.min.js');
            $jsLoader->register('DataTables', 'Select-1.2.3/js/dataTables.select.min.js');
            $jsLoader->register('selectize', 'sifter.min.js');
            $jsLoader->register('selectize', 'microplugin.min.js');
            $jsLoader->register('selectize', 'selectize.min.js');
            $cssLoader = CssLoader::getInstance();
            $cssLoader->prepend('DataTables', 'datatables.min.css', 'all', '/js');
            $cssLoader->prepend('selectize', 'selectize.css', 'all', '/js');
        }
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////
    /**
     * Default action
     */
    protected function defaultAction()
    {
        if(!$this->isAjax())
            return;

        if(!$this->Access->hasAdminPrivileges())
            return;

        $this->setView(new ElcaAdminView());
        $this->addNavigationView();
    }
    // End defaultAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Helper to add the navigation to the view stack
     */
    private function addNavigationView()
    {
        /**
         * Add left navigation
         */
        if($this->hasViewByName('Elca\View\ElcaAdminNavigationLeftView'))
            return;

        $this->addView(new ElcaAdminNavigationLeftView());
    }
    // End addNavigationView

    //////////////////////////////////////////////////////////////////////////////////////
}
// End AdminCtrl
