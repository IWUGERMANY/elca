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

namespace Elca\Service\Project\Processing;

use Beibob\Blibs\Config;
use Beibob\Blibs\DataObjectSet;
use Elca\Controller\Admin\BenchmarksCtrl;
use Elca\Db\ElcaBenchmarkGroupIndicator;
use Elca\Db\ElcaBenchmarkGroupSet;
use Elca\Db\ElcaBenchmarkGroupThresholdSet;
use Elca\Db\ElcaBenchmarkRefConstructionValueSet;
use Elca\Db\ElcaBenchmarkThreshold;
use Elca\Db\ElcaBenchmarkThresholdSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\Db\ElcaSetting;
use Elca\Db\ElcaSettingSet;
use Elca\Elca;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Processing\Benchmark\BnbFixedValuesBenchmarkCalculator;
use Elca\Model\Processing\Benchmark\BnbRefValueBenchmarkCalculator;
use Elca\Model\Processing\Benchmark\LinearScoreInterpolator;
use Elca\Model\Processing\Benchmark\NamedScoreThresholds;

/**
 * Helper service for benchmark calculations
 */
class BenchmarkService
{
    /**
     * @param ElcaBenchmarkVersion $benchmarkVersion
     * @param ElcaProjectVariant   $projectVariant
     * @return array
     */
    public function compute(ElcaBenchmarkVersion $benchmarkVersion, ElcaProjectVariant $projectVariant)
    {
        $m2a = $this->getM2aValue($projectVariant);

        /**
         * Depending on which calculation model to use get a data set
         */
        if ($benchmarkVersion->getUseReferenceModel()) {
            $results = $this->computeRefModelValues($benchmarkVersion, $projectVariant, $m2a);
        } else {
            $results = $this->computeFixedValues($benchmarkVersion, $projectVariant, $m2a);
        }

        return $results;
    }
    // End compute

    public function groupBenchmark(ElcaBenchmarkVersion $benchmarkVersion, array $benchmarkIndicatorscores)
    {
        $groups = ElcaBenchmarkGroupSet::findByBenchmarkVersionId($benchmarkVersion->getId());

        if (0 === $groups->count()) {
            return [];
        }

        $groupResults = [];

        foreach ($benchmarkIndicatorscores as $indicatorIdent => $indicatorscore) {
            $groupIndicator = ElcaBenchmarkGroupIndicator::findByBenchmarkVersionIdAndIndicatorIdent($benchmarkVersion->getId(), $indicatorIdent);

            if (!$groupIndicator->isInitialized()) {
                continue;
            }

            $groupResults[$indicatorIdent] = $dataObject = (object)[
                'name' => $groupIndicator->getGroup()->getName(),
                'caption' => '',
            ];

            $thresholds = ElcaBenchmarkGroupThresholdSet::findByGroupId($groupIndicator->getGroupId(), ['score' => 'ASC'])
                                                        ->getArrayBy('caption', 'score');

            foreach ($thresholds as $score => $caption){
                if ($indicatorscore < $score) {
                    break;
                }

                $dataObject->caption = $caption;
            }
        }

        return $groupResults;
    }


    /**
     * @param ElcaBenchmarkVersion $benchmarkVersion
     * @param ElcaProjectVariant   $projectVariant
     * @return array
     */
    public function computeProjection(ElcaBenchmarkVersion $benchmarkVersion, ElcaProjectVariant $projectVariant)
    {
        $m2a = $this->getM2aValue($projectVariant);

        $dataSets = [];

        /**
         * Depending on which calculation model to use get a data set
         */
        if ($benchmarkVersion->getUseReferenceModel()) {
            $constrValues = $this->getConstrProjectionValues($projectVariant);
            $opValues     = ElcaReportSet::findTotalEffectsPerLifeCycle(
                $projectVariant->getId(),
                ['life_cycle_phase' => ElcaLifeCycle::PHASE_OP]
            )->getArrayBy('value', 'ident');

            $refConstrValues = ElcaBenchmarkRefConstructionValueSet::findByBenchmarkVersionId(
                $benchmarkVersion->getId()
            )->getArrayBy('value', 'indicatorId');
            $refOpValues     = ElcaReportSet::findFinalEnergyRefModelEffects($projectVariant->getId())->getArrayBy(
                'value',
                'indicator_id'
            );

            foreach ($constrValues as $ConstrValue) {
                $id    = $ConstrValue->indicatorId;
                $ident = $ConstrValue->indicatorIdent;
                foreach (['min', 'max', 'avg'] as $name) {
                    if (!isset($opValues[$ident]) ||
                        !isset($refConstrValues[$id]) ||
                        !isset($refOpValues[$id])) {
                        continue;
                    }

                    $dataSets[$name][$ConstrValue->indicatorIdent] = ($ConstrValue->$name * $m2a + $opValues[$ident]) / ($refConstrValues[$id] * $m2a + $refOpValues[$id]);
                }
            }
        } else {
            $constrValues = $this->getConstrProjectionValues($projectVariant);
            $opValues     = ElcaReportSet::findTotalEffectsPerLifeCycle(
                $projectVariant->getId(),
                ['life_cycle_phase' => ElcaLifeCycle::PHASE_OP]
            )->getArrayBy('value', 'ident');

            foreach ($constrValues as $ConstrValue) {
                foreach (['min', 'max', 'avg'] as $name) {
                    if (!isset($opValues[$ConstrValue->indicatorIdent])) {
                        continue;
                    }
                    $dataSets[$name][$ConstrValue->indicatorIdent] = $ConstrValue->$name + $opValues[$ConstrValue->indicatorIdent] / $m2a;
                }
            }
        }

        $results = [];
        foreach ($dataSets as $name => $dataSet) {
            $results[$name] = $this->computeIndicators($benchmarkVersion, $dataSet);
        }

        return $results;
    }
    // End computeProjection


