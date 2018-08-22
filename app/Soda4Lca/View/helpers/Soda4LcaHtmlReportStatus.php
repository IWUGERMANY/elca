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
namespace Soda4Lca\View\helpers;

use Beibob\Blibs\HtmlDOMFactory;
use Beibob\HtmlTools\HtmlDataElement;
use DOMDocument;
use Soda4Lca\View\Soda4LcaDatabaseView;

/**
 * Builds a report status element
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 */
class Soda4LcaHtmlReportStatus extends HtmlDataElement
{
    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $DataObject = $this->getDataObject();
        $name = $this->getName();
        $value = $DataObject->$name;

        $cssClass = null;
        $title = null;
        $content = t(Soda4LcaDatabaseView::$processStatusMap[$value]);

        $Factory = new HtmlDOMFactory($Document);
        $Outer = $Factory->getDiv(['class' => 'status-info'], $Factory->getSpan($content));

        /* if($value != Soda4LcaProcess::STATUS_OK) */
        /* { */
        /*     $Factory->addClass($Outer, 'not-ok'); */
        /*     //$Span->setAttribute('title', $DataObject->details); */
        /*     //$Overlay = $Outer->appendChild($Factory->getDiv(array('class' => 'details-info'))); */
        /*     //$Overlay->appendChild($Factory->getText($DataObject->details)); */
        /* } */

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($Outer, $DataObject, $this->getName());

        return $Outer;
    }
    // End build

}
// End Soda4LcaHtmlReportStatus
