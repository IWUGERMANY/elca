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
use Beibob\Blibs\Exception;

/**
 * Osit item
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaOsitItem
{
    /**
     * The url of the item with arguments
     */
    private $url;

    /**
     * The items caption
     */
    private $caption;

    /**
     * Context
     */
    private $context;

    /**
     * Additional css class
     */
    private $cssClass;

    /**
     * No page link
     */
    private $noPageLink = false;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructor
     *
     * @param  string $caption
     * @param  string $url
     * @param  bool $isActive
     */
    public function __construct($caption, $url = null, $context = null, $cssClass = null, $noPageLink = false)
    {
        $this->caption = $caption;
        $this->url = $url;
        $this->context = $context;
        $this->cssClass = $cssClass;
        $this->noPageLink = $noPageLink;
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the Url
     *
     * @param  -
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
    // End getUrl

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
     * Returns the context
     *
     * @param  -
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }
    // End getContext

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the cssClass
     *
     * @param  -
     * @return string
     */
    public function getCssClass()
    {
        return $this->cssClass;
    }
    // End getCssClass

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the no page link flag
     *
     * @param  -
     * @return boolean
     */
    public function hasNoPageLink()
    {
        return $this->noPageLink;
    }
    // End hasNoPageLink

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaOsitItem
