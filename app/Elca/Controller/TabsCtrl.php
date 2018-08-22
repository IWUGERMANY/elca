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

use Beibob\Blibs\Url;
use Elca\Model\Navigation\ElcaTabs;
use Elca\Model\Navigation\ElcaTabItem;
use Elca\View\ElcaTabsView;

/**
 * Abstract base class for all Elca application controller that uses tab navigation
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
abstract class TabsCtrl extends AppCtrl
{
    /**
     * Tab navigation
     */
    private static $Tabs;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init tab navigation
         */
        self::$Tabs = ElcaTabs::getInstance();

        if($activeTabIdent = $this->getActiveTabIdent())
            self::$Tabs->setActiveItemByIdent($activeTabIdent);

        if($this->isAjax())
        {
            $View = $this->addView(new ElcaTabsView());

            if($activeTabIdent)
                $View->assign('activeTab', $activeTabIdent);
            $View->assign('context',$this->getContext());
        }
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Will be called on finalization
     *
     * @param  -
     * @return -
     */
    protected function finalize()
    {
        parent::finalize();

        if($this->isAjax() && $this->Request->isGet() && ($activeTabIdent = $this->getActiveTabIdent()))
        {
            $Url = Url::factory(null, $this->Request->getAsArray());
            $Url->removeParameter('_isBaseReq');
            $Url->addParameter(['tab' => $activeTabIdent]);
            $this->updateHashUrl((string)$Url, true, true);
        }
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Invokes the actioncontroller of the active tab
     */
    protected function invokeTabActionController()
    {
        if(!$this->isAjax())
            return;

        if(!$activeTabIdent = $this->getActiveTabIdent())
            return;

        $activeTab = $this->getTabItem($activeTabIdent);

        $args = [];
        if ($activeTab)
            $args = $activeTab->getArgs();

        $args['initialAction'] = $this->getAction();
        $this->forward($activeTab->getCtrlName(), $activeTab->getAction(), $activeTab->getModule(), $args);
    }
    // End invokeTabAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a ElcaTabItem instance
     */
    protected function addTabItemInstance(ElcaTabItem $Item)
    {
        self::$Tabs->addItem($Item);
    }
    // End addTabItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a new tab as ElcaTabItem with ident
     */
    protected function addTabItem($ident, $caption = null, $module = null, $ctrlName = null, $action = null, array $args = [])
    {
        self::$Tabs->add($ident, $caption, $module, $ctrlName? $ctrlName : $this->ident(), $action? $action : $this->getAction(), $args);
    }
    // End addTabItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a tab nav item by ident
     */
    protected function getTabItem($ident)
    {
        return self::$Tabs->getItemByIdent($ident);
    }
    // End getTabItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a tab nav item by ident
     */
    protected function getActiveTabIdent()
    {
        if($activeTab = $this->Request->tab)
            return $activeTab;

        if(self::$Tabs->hasItems())
            return self::$Tabs->getFirstItem()->getIdent();
    }
    // End getTabItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the context of the request
     */
    protected function getContext()
    {
        return $this->ident();
    }
    // End getTabItem

    //////////////////////////////////////////////////////////////////////////////////////

}
// End class ElcaTabCtrl