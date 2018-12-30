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

class LinearConversion extends AbstractConversion
{
    use SurrogateIdTrait;

    /**
     * @var float
     */
    private $factor;

    public function __construct(Unit $fromUnit, Unit $toUnit, float $factor)
    {
        parent::__construct($fromUnit, $toUnit);

        $this->factor = $factor;
    }

    public function factor(): float
    {
        return $this->factor;
    }

    public function convert(float $value): float
    {
        if ($this->isTrivial()) {
            return $value;
        }

        return $value * $this->factor;
    }

    public function invert(): Conversion
    {
        return new self($this->toUnit(), $this->fromUnit(), 1 / $this->factor());
    }

    public function __toString(): string
    {
        return parent::__toString() . ': ' . $this->factor;
    }
}
