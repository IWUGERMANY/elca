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
namespace Elca\View;

use Beibob\Blibs\HtmlView;
use DOMElement;
use Elca\ElcaNumberFormat;

/**
 * Builds a sheet
 *
 * Requirements
 *    headline - the sheet headline
  *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaSheetView extends HtmlView
{
    /**
     * Container element
     */
    private $Container;

    /**
     * Content container element
     */
    private $Content;

    /**
     * Headline
     */
    private $Headline;

    /**
     * Sub headline
     */
    private $SubHeadline;

    /**
     * Functions element
     */
    private $Functions;

    /**
     * Info container
     */
    private $Info;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructs the Document
     *
     * @param  string $xmlName
     * @return -
     */
    public function __construct()
    {
        parent::__construct('elca_sheet', 'elca');
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered after loading the template
     *
     * Note: Basic functionality is initialized here. Template is loaded
     *
     * @param  -
     * @return -
     */
    protected function afterLoading()
    {
        /**
         * Get container
         */
        $this->Container = $this->getElementById('container', true);
        $this->Container->setAttribute('id', 'elca-sheet-'.$this->get('itemId'));

        /**
         * Get content container
         */
        $this->Content = $this->getElementById('content', true);

        /**
         * Get function panel
         */
        $this->Functions = $this->getElementById('functions', true);

        /**
         * Headline
         */
        $this->Headline = $this->getElementById('headline', true);

        if($this->get('hideHeadline', false))
        {
            $this->Headline->parentNode->removeChild($this->Headline);
            $this->Headline = null;
        }

        /**
         * SubHeadline
         */
        $this->SubHeadline = $this->getElementById('subheadline', true);
    }
    // End afterLoading

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a function
     *
     * Assumes a icon with css class <name>
     *
     * @param  array $functions
     * @return -
     */
    protected function addFunction($name, $url, $title = null, $addCssClass = null)
    {
        $aAttr = ['href' => $url,
                       'class' => trim('icon '. $name.' '.$addCssClass),
                       ];

        if($title)
            $aAttr['title'] = $title;

        $this->Functions->appendChild($this->getA($aAttr, $name));
    }
    // End functions

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a description
     *
     * @param  string $text
     * @return -
     */
    protected function addDescription($text, $addEmpty = false)
    {
        if($text || $addEmpty)
        {
            $P = $this->Content->appendChild($this->getP($text));
            if (!$text && $addEmpty)
                $P->appendChild($this->createEntityReference('nbsp'));
        }
    }
    // End addDescription

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a info
     *
     * @param  string $text 
     * @return -
     */
    protected function addInfo($info, $caption = null, $unit = null, $newLine = false)
    {
        $this->Info = $this->getInfo();

        $Li = $this->Info->appendChild($this->getLi());

        if($newLine)
            $Li->setAttribute('class', 'clear');

        if($caption)
            $Li->appendChild($this->getText($caption . utf8_encode(chr(160))));

        $Li->appendChild($this->getStrong($info));

        if($unit)
        {
            $Li->appendChild($this->getText(utf8_encode(chr(160))));
            $Li->appendChild($this->getStrong(ElcaNumberFormat::formatUnit($unit)));
        }

        return $Li;
    }
    // End addInfo

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the headline element
     *
     * @param  -
     * @return DOMElement
     */
    protected function getHeadline()
    {
        return $this->Headline;
    }
    // End getHeadline

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the sub headline element
     *
     * @param  -
     * @return DOMElement
     */
    protected function getSubHeadline()
    {
        return $this->SubHeadline;
    }
    // End getSubHeadline

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the container element
     *
     * @param  -
     * @return DOMElement
     */
    protected function getContainer()
    {
        return $this->Container;
    }
    // End getContainer

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns (or creates) the info element
     *
     * @param  -
     * @return DOMElement
     */
    protected function getInfo()
    {
        if(!$this->Info)
            $this->Info = $this->Content->appendChild($this->getUl(['class' => 'infos clearfix']));

        return $this->Info;
    }
    // End getInfo

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the container element
     *
     * @param  -
     * @return DOMElement
     */
    protected function getContentContainer()
    {
        return $this->Content;
    }
    // End getContentContainer

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaSheetView
