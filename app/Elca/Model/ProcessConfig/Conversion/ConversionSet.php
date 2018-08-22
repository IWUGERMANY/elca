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

namespace Elca\Model\ProcessConfig\Conversion;

use Elca\Model\Common\Unit;
use phpDocumentor\Reflection\Types\Void_;

class ConversionSet implements \IteratorAggregate
{
    /**
     * @var Conversion[][]
     */
    private $conversions;

    /**
     * Converter constructor.
     *
     * @param Conversion[] $conversions
     */
    public function __construct(array $conversions = [])
    {
        $this->conversions = [];

        foreach ($conversions as $conversion) {
            $fromUnit = (string)$conversion->fromUnit();
            $toUnit   = (string)$conversion->toUnit();

            if (isset($this->conversions[$toUnit][$fromUnit])) {
                continue;
            }

            $this->conversions[$fromUnit][$toUnit] = $conversion;
        }
    }

    public function add(Conversion $conversion)
    {
        if (null !== $this->find($conversion->fromUnit(), $conversion->toUnit())) {
            return;
        }

        $this->conversions[(string)$conversion->fromUnit()][(string)$conversion->toUnit()] = $conversion;
    }

    public function has(Unit $fromUnit, Unit $toUnit): bool
    {
        return $this->hasExact($fromUnit, $toUnit) || $this->hasExact($toUnit, $fromUnit);
    }

    public function hasExact(Unit $fromUnit, Unit $toUnit): bool
    {
        return isset($this->conversions[(string)$fromUnit][(string)$toUnit]);
    }

    public function find(Unit $fromUnit, Unit $toUnit): ?Conversion
    {
        if ($this->hasExact($fromUnit, $toUnit)) {
            return $this->conversions[(string)$fromUnit][(string)$toUnit];
        }

        if ($this->hasExact($toUnit, $fromUnit)) {
            return $this->conversions[(string)$toUnit][(string)$fromUnit]
                ->invert();
        }

        return null;
    }

    public function toArray(): array
    {
        $list = [[]];
        foreach ($this->conversions as $fromUnit => $conversions) {
            $list[] = \array_values($conversions);
        }

        return \array_merge(...$list);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * @return Unit[]
     */
    public function extractUnits(): array
    {
        $units = [];
        foreach ($this->toArray() as $conversion) {
            $fromUnit             = $conversion->fromUnit();
            $units[(string)$fromUnit] = $fromUnit;
            $toUnit             = $conversion->toUnit();
            $units[(string)$toUnit] = $toUnit;
        }

        return $units;
    }

    public function isEmpty(): bool
    {
        return empty($this->conversions);
    }
}