    /**
     * Returns the default values
     *
     * @param ElcaBenchmarkVersion $benchmarkVersion
     * @param bool                 $useReferenceModel
     * @return Config
     */
    public function getDefaultValues(ElcaBenchmarkVersion $benchmarkVersion, $useReferenceModel = null)
    {
        $useReferenceModel = $useReferenceModel ?? $benchmarkVersion->getUseReferenceModel();

        return Elca::getInstance()->getDefaults($useReferenceModel ? 'bnb-ref' : 'bnb-static');
    }
    // End getDefaultValues


    /**
     * Inits the benchmark version with default values from defaults.ini
     *
     * @param ElcaBenchmarkVersion $benchmarkVersion
     */
    public function initWithDefaultValues(ElcaBenchmarkVersion $benchmarkVersion)
    {
        if (!$benchmarkVersion->isInitialized()) {
            return;
        }

        $BnbDefaults = $this->getDefaultValues($benchmarkVersion);

        /** @var ElcaIndicator $Indicator */
        foreach ($BnbDefaults as $ident => $values) {
            $indicatorId = ElcaIndicator::findByIdent($ident)->getId();
            foreach ($values as $score => $value) {

                $Threshold = ElcaBenchmarkThreshold::findByBenchmarkVersionIdAndIndicatorIdAndScore(
                    $benchmarkVersion->getId(),
                    $indicatorId,
                    $score
                );
                if ($Threshold->isInitialized()) {
                    $Threshold->setValue($value);
                    $Threshold->update();
                } else {
                    ElcaBenchmarkThreshold::create($benchmarkVersion->getId(), $indicatorId, $score, $value);
                }
            }
        }
    }
    // End initWithDefaultValues


    /**
     * @param array  $indicatorValues
     * @param array  $benchmarks
     * @param string $indicatorIdent
     * @return float|null
     */
    private function computeRenewablePrimaryEnergy(LinearScoreInterpolator $benchmark, array $indicatorValues)
    {
        $total = 0;
        switch ((string)$benchmark->name()) {
            case ElcaIndicator::IDENT_PE_EM:
                if (!isset(
                    $indicatorValues[ElcaIndicator::IDENT_PE_EM],
                    $indicatorValues[ElcaIndicator::IDENT_PE_N_EM]
                )
                ) {
                    return null;
                }

                $total = $indicatorValues[ElcaIndicator::IDENT_PE_EM] + $indicatorValues[ElcaIndicator::IDENT_PE_N_EM];
                break;

            case ElcaIndicator::IDENT_PERE:
            case ElcaIndicator::IDENT_PERM:
                if (!isset(
                    $indicatorValues[(string)$benchmark->name()],
                    $indicatorValues[ElcaIndicator::IDENT_PERT],
                    $indicatorValues[ElcaIndicator::IDENT_PENRT]
                )
                ) {
                    return null;
                }

                $total = $indicatorValues[ElcaIndicator::IDENT_PERT] + $indicatorValues[ElcaIndicator::IDENT_PENRT];
        }

        if (!$total) {
            return null;
        }

        $value = $indicatorValues[(string)$benchmark->name()] / $total * 100;

        return $benchmark->computeScore($value);
    }
    // End computeRenewablePrimaryEnergy


    /**
     * @param ElcaBenchmarkVersion $benchmarkVersion
     * @param ElcaProjectVariant   $projectVariant
     * @param float                $m2a
     * @return array
     */
    private function computeFixedValues(ElcaBenchmarkVersion $benchmarkVersion, ElcaProjectVariant $projectVariant, $m2a
    ) {
        $dataSet = ElcaReportSet::findTotalEffects($projectVariant->getId())->getArrayBy('value', 'ident');

        $thresholds = [];

        foreach ($dataSet as $ident => $value) {
            $indicatorIdent  = new IndicatorIdent($ident);
            $dataSet[$ident] = new IndicatorValue(
                $indicatorIdent,
                $value / $m2a
            );

            $thresholds[$ident] = new NamedScoreThresholds(
                (string)$indicatorIdent,
                ElcaBenchmarkThresholdSet::findByVersionIdAndIndicatorIdent($benchmarkVersion->getId(), $ident)
                                         ->getArrayBy('value', 'score')
            );
        }

        return (new BnbFixedValuesBenchmarkCalculator($thresholds))
            ->compute($dataSet);
    }
    // End computeFixedValues


