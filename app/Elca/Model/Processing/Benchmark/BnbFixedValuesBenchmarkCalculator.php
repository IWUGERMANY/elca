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

use Elca\Db\ElcaIndicator;
use Elca\Model\Indicator\IndicatorValue;

class BnbFixedValuesBenchmarkCalculator
{
    /**
     * @var NamedScoreThresholds[]
     */
    private $thresholds;

    /**
     * FixedValueBenchmarkCalculator constructor.
     *
     * @param NamedScoreThresholds[]|array $indicatorThresholds
     */
    public function __construct(array $indicatorThresholds)
    {
        $this->thresholds = [];

        foreach ($indicatorThresholds as $thresholds) {
            $this->thresholds[$thresholds->name()] = $thresholds;
        }
    }

    /**
     * @param IndicatorValue[]|array $indicatorValues
     * @return array
     */
    public function compute(array $indicatorValues): array
    {
        $result = [];

        foreach ($indicatorValues as $indicatorValue) {
            $indicatorIdent = $indicatorValue->ident();

            $linearInterpolator = new LinearScoreInterpolator(
                $this->thresholds[(string)$indicatorIdent]
            );

            if ($indicatorIdent->isRenewablePrimaryEnergy()) {
                $result[(string)$indicatorIdent] = $this->computeRenewablePrimaryEnergy(
                    $linearInterpolator,
                    $indicatorValues
                );
            } else {
                $result[(string)$indicatorIdent] = $linearInterpolator->computeScore($indicatorValue->value());
            }
        }

        if ($this->isEn15804Compliant()) {
            if (isset($result[ElcaIndicator::IDENT_PET], $result[ElcaIndicator::IDENT_PERT])) {
                $result['pe'] = min(
                    100,
                    ($result[ElcaIndicator::IDENT_PET] ?? 0)
                    + ($result[ElcaIndicator::IDENT_PERT] ?? 0)
                    + ($result[ElcaIndicator::IDENT_PENRT] ?? 0)
                );
            }
        } else {
            if (isset($result[ElcaIndicator::IDENT_PET], $result[ElcaIndicator::IDENT_PE_EM])) {
                $result['pe'] = min(
                    100,
                    ($result[ElcaIndicator::IDENT_PET] ?? 0)
                    + ($result[ElcaIndicator::IDENT_PE_EM] ?? 0)
                );
            }
        }

        return $result;
    }

    /**
     * @param LinearScoreInterpolator $linearInterpolator
     * @param array                   $indicatorValues
     * @return null|float|int
     */
    private function computeRenewablePrimaryEnergy(LinearScoreInterpolator $linearInterpolator, array $indicatorValues)
    {
        $total = $indicatorValues[ElcaIndicator::IDENT_PET]->value();
        $value = $indicatorValues[(string)$linearInterpolator->name()]->value() / $total * 100;

        return $linearInterpolator->computeScore($value);
    }

    private function isEn15804Compliant()
    {
        return isset($this->thresholds[ElcaIndicator::IDENT_PERT]) ||
               isset($this->thresholds[ElcaIndicator::IDENT_PENRT]);
    }
}
