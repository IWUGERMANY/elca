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

namespace Elca\Model\Indicator;


use Assert\Assert;

class IndicatorValue
{
    /**
     * @var IndicatorIdent
     */
    private $ident;

    /**
     * @var int|float
     */
    private $value;

    /**
     * @param array $map - [ indicatorIdent => value ]
     * @return IndicatorValue[]
     */
    public static function valuesFromMap(array $map) : array
    {
        $list = [];
        foreach ($map as $ident => $value) {
            $list[$ident] = new IndicatorValue(
                new IndicatorIdent($ident),
                $value
            );
        }

        return $list;
    }

    public static function valuesToMap(array $list): array
    {
        Assert::thatAll($list)->isInstanceOf(IndicatorValue::class);

        $map = [];
        foreach ($list as $value) {
            $map[(string)$value->ident()] = $value->value();
        }

        return $map;
    }

    /**
     * IndicatorValue constructor.
     *
     * @param IndicatorIdent $ident
     * @param float|null $value
     */
    public function __construct(IndicatorIdent $ident, float $value = null)
    {
        $this->ident = $ident;
        $this->value = $value;
    }

    /**
     * @return IndicatorIdent
     */
    public function ident(): IndicatorIdent
    {
        return $this->ident;
    }

    /**
     * @return int|float|null
     */
    public function value(): ?float
    {
        return $this->value;
    }

    public function isDefined(): bool
    {
        return null !== $this->value;
    }

    public function add(float $value) : IndicatorValue
    {
        return new self(
            $this->ident,
            $this->value + $value
        );
    }

    public function multiplyBy(float $value) : IndicatorValue
    {
        return new self(
            $this->ident,
            $this->value * $value
        );
    }

    public function divideBy(float $value) : IndicatorValue
    {
        return new self(
            $this->ident,
            $this->value / $value
        );
    }
}
