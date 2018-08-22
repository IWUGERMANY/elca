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

namespace Elca\Model\Benchmark;

use Elca\Db\ElcaBenchmarkRefConstructionValueSet;
use Elca\Db\ElcaBenchmarkThresholdSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Processing\Benchmark\BnbRefValueBenchmarkCalculator;
use Elca\Model\Processing\Benchmark\NamedScoreThresholds;

class ReferenceModelBenchmarkMethod implements BenchmarkMethod
{
    public function name(): string
    {
        return 'reference model method';
    }

    public function usesReferenceModel(): bool
    {
        return true;
    }

    public function compute(ElcaBenchmarkVersion $benchmarkVersion, ElcaProjectVariant $projectVariant, float $m2a) : array
    {
        $indicators = ElcaIndicatorSet::findWithPetByProcessDbId(
            $projectVariant->getProject()->getProcessDbId()
        )->getArrayBy('ident', 'id');

        $totalValues = ElcaReportSet::findTotalEffects($projectVariant->getId())->getArrayBy('value', 'ident');

        $refConstrValues = ElcaBenchmarkRefConstructionValueSet::findByBenchmarkVersionId(
            $benchmarkVersion->getId()
        )->getArrayBy('value', 'indicatorId');

        $refOpValues     = ElcaReportSet::findFinalEnergyRefModelEffects($projectVariant->getId())->getArrayBy(
            'value',
            'indicator_id'
        );

        $thresholds = [];
        $refConstrIndicatorValues = [];
        $indicatorTotalValues = [];
        $finalEnergyRefModelIndicatorValues = [];
        foreach ($indicators as $id => $ident) {
            if (!isset($refConstrValues[$id], $refOpValues[$id], $totalValues[$ident]) ||
                !$refConstrValues[$id] || !$refOpValues[$id] || !$totalValues[$ident]) {
                continue;
            }

            $indicatorIdent  = new IndicatorIdent($ident);

            $thresholds[$ident] = new NamedScoreThresholds(
                (string)$indicatorIdent,
                ElcaBenchmarkThresholdSet::findByVersionIdAndIndicatorIdent($benchmarkVersion->getId(), $ident)
                                         ->getArrayBy('value', 'score')
            );

            $refConstrIndicatorValues[$ident] = new IndicatorValue($indicatorIdent, $refConstrValues[$id]);
            $finalEnergyRefModelIndicatorValues[$ident] = new IndicatorValue($indicatorIdent, $refOpValues[$id]);
            $indicatorTotalValues[$ident] = new IndicatorValue($indicatorIdent, $totalValues[(string)$ident]);
        }

        $calculator = new BnbRefValueBenchmarkCalculator($thresholds, $refConstrIndicatorValues);
        return $calculator->compute($indicatorTotalValues, $finalEnergyRefModelIndicatorValues);
    }

    public function __toString()
    {
        return $this->name();
    }
}
