<?php
/**
 * This file is part of the elca project
 *
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *                    Fabian Möller <fab@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * elca is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * elca is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with elca. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Elca\View\helpers;

use Beibob\Blibs\Environment;
use Beibob\HtmlTools\ObjectPropertyConverter;
use Elca\ElcaNumberFormat;

/**
 * This converter converts numeric input values into euro schreibweise
 *
 * @package elca
 *
 */
class ElcaCurrencyConverter extends ObjectPropertyConverter
{
    /**
     * Constructor
     *
     * @param  -
     *
     * @return -
     */
    public function __construct($decPoint = '?', $thousendSep = '?', $currencySymbol = '€')
    {
        if ($decPoint == '?' || $thousendSep == '?')
        {
            $ElcaLocale = Environment::getInstance()->getContainer()->get('Elca\Service\ElcaLocale');
            if (!ElcaNumberFormat::formatCharacters($ElcaLocale->getLocale(), 'decPoint') ||
                !ElcaNumberFormat::formatCharacters($ElcaLocale->getLocale(), 'thousendSep')) {
                throw new \Exception(
                    'decPoint and thousendSep definition missing for locale `'.$ElcaLocale->getLocale(
                    ).'\' in ElcaNumberFormat::$formatCharacters'
                );
            }

        }
        $this->set('decPoint', $decPoint == '?' ? ElcaNumberFormat::formatCharacters($ElcaLocale->getLocale(), 'decPoint') : $decPoint);
        $this->set('thousendSep', $thousendSep == '?' ? ElcaNumberFormat::formatCharacters($ElcaLocale->getLocale(), 'thousendSep') : $thousendSep);

        $this->set('currencySymbol', $currencySymbol);
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


        return number_format($value, 2, $this->get('decPoint'), $this->get('thousendSep')) . ' ' . $this->get('currencySymbol');
    }
    // End convertToText
}
// End ElcaCurrencyConverter
