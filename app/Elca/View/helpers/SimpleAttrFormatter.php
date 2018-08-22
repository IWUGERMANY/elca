<?php
/**
 * This file is part of blibs - mvc development framework
 *
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *                    Fabian Möller <fab@beibob.de>
 *                    BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * blibs is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * blibs is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with blibs. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Elca\View\helpers;

use Beibob\HtmlTools\AbstractFormatter;
use Beibob\HtmlTools\HtmlElement;

/**
 * This is a generic formatter which sets an attribute to an object property value
 *
 * @package htmlTools
 * @author Tobias <tobias@beibob.de>
 */
class SimpleAttrFormatter extends AbstractFormatter
{
    /**
     * Attribute and object property
     */
    private $attribute;
    private $property;
    private $append;


    /**
     * Creates a new instance of the formatter
     *
     * @param string $attribute
     * @param string $property
     */
    public function __construct($attribute = 'id', $property = 'id', $append = false)
    {
        $this->attribute = $attribute;
        $this->property = $property;
        $this->append = $append;
    }
    // End __construct


    /**
     * Formats the object
     *
     * @see Formatter::format()
     */
    public function format(HtmlElement $Element, $DataObject = null, $property = null)
    {
        if(!is_object($DataObject))
            return;

        $propertyName = $this->property;
        if(!isset($DataObject->$propertyName))
            return;

        if($this->append)
        {
            $Element->cutAttribute($this->attribute, $DataObject->$propertyName);
            $Element->appendAttribute($this->attribute, $DataObject->$propertyName);
        }
        else
            $Element->setAttribute($this->attribute, $DataObject->$propertyName);
    }
    // End format
}
// End AttributeFormatter
