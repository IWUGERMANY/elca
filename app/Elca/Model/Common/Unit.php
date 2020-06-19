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

namespace Elca\Model\Common;

class Unit
{
    public const KILOGRAMM = 'kg';
    public const CUBIC_METER = 'm3';
    public const SQUARE_METER = 'm2';
    public const METER = 'm';
    public const PIECE = 'Stück';
    public const KILOWATTHOUR = 'kWh';
    public const MEGAJOULE = 'MJ';
    public const TON_KILOMETER = 't*km';

    /**
     * @var string
     */
    private $unit;

    /**
     * @param $unitString
     * @return Unit
     */
    public static function fromString($unitString): Unit
    {
        return new Unit((string)$unitString);
    }

    /**
     * @return Unit
     */
    public static function kg(): Unit
    {
        return new Unit(self::KILOGRAMM);
    }

    /**
     * @return Unit
     */
    public static function m3(): Unit
    {
        return new Unit(self::CUBIC_METER);
    }

    /**
     * @return Unit
     */
    public static function m2(): Unit
    {
        return new Unit(self::SQUARE_METER);
    }

    /**
     * @return Unit
     */
    public static function m(): Unit
    {
        return new Unit(self::METER);
    }

    /**
     * @return Unit
     */
    public static function piece(): Unit
    {
        return new Unit(self::PIECE);
    }

    /**
     * @return Unit
     */
    public static function kWh(): Unit
    {
        return new Unit(self::KILOWATTHOUR);
    }

    /**
     * @return Unit
     */
    public static function tkm(): Unit
    {
        return new Unit(self::TON_KILOMETER);
    }

    public static function MJ(): Unit
    {
        return new Unit(self::MEGAJOULE);
    }

    public function __construct(string $unit)
    {
        $this->unit = $unit;
    }

    public function value(): string
    {
        return $this->unit;
    }

    public function __toString(): string
    {
        return $this->unit;
    }

    public function equals(Unit $unit): bool
    {
        return $this->unit === $unit->value();
    }

    public function isKg(): bool
    {
        return self::KILOGRAMM === $this->unit;
    }
}
