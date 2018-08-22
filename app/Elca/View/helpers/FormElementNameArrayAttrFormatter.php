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
class FormElementNameArrayAttrFormatter extends AbstractFormatter
{
    /**
     * object property
     */
    private $idProperty;

    /**
     * @var
     */
    private $property;

    /**
     * Creates a new instance of the formatter
     *
     * @param        $property
     * @param string $idProperty
     * @internal param string $attribute
     */
    public function __construct($property, $idProperty = 'id')
    {
        $this->idProperty = $idProperty;
        $this->property = $property;
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

        $idProperty = $this->idProperty;
        if(!isset($DataObject->$idProperty))
            return;

        $Element->setAttribute('name', sprintf('%s[%s]', $this->property, $DataObject->$idProperty));
    }
    // End format
}
// End AttributeFormatter
