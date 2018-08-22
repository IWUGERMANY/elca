<?php
/**
 *  This file is part of the eLCA project
 *
 *  eLCA
 *  A web based life cycle assessment application
 *
 *  Copyright (c) 2017 Tobias Lode <tobias@beibob.de>
 *                BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 *  eLCA is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  eLCA is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Elca\Model\ProcessConfig\Conversion;


use Elca\Model\Common\Unit;

final class ConversionType
{
    public const UNKNOWN = '';
    public const GROSS_DENSITY = 'GROSS_DENSITY';
    public const INITIAL = 'INITIAL';
    public const PRODUCTION = 'PRODUCTION';
    public const AVG_MPUA = 'AVG_MPUA';
    public const BULK_DENSITY = 'BULK_DENSITY';
    public const LAYER_THICKNESS = 'LAYER_THICKNESS';
    public const PRODUCTIVENESS = 'PRODUCTIVENESS';
    public const LINEAR_DENSITY = 'LINEAR_DENSITY';
    public const ENERGY_EQUIVALENT = 'ENERGY_EQUIVALENT';
    public const CONVERSION_TO_MASS = 'CONVERSION_TO_MASS';

    private static $knownTypes = [
        Unit::CUBIC_METER  => [
            Unit::KILOGRAMM => self::GROSS_DENSITY,
        ],
        Unit::SQUARE_METER => [
            Unit::KILOGRAMM => self::AVG_MPUA,
        ],
        Unit::METER        => [
            Unit::KILOGRAMM => self::LINEAR_DENSITY,
        ],
        Unit::KILOWATTHOUR => [
            Unit::MEGAJOULE => self::ENERGY_EQUIVALENT,
        ]
    ];

    /**
     * @var string
     */
    private $value;

    public static function guess(Conversion $conversion): self
    {
        return self::guessForUnits($conversion->fromUnit(), $conversion->toUnit());
    }

    public static function guessForUnits(Unit $fromUnit, Unit $toUnit): self
    {
        return new self(
            self::$knownTypes[(string)$fromUnit][(string)$toUnit] ?? self::UNKNOWN
        );
    }

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }


    public function equals(self $object): bool
    {
        return $this->value === $object->value;
    }

    public function isKnown(): bool
    {
        return $this->value !== self::UNKNOWN;
    }

    public function isGrossDensity(): bool
    {
        return self::GROSS_DENSITY === $this->value;
    }
}