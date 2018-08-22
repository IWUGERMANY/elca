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

use Beibob\HtmlTools\ObjectPropertyConverter;
use Elca\ElcaNumberFormat;

/**
 * This converter converts numeric input values into german notation
 *
 * @package elca
 * @author  Tobias <tobias@beibob.de>
 *
 */
class ElcaNumberFormatConverter extends ObjectPropertyConverter
{
    /**
     * Constructor
     *
     * @param  -
     *
     * @return -
     */
    public function __construct($precision = null, $isPercentage = false, $decPoint = '?', $inScientific = false)
    {
        if (!is_null($precision))
            $this->set('precision', $precision);

        $this->set('decPoint', $decPoint);
        $this->set('isPercentage', $isPercentage);
        $this->set('inScientific', $inScientific);
    }
    // End __construct


    /**
     * Returns $DataObject->$property
     *
     * @param  string    $value      the previous value
     * @param  \stdClass $DataObject the data object
     * @param  string    $property   the property
     *
     * @return string                $DataObject->$property or $property if $DataObject is null
     */
    public function convertToText($value, $DataObject = null, $property = null)
    {
        $value = (string)parent::convertToText($value, $DataObject, $property);
        if ($value === '')
            return '';

        switch ($property) {
            case 'refUnit':
                return ElcaNumberFormat::formatUnit($value);
        }

        return ElcaNumberFormat::toString($value, $this->get('precision'), $this->get('isPercentage'), $this->get('decPoint'), $this->get('inScientific'));
    }
    // End convertToText


    /**
     * Sets $DataObject->$property to $value
     *
     * @param  string    $value      the value
     * @param  \stdClass $DataObject the data object
     * @param  string    $property   the property
     *
     * @return string                the $value
     */
    public function convertFromText($value, $DataObject = null, $property = null)
    {
        if ($value === '')
            return null;

        $value = ElcaNumberFormat::fromString($value, $this->get('precision'), $this->get('isPercentage'), $this->get('decPoint'));

        if (!is_null($DataObject) && !is_null($property))
            $DataObject->$property = $value;

        return $value;
    }
    // convertFromText

}
// End ElcaNumberFormatConverter
