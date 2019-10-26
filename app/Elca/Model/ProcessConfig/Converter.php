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

namespace Elca\Model\ProcessConfig;

use Elca\Model\Common\Unit;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\ConversionException;
use Elca\Model\ProcessConfig\Conversion\ConversionSet;

class Converter
{
    /**
     * @var ProcessConfigId
     */
    private $processConfigId;

    /**
     * @var ConversionSet
     */
    private $conversions;

    /**
     * Converter constructor.
     *
     * @todo check if processConfigId is realy needed
     *
     * @param ProcessConfigId $processConfigId
     * @param Conversion[]    $conversions
     */
    public function __construct(ProcessConfigId $processConfigId, array $conversions)
    {
        $this->conversions = new ConversionSet($conversions);
        $this->processConfigId = $processConfigId;
    }

    public function processConfigId(): ProcessConfigId
    {
        return $this->processConfigId;
    }

    /**
     * @throws ConversionException
     */
    public function convert(float $quantity, Unit $fromUnit, Unit $toUnit): float
    {
        if ($fromUnit->equals($toUnit)) {
            return $quantity;
        }

        $conversion = $this->conversions->find($fromUnit, $toUnit);

        if (null === $conversion) {
            throw new ConversionException($this->processConfigId, $fromUnit, $toUnit);
        }

        return $conversion->convert($quantity);
    }

    public function conversionsToConvertInto(Unit $toUnit)
    {
        return $this->conversions->filterByUnit($toUnit);
    }

    public function has(Unit $fromUnit, Unit $toUnit): bool
    {
        return $this->conversions->has($fromUnit, $toUnit);
    }

    public function hasExact(Unit $fromUnit, Unit $toUnit): bool
    {
        return $this->conversions->hasExact($fromUnit, $toUnit);
    }

    public function find(Unit $fromUnit, Unit $toUnit): ?Conversion
    {
        return $this->conversions->find($fromUnit, $toUnit);
    }

    public function conversions(): ConversionSet
    {
        return $this->conversions;
    }
}
