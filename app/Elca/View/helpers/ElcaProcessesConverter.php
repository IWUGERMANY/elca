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
 * Converter for the process database data
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaProcessesConverter extends ObjectPropertyConverter
{
    /**
     * Returns the converted value
     */
    public function convertToText($value, $DataObject = null, $property = null)
    {
        switch($property)
        {
            case 'refValue':
                return ElcaNumberFormat::toString($DataObject->refValue, 2). ' '. ElcaNumberFormat::formatUnit($DataObject->refUnit);
            case 'epdType':
                return isset($DataObject->epdType) ? t($DataObject->epdType) : '';
        }

        return parent::convertToText($value, $DataObject, $property);
    }
    // End convert


}
// End ElcaProcessesConverter