    /**
     * @param ElcaBenchmarkVersion $benchmarkVersion
     * @param ElcaProjectVariant   $ProjectVariant
     * @param float                $m2a
     * @return array
     */
    private function computeRefModelValues(
        ElcaBenchmarkVersion $benchmarkVersion, ElcaProjectVariant $ProjectVariant, $m2a
    ) {
        $indicators = ElcaIndicatorSet::findWithPetByProcessDbId(
            $ProjectVariant->getProject()->getProcessDbId()
        )->getArrayBy('ident', 'id');

        $totalValues = ElcaReportSet::findTotalEffects($ProjectVariant->getId())->getArrayBy('value', 'ident');

        $refConstrValues = ElcaBenchmarkRefConstructionValueSet::findByBenchmarkVersionId(
            $benchmarkVersion->getId()
        )->getArrayBy('value', 'indicatorId');

        $refOpValues     = ElcaReportSet::findFinalEnergyRefModelEffects($ProjectVariant->getId())->getArrayBy(
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
    // End computeRefModelValues


    /**
     * Calculates bnb benchmark for the given indicator values (indicatorIdent => value)
     * All values should be normalized by NGFa and life time
     *
     * @param ElcaBenchmarkVersion $benchmarkVersion
     * @param  array               $indicatorValues
     * @return array
     */
    private function computeIndicators(ElcaBenchmarkVersion $benchmarkVersion, array $indicatorValues)
    {
        $results    = [];
        $benchmarks = [];

        /**
         * Build benchmark cache
         */
        foreach ($indicatorValues as $ident => $value) {
            $indicatorIdent     = new IndicatorIdent($ident);
            $benchmarks[$ident] = new LinearScoreInterpolator(
                new NamedScoreThresholds(
                    $indicatorIdent,
                    ElcaBenchmarkThresholdSet::findByVersionIdAndIndicatorIdent($benchmarkVersion->getId(), $ident)
                                             ->getArrayBy('value', 'score')
                )
            );

            if (!$indicatorIdent->isRenewablePrimaryEnergy()) {
                $results[$ident] = $benchmarks[$ident]->computeScore($value);
            }
        }

        /**
         * Compute benchmark for renewable primary energy based on total primary energy
         */
        if ($benchmarkVersion->getProcessDb()->isEn15804Compliant()) {

            if (isset($benchmarks[ElcaIndicator::IDENT_PERE])) {
                $results[ElcaIndicator::IDENT_PERE] = $this->computeRenewablePrimaryEnergy(
                    $benchmarks[ElcaIndicator::IDENT_PERE],
                    $indicatorValues
                );
            }

            if (isset($benchmarks[ElcaIndicator::IDENT_PERM])) {
                $results[ElcaIndicator::IDENT_PERM] = $this->computeRenewablePrimaryEnergy(
                    $benchmarks[ElcaIndicator::IDENT_PERM],
                    $indicatorValues
                );
            }

        } else {

            if (isset($benchmarks[ElcaIndicator::IDENT_PE_EM])) {
                $results[ElcaIndicator::IDENT_PE_EM] = $this->computeRenewablePrimaryEnergy(
                    $benchmarks[ElcaIndicator::IDENT_PE_EM],
                    $indicatorValues
                );
            }
        }

        return $results;
    }
    // End computeIndicators


    /**
     * @param ElcaProjectVariant $projectVariant
     *
     * @return DataObjectSet
     */
    private function getConstrProjectionValues(ElcaProjectVariant $projectVariant)
    {
        $section = BenchmarksCtrl::SETTING_SECTION_PROJECTIONS.'.'.$projectVariant->getProject()->getBenchmarkVersionId();
        $settings = ElcaSettingSet::findBySection($section);

        $indicatorSet = ElcaIndicatorSet::findByProcessDbId($projectVariant->getProject()->getProcessDbId());

        $refConstrEffects = new DataObjectSet();
        foreach ($indicatorSet as $indicator) {
            $refConstrEffect                 = new \stdClass();
            $refConstrEffect->indicatorId    = $indicator->getId();
            $refConstrEffect->indicatorIdent = $indicator->getIdent();
            $refConstrEffect->min            = $refConstrEffect->avg = $refConstrEffect->max = null;
            $refConstrEffects->add($refConstrEffect);

            foreach (['min', 'avg', 'max'] as $property) {
                $ident = $property.'.'.$refConstrEffect->indicatorIdent;

                $setting = $settings->search('ident', $ident);
                if ($setting instanceof ElcaSetting) {
                    $refConstrEffect->$property = $setting->getNumericValue();
                }
            }
        }

        return $refConstrEffects;
    }


    /**
     * @param ElcaProjectVariant $ProjectVariant
     *
     * @return mixed
     */
    private function getM2aValue(ElcaProjectVariant $ProjectVariant)
    {
        $ProjectConstruction = $ProjectVariant->getProjectConstruction();
        $Project             = $ProjectVariant->getProject();

        /**
         * Normalize values by ngf and life time
         */
        return max(1, $Project->getLifeTime() * $ProjectConstruction->getNetFloorSpace());
    }
    // End getM2aValue


}
// End ElcaBenchmark
