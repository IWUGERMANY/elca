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
use Beibob\HtmlTools\HtmlDataElement;
use DOMDocument;

/**
 * Builds a report bar
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlReportBar extends HtmlDataElement
{
    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $barValue = intval($this->getConvertedTextValue());

        $width = abs($barValue);

        $Factory = new HtmlDOMFactory($Document);
        $Outer = $Factory->getDiv(array('class' => 'report-bar-outer'));

        if($barValue < 0)
            $Factory->addClass($Outer, 'gain');

        if($width > 100)
            $Factory->addClass($Outer, 'overdub');

        $Outer->appendChild($Factory->getDiv(array('class' => 'report-bar', 'style' => 'width:'. min($width, 100) .'%')));

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($Outer, $this->getDataObject(), $this->getName());

        return $Outer;
    }
    // End build

}
// End ElcaHtmlReportBar
