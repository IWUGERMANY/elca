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

use Elca\Model\Navigation\ElcaTabItem;

/**
 * Handles a set of tab items
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaTabs
{
    /**
     * Singleton instance
     */
    private static $Instance;

    /**
     * Tabs
     */
    private $tabs = [];

    /**
     * Active item ident
     */
    private $activeItemIdent;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns and inits the singelton
     *
     * @param  -
     * @return ElcaTabs
     */
    public static function getInstance()
    {
        if(self::$Instance instanceOf ElcaTabs)
            return self::$Instance;

        return self::$Instance = new ElcaTabs();
    }
    // End getInstance

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the active item ident
     */
    public function setActiveItemByIdent($ident)
    {
        $this->activeItemIdent = $ident;

        if(isset($this->tabs[$ident]))
            $this->tabs[$ident]->setActive();
    }
    // End setActiveItemByIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a tab item
     *
     * @param  string $ident
     * @param  string $caption
     * @param  string $module
     * @param  string $ctrlName
     * @param  string $action
     */
    public function add($ident, $caption, $module, $ctrlName, $action, array $args = [])
    {
        $this->tabs[$ident] = new ElcaTabItem($ident, $caption, $module, $ctrlName, $action, $args);

        if($this->activeItemIdent && $ident == $this->activeItemIdent)
            $this->tabs[$ident]->setActive();
    }
    // End add

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a tab item
     *
     * @param  string $ident
     * @param  string $caption
     * @param  string $module
     * @param  string $ctrlName
     * @param  string $action
     */
    public function addItem(ElcaTabItem $Item)
    {
        $ident = $Item->getIdent();
        $this->tabs[$ident] = $Item;

        if($this->activeItemIdent && $ident == $this->activeItemIdent)
            $this->tabs[$ident]->setActive();
    }
    // End addItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns true if any tabs were added
     *
     * @param
     * @return boolean
     */
    public function hasItems()
    {
        return (bool)count($this->tabs);
    }
    // End hasItems

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns all items
     *
     * @param
     * @return array
     */
    public function getItems()
    {
        return $this->tabs;
    }
    // End getItems

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the first item
     *
     * @param
     * @return \Elca\Model\Navigation\ElcaTabItem
     */
    public function getFirstItem()
    {
        return count($this->tabs)? reset($this->tabs) : null;
    }
    // End getFirstItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a tab item by its ident
     *
     * @param  string $Ident
     * @return ElcaTabItem
     */
    public function getItemByIdent($ident)
    {
        return isset($this->tabs[$ident])? $this->tabs[$ident] : null;
    }
    // End getItemByIdent

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaTabs
