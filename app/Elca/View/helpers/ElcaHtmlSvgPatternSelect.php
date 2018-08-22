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
namespace Elca\View\helpers;

use Beibob\Blibs\HtmlDOMFactory;
use Beibob\HtmlTools\HtmlFormElement;
use DOMDocument;
use Elca\Db\ElcaSvgPattern;
use Elca\Db\ElcaSvgPatternSet;

/**
 * Builds a pattern selectbox with preview image
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaHtmlSvgPatternSelect extends HtmlFormElement
{
    /** @var int $svgPatternId */
    private $svgPatternId;

    /** @var bool $isChanged */
    private $isChanged = false;


    /**
     * Sets the default svg pattern
     */
    public function setDefaultSvgPatternId($svgPatternId)
    {
        $this->svgPatternId = $svgPatternId;
    }
    // End setDefaultSvgPatternId


    /**
     * Sets the element changed
     */
    public function setIsChanged($isChanged = true)
    {
        $this->isChanged = $isChanged;
    }
    // End setChanged

    /**
     * @return boolean
     */
    public function isChanged()
    {
        return $this->isChanged;
    }
    // End isChanged


    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $Factory = new HtmlDOMFactory($Document);

        $DataObject = $this->getDataObject();
        $name = $this->getName();
        $currentSvgPatternId = $this->getConvertedTextValue();//is_object($DataObject)? $DataObject->$name : null;

        $Div = $Factory->getDiv();
        $ImgDiv = $Div->appendChild($Factory->getDiv(['class' => 'cell image']));
        $ImgDiv->appendChild($Factory->getSpan(null, ['class' => 'image']));

        // add selectbox
        $SelDiv = $Div->appendChild($Factory->getDiv(['class' => 'cell select']));

        $attributes = ['name' => $name];

        if ($this->svgPatternId) {
            $attributes['data-default-value'] = $this->svgPatternId;
        }
        if ($currentSvgPatternId) {
            $attributes['data-orig-value'] = $currentSvgPatternId;
        }
        if ($this->isReadonly()) {
            $attributes['disabled'] = 'disabled';
        }
        if ($this->isChanged()) {
            $attributes['class'] = 'changed';
        }

        $Select = $SelDiv->appendChild($Factory->getSelect($attributes));

        $SvgPatterns = ElcaSvgPatternSet::find(null, ['name' => 'ASC']);

        /** @var ElcaSvgPattern $SvgPattern */
        foreach ($SvgPatterns as $SvgPattern) {
            $svgPatternId = $SvgPattern->getId();
            $patternName = $SvgPattern->getName();

            if ($svgPatternId == $this->svgPatternId)
                $patternName .= ' (' . t('Voreinstellung') . ')';

            $Opt = $Select->appendChild($Factory->getOption(['value'                => $svgPatternId,
                                                             'data-svg-pattern-url' => $SvgPattern->getImageUrl()
            ], $patternName));

            if ($svgPatternId == $currentSvgPatternId || (!$currentSvgPatternId && $svgPatternId == $this->svgPatternId)) {
                $Opt->setAttribute('selected', 'selected');
            }
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($Div, $DataObject, $this->getName());
        $Factory->addClass($Div, 'elca-svg-pattern-select');

        return $Div;
    }
    // End build
}
// End ElcaHtmlSvgPatternSelect
