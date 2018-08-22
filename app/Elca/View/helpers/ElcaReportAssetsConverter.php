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
 * Converter for the assets report
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaReportAssetsConverter extends ObjectPropertyConverter
{
    /**
     * Returns the converted value
     */
    public function convertToText($value, $DataObject = null, $property = null)
    {
        switch($property)
        {
            case 'component_layer_position':
                return $DataObject->component_layer_position.'.';

            case 'process_ratio':
                return ElcaNumberFormat::toString($DataObject->process_ratio, 0, true) . '%';

            case 'process_ref_value':
                return ElcaNumberFormat::toString($DataObject->process_ref_value, 2). ' '. ElcaNumberFormat::formatUnit($DataObject->process_ref_unit);
            case 'total':
                return ElcaNumberFormat::toString($DataObject->total, 2). ' '. ElcaNumberFormat::formatUnit($DataObject->total_unit);
        }

        return parent::convertToText($value, $DataObject, $property);
    }
    // End convert

}
// End ElcaReportAssetsConverter
