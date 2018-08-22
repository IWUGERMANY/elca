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
class ElcaHtmlBenchmarkProjection extends HtmlDataElement
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
        $valProperty = $name .'Projection';
        $ratingName = $name .'Rating';

        $Factory = new HtmlDOMFactory($Document);
        $Div = $Factory->getSpan(isset($DataObject->$valProperty) && !is_null($DataObject->$valProperty)? $DataObject->$valProperty : '',
                                 ['class' => isset($DataObject->$ratingName)? $DataObject->$ratingName : '']
        );

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($Div, $DataObject, $this->getName());

        return $Div;
    }
    // End build

}
// End ElcaHtmlBenchmarkProjection
