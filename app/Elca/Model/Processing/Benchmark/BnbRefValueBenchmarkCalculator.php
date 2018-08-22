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

namespace Elca\Model\Processing\Benchmark;

use Elca\Model\Indicator\IndicatorValue;

class BnbRefValueBenchmarkCalculator
{
    /**
     * @var array
     */
    private $refConstructionIndicatorValues;

    /**
     * @var BnbFixedValuesBenchmarkCalculator
     */
    private $fixedValuesBenchmarkCalculator;

    /**
     * FixedValueBenchmarkCalculator constructor.
     *
     * @param NamedScoreThresholds[]|array $indicatorThresholds
     * @param IndicatorValue[]|array       $refConstructionIndicatorValues
     */
    public function __construct(array $indicatorThresholds, array $refConstructionIndicatorValues)
    {
        $this->fixedValuesBenchmarkCalculator = new BnbFixedValuesBenchmarkCalculator($indicatorThresholds);

        $this->refConstructionIndicatorValues = $this->mapIndicatorValues($refConstructionIndicatorValues);
    }

    /**
     * @param IndicatorValue[]|array $indicatorTotalValues
     * @param IndicatorValue[]|array $finalEnergyRefModelIndicatorValues
     * @return array
     */
    public function compute(array $indicatorTotalValues, array $finalEnergyRefModelIndicatorValues): array
    {
        $finalEnergyRefModelIndicatorValues = $this->mapIndicatorValues($finalEnergyRefModelIndicatorValues);

        $indicatorValues = [];
        foreach ($indicatorTotalValues as $totalIndicatorValue) {
            $ident = (string)$totalIndicatorValue->ident();

            if (!isset($this->refConstructionIndicatorValues[$ident], $finalEnergyRefModelIndicatorValues[$ident]) ||
                !$totalIndicatorValue->value() ||
                !$this->refConstructionIndicatorValues[$ident]->value() ||
                !$finalEnergyRefModelIndicatorValues[$ident]->value()
            ) {
                continue;
            }

            $indicatorValues[$ident] = new IndicatorValue(
                $totalIndicatorValue->ident(),
                $totalIndicatorValue->value() / ($this->refConstructionIndicatorValues[$ident]->value() + $finalEnergyRefModelIndicatorValues[$ident]->value())
            );
        }

        return $this->fixedValuesBenchmarkCalculator->compute($indicatorValues);
    }

    /**
     * @param IndicatorValue[]|array $indicatorValues
     * @return IndicatorValue[]|array
     */
    private function mapIndicatorValues(array $indicatorValues)
    {
        $map = [];

        foreach ($indicatorValues as $indicatorValue) {
            $map[(string)$indicatorValue->ident()] = $indicatorValue;
        }

        return $map;
    }
}
