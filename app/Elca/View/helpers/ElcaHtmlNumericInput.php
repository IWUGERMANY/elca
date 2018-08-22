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

use Beibob\Blibs\DbObject;
use Beibob\HtmlTools\HtmlInputElement;
use Beibob\HtmlTools\HtmlTextInput;
use Beibob\HtmlTools\Interfaces\Converter;

/**
 * Numeric input form element
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlNumericInput extends HtmlTextInput
{
    /**
     * Creates a text input element
     *
     * @see HtmlFormElement::__construct()
     * @param string    $name
     * @param null      $value
     * @param bool      $readonly
     * @param Converter $defaultConverter
     * @param DbObject  $DataObject
     */
    public function __construct($name, $value = null, $readonly = false, Converter $defaultConverter = null, DbObject $DataObject = null)
    {
        if (null === $defaultConverter) {
            $defaultConverter = new ElcaNumberFormatConverter();
        }

        parent::__construct($name, $value, $readonly, $defaultConverter, $DataObject);

        /**
         * Add css class
         */
        $this->addClass('numeric-input');

        if ($precision = $defaultConverter->get('precision'))
            $this->setPrecision($precision);
    }
    // End __construct

    /**
     * Sets the precision for the jquery.numeric input plugin
     *
     * @param $precision
     */
    public function setPrecision($precision)
    {
        $this->setAttribute('data-scale', $precision);
    }
    // End setPrecision

    /**
     * @param bool $disable
     */
    public function disableNegative($disable = true)
    {
        $this->setAttribute('data-negative', $disable? 'false' : 'true');
    }
    // End disableNegative
}
// End ElcaHtmlNumericInput
