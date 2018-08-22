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
namespace Stlb\View\helpers;

use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\Interfaces\Formatter;
use Beibob\HtmlTools\ObjectPropertyConverter;
use Elca\ElcaNumberFormat;

/**
 * This converter converts numeric input values into german notation
 *
 * @package elca
 * @author Patrick Kocurek <patrick@kocurek.de> ,Tobias <tobias@beibob.de>
 */
class StlbConverter extends ObjectPropertyConverter implements Formatter
{
    /**
     * Processes the given values and either sets some properties on the $obj
     * or returns an array with attribute => value pairs
     *
     * @param  mixed  $value
     * @return array(html_attribute => value)
     */
    public function format(HtmlElement $Obj, $DataObject = null, $property = null)
    {
        switch ($property)
        {

            case 'hide':
                $Obj->setAttribute('href', '/stlb/data/hide/?id='.$DataObject->id );
                break;
            default:
                if ($DataObject && $property)
                    $Obj->setAttribute('title', $DataObject->$property);
                break;
        }
    }
    // End format


    /**
     * Returns $DataObject->$property
     *
     * @param  string $value         the previous value
     * @param  \stdClass $DataObject  the data object
     * @param  string $property      the property
     * @return string                $DataObject->$property or $property if $DataObject is null
     */
    public function convertToText($value, $DataObject = null, $property = null)
    {
        $value = (string)parent::convertToText($value, $DataObject, $property);
        switch($property)
        {
            case 'refUnit':
                return ElcaNumberFormat::formatUnit($value);
            case 'quantity':
                return ElcaNumberFormat::toString($value, 3);
            case 'hide':
                return '/stlb/data/hide/?id='.$DataObject->id; //'convertToText - verbergen';
        }

        return $value;
    }
    // End convertToText


    /**
     * Sets $DataObject->$property to $value
     *
     * @param  string $value         the value
     * @param  \stdClass $DataObject  the data object
     * @param  string $property      the property
     * @return string                the $value
     */
    public function convertFromText($value, $DataObject = null, $property = null)
    {
    }
    // convertFromText

}
// End StlbConverter
