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

namespace Elca\Model\ProcessConfig\Conversion;

use Elca\Model\Common\Unit;
use Utils\Model\SurrogateIdTrait;

abstract class AbstractConversion implements Conversion
{
    /**
     * @var Unit
     */
    private $fromUnit;

    /**
     * @var Unit
     */
    private $toUnit;

    public function __construct(Unit $fromUnit, Unit $toUnit)
    {
        $this->fromUnit = $fromUnit;
        $this->toUnit   = $toUnit;
    }

    public function fromUnit(): Unit
    {
        return $this->fromUnit;
    }

    public function toUnit(): Unit
    {
        return $this->toUnit;
    }

    public function isTrivial(): bool
    {
        return $this->fromUnit()->equals($this->toUnit());
    }

    abstract public function convert(float $value): float;

    abstract public function invert(): Conversion;

    public function type(): ConversionType
    {
        return ConversionType::guess($this);
    }

    public function isKnown(): bool
    {
        return $this->type()->isKnown();
    }

    public function __toString(): string
    {
        return $this->fromUnit.' >> '.$this->toUnit;
    }

    public function equals(Conversion $conversion): bool
    {
        return $this == $conversion;
    }
}
