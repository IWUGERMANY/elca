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

namespace Elca\Model\Assistant;

/**
 * ElementAssistantConfiguration
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Configuration
{
    private $dinCodes;
    private $ident;
    private $caption;
    private $controller;
    private $controllerAction;
    /**
     * @var array
     */
    private $lockedProperties;
    /**
     * @var array
     */
    private $lockedFunctions;

    /**
     * @param array  $dinCodes
     * @param string $ident
     * @param string $caption
     * @param string $controller
     * @param string $controllerAction
     * @param array  $lockedProperties
     * @param array  $lockedFunctions
     */
    public function __construct(array $dinCodes, $ident, $caption, $controller, $controllerAction,
        array $lockedProperties, array $lockedFunctions)
    {
        $this->dinCodes         = $dinCodes;
        $this->ident            = $ident;
        $this->caption          = $caption;
        $this->controller       = $controller;
        $this->controllerAction = $controllerAction;
        $this->lockedProperties = $lockedProperties;
        $this->lockedFunctions = $lockedFunctions;
    }

    /**
     * @return array
     */
    public function getDinCodes()
    {
        return $this->dinCodes;
    }

    /**
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }

    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getControllerAction()
    {
        return $this->controllerAction;
    }

    /**
     * @return array
     */
    public function getLockedProperties()
    {
        return $this->lockedProperties;
    }

    /**
     * @return array
     */
    public function getLockedFunctions()
    {
        return $this->lockedFunctions;
    }
}
