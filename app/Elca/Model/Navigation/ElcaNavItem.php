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

/**
 * This file is part of the eLCA project
 *
 * eLCA
 *
 * Copyright (c) 2010-2011 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 * Licensed under Creative Commons license CC BY-NC 3.0
 * http://creativecommons.org/licenses/by-nc/3.0/de/
 */
use Beibob\Blibs\FrontController;
use Elca\Model\Navigation;

/**
 * Single navigation item
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaNavItem
{
    /**
     * Properties
     */
    private $id;
    private $caption;
    private $module;
    private $ctrlName;
    private $action;
    private $args;
    private $data = [];

    private $children;
    private $isActive;
    private $isAccessible;
    private $ParentItem;
    private $level;
    protected $RootItem;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a new child NavItem
     *
     * @param string $caption
     * @param null  $module
     * @param null  $ctrlName
     * @param null  $action
     * @param array $args
     * @param array $data
     * @return ElcaNavItem
     */
    public function add($caption, $module = null, $ctrlName = null, $action = null, array $args = null, array $data = [])
    {
        return $this->children[] = new ElcaNavItem($caption, $module, $ctrlName, $action, $args, $data, $this, $this->RootItem);
    }
    // End addChild

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the id
     *
     * @param  -
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    // End getId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the caption
     *
     * @param  -
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }
    // End getCaption

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the caption
     *
     * @param  string $caption
     * @return string
     */
    public function setCaption($caption)
    {
        return $this->caption = $caption;
    }
    // End setCaption

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the controller name
     *
     * @param  -
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }
    // End getModule

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the controller name
     *
     * @param  -
     * @return string
     */
    public function getCtrlName()
    {
        return $this->ctrlName;
    }
    // End getCtrlName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the action
     *
     * @param  -
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
    // End getAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the arguments
     *
     * @param  -
     * @return array
     */
    public function getArgs($key = null)
    {
        if(is_null($key))
            return $this->args;

        return $this->args[$key];
    }
    // End getArgs

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns all data or a single value
     *
     * @param  string $key
     * @return mixed
     */
    public function getData($key = null)
    {
        if(null === $key)
            return $this->data;

        if(!isset($this->data[$key]))
            return null;

        return $this->data[$key];
    }

    public function setData(array $data = null)
    {
        $this->data = $data;
    }

    public function setDataValue($key, $value = null)
    {
        $this->data[$key] = $value;
    }

    public function hasData()
    {
        return !empty($this->data);
    }

    /**
     * Returns the level
     *
     * @param  -
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }
    // End getId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks if children exists
     *
     * @param  -
     * @return boolean
     */
    public function hasChildren()
    {
        return count($this->children) > 0;
    }
    // End hasChildren

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the children: array of more NavItems
     *
     * @param  -
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }
    // End getChildren

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the first child
     *
     * @param  -
     * @return ElcaNavItem
     */
    public function getFirstChild()
    {
        return isset($this->children[0])? $this->children[0] : null;
    }
    // End getChildren

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the parent item
     *
     * @param  -
     * @return ElcaNavItem
     */
    public function getParentItem()
    {
        return $this->ParentItem;
    }
    // End getParent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns all parent items
     *
     * @param  -
     * @return array
     */
    public function getParentItems()
    {
        $parents = [];

        $Item = $this;
        while($Item = $Item->getParentItem())
            $parents[] = $Item;

        return $parents;
    }
    // End getParent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns an child item by id
     *
     * @param  string $id
     * @return ElcaNavItem
     */
    public function getChildById($id)
    {
        foreach($this->getChildren() as $Item)
        {
            if($Item->getId() == $id)
                return $Item;

            if($ChildItem = $Item->getChildById($id))
                return $ChildItem;
        }

        return null;
    }
    // End getItemById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets this item as active
     *
     * @param  -
     * @return -
     */
    public function setActive()
    {
        $this->isActive = true;

        /**
         * Set active recursive
         */
        if($this->ParentItem)
            $this->ParentItem->setActive();
    }
    // End setActive

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks if the current item is active
     *
     * @param  -
     * @return boolean
     */
    public function isActive()
    {
        if(!isset($this->isActive))
        {
            foreach($this->getChildren() as $Child)
            {
                if($Child->isActive())
                    return $this->isActive = true;
            }

            list($activeCtrlName, $activeAction) = $this->RootItem->getActiveController();

            if($activeCtrlName === $this->ctrlName &&
               (($activeAction == $this->action) || (($this->ctrlName == 'Elca\Controller\ProjectsCtrl') && is_numeric($activeAction))))
            {
                if(is_array($this->args))
                {
                    $Request = FrontController::getInstance()->getRequest();

                    foreach($this->args as $key => $value)
                        if(!$Request->has($key) || $Request->$key != $value)
                            return $this->isActive = false;

                    return $this->isActive = true;
                }
                else
                    return $this->isActive = true;
            }
        }
        return $this->isActive;
    }
    // End hasChildren

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns true if the current user has permissions to access this item
     *
     * @param  -
     * @return boolean
     */
    public function isAccessible()
    {
        return $this->isAccessible;
    }
    // End hasChildren

    /**
     * Magic method toString
     */
    public function __toString()
    {
        return $this->caption;
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Generates a id for the given arguments
     */
    public static function buildId($ctrlName = null, $action = null, array $args = null, $fallback = null)
    {
        $identStr = $ctrlName . $action;

        if($args)
            $identStr .= join('', $args);

        if(!$identStr)
            $identStr = $fallback;

        return md5($identStr);
    }
    // End buildId

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructor
     */
    protected function __construct($caption, $module = null, $ctrlName = null, $action = null, array $args = null, array $data = [], ElcaNavItem $ParentItem = null, Navigation\ElcaNavigation $RootItem = null)
    {
        $this->id = self::buildId($ctrlName, $action, $args, $caption);
        $this->caption  = $caption;
        $this->ctrlName = $ctrlName;
        $this->action = $action;
        $this->args = $args;
        $this->data = $data;
        $this->module = $module;
        $this->ParentItem = $ParentItem;
        $this->RootItem = $RootItem;
        $this->level = $ParentItem? $ParentItem->getLevel() + 1 : 0;
        $this->children = [];

        /**
         * @todo: check accessibility
         */
        $this->isAccessible = true;
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

}
// End ElcaNavItem
