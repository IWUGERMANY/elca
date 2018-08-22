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

class ElcaHtmlCheckbox extends HtmlFormElement
{
    /**
     * Builds this element.
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $inputElt = $Document->createElement('input');

        $inputElt->setAttribute('type', 'checkbox');
        $inputElt->setAttribute('name', $this->getName());

        if ($this->isReadonly()) {
            $inputElt->setAttribute('readonly', 'readonly');
        }
        if ($this->isDisabled()) {
            $inputElt->setAttribute('disabled', 'disabled');
        }

        $inputElt->setAttribute('value', (string)$this->getConvertedTextValue());

        if ($this->isChecked()) {
            $inputElt->setAttribute('checked', 'checked');
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($inputElt, $this->getDataObject(), $this->getName());

        return $inputElt;
    }

    public function isChecked()
    {
        $property = $this->getName();
        $dataObject = $this->getDataObject();

        if ($property && preg_match('/^(.+?)\[(.+?)\]$/', $property, $matches)) {
            $name = $matches[1];
            $key  = $matches[2];

            if (isset($dataObject->$name) && is_array($dataObject->$name)) {
                $value = $dataObject->$name;

                return isset($value[$key]);
            }

            return null;
        }

        return isset($dataObject->$property);
    }
}
