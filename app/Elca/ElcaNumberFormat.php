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
namespace Elca;
use Beibob\Blibs\Environment;
use Elca\Service\ElcaLocale;

/**
 * Number formatter
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaNumberFormat
{
    private static $formatCharacters = ['de' => ['decPoint' => ',', 'thousendSep' => '.'],
                                        'en' => ['decPoint' => '.', 'thousendSep' => ','],
                                        'es' => ['decPoint' => ',', 'thousendSep' => '.'],
    ];

    protected static $translatedUnits = array();

    /**
     * Checks wether the given string is a valid numeric value
     */
    public static function isNumeric($numberString, $decPoint = '?')
    {
        return is_numeric(self::fromString($numberString, null, false, $decPoint));
    }
    // End isNumeric


    /**
     * Parses a string number into a float
     *
     * @param  string $numberString
     * @return float
     */
    public static function fromString($numberString, $precision = null, $isPercentage = false, $decPoint = '?')
    {
        if ($decPoint == '?')
        {
            $ElcaLocale = Environment::getInstance()->getContainer()->get(ElcaLocale::class);
            if (!isset(self::$formatCharacters[$ElcaLocale->getLocale()]))
                throw new \Exception('decPoint definition missing for locale `' . $ElcaLocale->getLocale() . '\' in ElcaNumberFormat::$formatCharacters');
            $decPoint = self::$formatCharacters[$ElcaLocale->getLocale()]['decPoint'];
        }

        if ($numberString === '')
            return null;

        $value = strtr($numberString, [$decPoint => '.']);

        if (!\is_numeric($value)) {
            return null;
        }

        $value = (float)$value;

        if ($isPercentage)
            $value = $value / 100;

        if (null !== $precision)
            return round($value, $precision);

        return $value;
    }
    // End fromString


    /**
     * Returns a string representation of the float
     *
     * @param  float $value
     * @return string
     */
    public static function toString($number, $precision = null, $isPercentage = false, $decPoint = '?', $inScientific = false)
    {
        if ($decPoint == '?')
        {
            $ElcaLocale = Environment::getInstance()->getContainer()->get(ElcaLocale::class);
            if (!isset(self::$formatCharacters[$ElcaLocale->getLocale()]))
                throw new \Exception('decPoint and thousendSep definition missing for locale `' . $ElcaLocale->getLocale() . '\' in ElcaNumberFormat::$formatCharacters');

            $decPoint = self::$formatCharacters[$ElcaLocale->getLocale()]['decPoint'];
        }

        $exponent = 0;

        if(is_null($number))
            return null;

        if($isPercentage)
            $number = $number * 100;

        if($inScientific && $number != 0)
        {
            $baseTen = 10;
            $exponent = floor(log(abs($number), $baseTen));
            if(abs($exponent) > 2)
                $number = $number / pow($baseTen, $exponent);

            else
                $inScientific = false;
        }

        if(!is_null($precision))
            $number = sprintf('%.'.$precision.'f', round($number, $precision));

        $formated = $inScientific && $number != 0 && $exponent != 0 ? strtr((string)$number, array('.' => $decPoint)) . 'E' . $exponent : strtr((string)$number, array('.' => $decPoint));
        return $formated;
    }
    // End toString


    /**
     * Formats the units m2 and m3 into m² and m³
     *
     * @param  string
     * @return string
     */
    public static function formatUnit($unitStr)
    {
        if (!count(self::$translatedUnits))
        {
            foreach (Elca::$units as $k => $v)
                self::$translatedUnits[$k] = t($v);
        }

        return strtr($unitStr, self::$translatedUnits);
    }
    // End formatUnit

    public static function formatQuantity($number, $unit, $precision = null, $isPercentage = false, $decPoint = '?', $inScientific = false)
    {
        return self::toString($number, $precision, $isPercentage, $decPoint, $inScientific) . ' ' . self::formatUnit($unit);
    }

    /**
     * @return array
     */
    public static function formatCharacters($locale, $key, $default = null)
    {
        return isset(self::$formatCharacters[$locale][$key]) ? self::$formatCharacters[$locale][$key] : $default;
    }
    // End
}
// End ElcaNumberFormat
