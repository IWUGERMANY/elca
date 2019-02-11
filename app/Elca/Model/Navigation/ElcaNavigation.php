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

use Beibob\Blibs\FrontController;
use Elca\Model\Navigation\ElcaNavItem;

/**
 * Root Navigation item
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaNavigation extends ElcaNavItem
{
    /**
     * Named singleton instances
     */
    private static $instances = [];

    /**
     * Active controller and invoked action
     */
    public $activeCtrlName;
    public $activeAction;
    public $activeArgs;

    /**
     * active nav item
     */
    private $ActiveItem;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns and inits the singelton
     *
     * @param string $name
     * @param null $activeCtrlName
     * @param null $activeAction
     * @return ElcaNavigation
     */
    public static function getInstance($name = 'default', $activeCtrlName = null, $activeAction = null, $activeArgs = null)
    {
        if(isset(self::$instances[$name]) && self::$instances[$name] instanceOf ElcaNavigation)
            return self::$instances[$name];

        return self::$instances[$name] = new ElcaNavigation($name, $activeCtrlName, $activeAction, $activeArgs);
    }
    // End getInstance

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the active item
     *
     * @return ElcaNavItem
     */
    public function getActiveItem()
    {
        if(!isset($this->ActiveItem))
        {
            $items = $this->getChildren();

            while($Item = array_shift($items))
            {
                if($Item->isActive())
                {
                    $this->ActiveItem = $Item;

                    if(!$Item->hasChildren())
                        break;

                    $items = $Item->getChildren();
                }
            }
        }

        return $this->ActiveItem;
    }
    // End getActiveItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns an item by id
     *
     * @param  int $id
     * @return ElcaNavItem
     */
    public function getItemById($id)
    {
        return $this->getChildById($id);
    }
    // End getItemById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the active controller name and action by argument or from FrontController
     *
     * @param null $ctrlName
     * @param null $action
     * @param bool $resetActiveItem
     * @return void -
     */
    public function setActiveController($ctrlName = null, $action = null, $args = null, $resetActiveItem = false)
    {
        $FrontController = FrontController::getInstance();

        if($ctrlName)
        {
            $this->activeCtrlName = $ctrlName;
            $this->activeAction   = $action;
            $this->activeArgs     = $args;
        }
        else
        {
            $this->activeCtrlName = $FrontController->getActionControllerName();
            $this->activeAction   = $FrontController->getAction();
        }

        if($resetActiveItem)
        {
            $this->ActiveItem = null;
            $this->getActiveItem();
        }
    }
    // End setCurrentController

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the active controller and action
     *
     * @param  -
     * @return array
     */
    public function getActiveController()
    {
        return [$this->activeCtrlName, $this->activeAction, $this->activeArgs];
    }
    // End getActiveController

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructor
     *
     * @param $name
     * @param null $ctrlName
     * @param null $action
     * @return ElcaNavigation
     */
    protected function __construct($name, $ctrlName = null, $action = null, $args = null)
    {
        parent::__construct($name);
        $this->RootItem = $this;
        $this->setActiveController($ctrlName, $action, $args);
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

}
// End ElcaNavigation
