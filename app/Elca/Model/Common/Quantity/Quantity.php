<?php declare(strict_types=1);
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

namespace Elca\Model\Common\Quantity;

use Beibob\Blibs\FloatCalc;
use Elca\Model\Common\Unit;

class Quantity
{
    /**
     * @var int|float
     */
    private $value;

    /**
     * @var Unit
     */
    private $unit;

    public static function inKg(float $quantity): Quantity
    {
        return new self($quantity, Unit::kg());
    }
    public static function inM3(float $quantity): Quantity
    {
        return new self($quantity, Unit::m3());
    }
    public static function inM2(float $quantity): Quantity
    {
        return new self($quantity, Unit::m2());
    }
    public static function inMeter(float $quantity): Quantity
    {
        return new self($quantity, Unit::m());
    }
    public static function inPiece(float $quantity): Quantity
    {
        return new self($quantity, Unit::piece());
    }
    public static function inKWh(float $quantity): Quantity
    {
        return new self($quantity, Unit::kWh());
    }
    public static function inMJ(float $quantity): Quantity
    {
        return new self($quantity, Unit::MJ());
    }

    public static function fromValue($value, string $unit)
    {
        return new self($value, Unit::fromString($unit));
    }

    /**
     * @var float|int $value
     */
    public function __construct($value, Unit $unit)
    {
        if (!\is_numeric($value)) {
            throw new \InvalidArgumentException('Given value '. print_r($value, true) .' is not a numeric value');
        }

        $this->value = $value;
        $this->unit  = $unit;
    }

    /**
     * @return float|int
     */
    public function value()
    {
        return $this->value;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function scale(float $factor): Quantity
    {
        return new self($this->value * $factor, $this->unit);
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->value(), (string)$this->unit());
    }

    public function equals(self $object)
    {
        return FloatCalc::cmp($this->value, $object->value()) &&
               $this->unit->equals($object->unit);
    }

}
