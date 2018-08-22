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

/**
 * Tab item
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaTabItem
{
    /**
     * The items ident
     */
    private $ident;

    /**
     * The items caption
     */
    private $caption;

    /**
     * The items module
     */
    private $module;

    /**
     * The items controller
     */
    private $ctrlName;

    /**
     * The items action
     */
    private $action;

    /**
     * The items arguments
     */
    private $args;

    /**
     * Marks the item active
     */
    private $isActive = false;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructor
     *
     * @param  string $ident
     * @param  string $caption
     * @param  string $module
     * @param  string $ctrlName
     * @param  string $action
     */
    public function __construct($ident, $caption, $module, $ctrlName, $action, array $args = [])
    {
        $this->ident = $ident;
        $this->caption = $caption;
        $this->module = $module;
        $this->ctrlName = $ctrlName;
        $this->action = $action;
        $this->args = $args;
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets this tab active
     *
     */
    public function setActive($active = true)
    {
        return $this->isActive = $active;
    }
    // End setActive

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the Ident
     *
     * @param  -
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getIdent

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
     * Returns the module
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
     * Returns the args
     *
     * @param  -
     * @return string
     */
    public function getArgs()
    {
        return $this->args;
    }
    // End getArgs

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaTabItem
