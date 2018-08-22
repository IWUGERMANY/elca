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

namespace Elca\Model\Processing;

use Elca\Model\Indicator\IndicatorId;

class IndicatorResult
{
    /**
     * @var IndicatorId
     */
    private $indicatorId;

    /**
     * @var float|null
     */
    private $value;

    /**
     * @param array $map - [ indicatorId => value ]
     * @return IndicatorResult[]
     */
    public static function valuesFromMap(array $map): array
    {
        $list = [];
        foreach ($map as $id => $value) {
            $list[] = new IndicatorResult(
                new IndicatorId($id),
                $value
            );
        }

        return $list;
    }

    public static function valuesToMap(IndicatorResults $list): array
    {
        $map = [];
        foreach ($list as $indicatorResult) {
            if (!$indicatorResult instanceof IndicatorResult) {
                continue;
            }

            $map[(string)$indicatorResult->indicatorId()] = $indicatorResult->value();
        }

        return $map;
    }

    public function __construct(IndicatorId $indicatorId, ?float $value)
    {
        $this->indicatorId = $indicatorId;
        $this->value       = $value;
    }

    public function indicatorId(): IndicatorId
    {
        return $this->indicatorId;
    }

    public function value(): ?float
    {
        return $this->value;
    }

    public function isDefined(): bool
    {
        return null !== $this->value;
    }

    public function add(?float $value): IndicatorResult
    {
        return new self(
            $this->indicatorId,
            $this->value + $value
        );
    }

    public function multiplyBy(float $value): IndicatorResult
    {
        return new self(
            $this->indicatorId,
            $this->value * $value
        );
    }

    public function divideBy(float $value): IndicatorResult
    {
        return new self(
            $this->indicatorId,
            $this->value / $value
        );
    }
}