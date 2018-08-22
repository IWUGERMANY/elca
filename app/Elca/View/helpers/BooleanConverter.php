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

declare(strict_types = 1);
namespace Elca\View\helpers;

use Beibob\HtmlTools\ObjectPropertyConverter;

class BooleanConverter extends ObjectPropertyConverter
{
    private $falseValue;
    private $trueValue;

    /**
     * BooleanConverter constructor.
     *
     * @param $falseValue
     * @param $trueValue
     */
    public function __construct($falseValue, $trueValue)
    {
        $this->falseValue = $falseValue;
        $this->trueValue = $trueValue;
    }

    /**
     * Returns $DataObject->$property
     *
     * @param  string    $value      the previous value
     * @param  \stdClass $DataObject the data object
     * @param  string    $property   the property
     * @return string                $DataObject->$property or $property if $DataObject is null
     */
    public function convertToText($value, $DataObject = null, $property = null)
    {
        return $DataObject->$property ? $this->trueValue : $this->falseValue;
    }
}
