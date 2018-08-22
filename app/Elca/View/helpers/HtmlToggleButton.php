<?php declare(strict_types=1);
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

use Beibob\HtmlTools\HtmlFormElement;
use DOMDocument;
use DOMNode;

class HtmlToggleButton extends HtmlFormElement
{
    /**
     * Builds this element.
     *
     * @see HtmlElement::build()
     * @param DOMDocument $document
     * @return DOMNode|\DOMElement
     */
    public function build(DOMDocument $document)
    {
        $divElt = $document->createElement('div');

        $inputElt = $document->createElement('input');

        $inputElt->setAttribute('type', 'checkbox');
        $inputElt->setAttribute('name', $this->getName());

        if ($this->isReadonly()) {
            $inputElt->setAttribute('readonly', 'readonly');
        }
        if ($this->isDisabled()) {
            $inputElt->setAttribute('disabled', 'disabled');
        }

        $inputElt->setAttribute('value', 'true');
        if ($this->getConvertedTextValue()) {
            $inputElt->setAttribute('checked', 'checked');
        }

        $inputElt->setAttribute('class', 'toggle toggle-light');

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($inputElt, $this->getDataObject(), $this->getName());
        $divElt->appendChild($inputElt);

        $label = $divElt->appendChild($document->createElement('label'));
        $label->setAttribute('class', 'toggle-btn');
        $label->setAttribute('for', $this->getId());

        return $divElt;
    }
}
