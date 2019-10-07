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

namespace Lcc\Service;

use Elca\Db\ElcaProjectConstruction;
use Elca\Model\Processing\Benchmark\LinearScoreInterpolator;
use Elca\Model\Processing\Benchmark\NamedScoreThresholds;
use Lcc\Db\LccBenchmarkGroupSet;
use Lcc\Db\LccBenchmarkGroupThresholdSet;
use Lcc\Db\LccBenchmarkThresholdSet;
use Lcc\Db\LccCost;
use Lcc\Db\LccProjectTotalSet;
use Lcc\Db\LccProjectVersion;
use Lcc\LccModule;

class BenchmarkService
{
    /**
     * Grouping captions
     *
     * @translate array Lcc\Service\BenchmarkService::$groupingCaptions
     */
    public static $groupingCaptions = ['costs' => 'Herstellkosten KG ',
                                       LccCost::GROUPING_WATER => 'Barwert Nutzungskosten Wasser/ Abwasser',
                                       LccCost::GROUPING_ENERGY => 'Barwert Nutzungskosten Energie',
                                       LccCost::GROUPING_CLEANING => 'Barwert Nutzungskosten Reinigung',
                                       LccCost::GROUPING_KGR => 'Barwert regelmäßige Instandhaltungskosten KG ',
                                       LccCost::GROUPING_KGU => 'Barwert unregelmäßige Zahlungen KG ',
                                       'total' => 'Barwert Gesamt',
                                       'totalPerBgf' => 'Lebenszykluskosten / m²BGF',
                                       'totalPoints' => 'Punkte',
    ];

    /**
     * @translate array Lcc\Service\BenchmarkService::$groupingShortNames
     */
    public static $groupingShortNames = ['costs' => 'KG ',
                                         LccCost::GROUPING_WATER => 'Wasser/ Abwasser',
                                         LccCost::GROUPING_ENERGY => 'Energie',
                                         LccCost::GROUPING_CLEANING => 'Reinigung',
                                         LccCost::GROUPING_KGR => 'Regelm. KG ',
                                         LccCost::GROUPING_KGU => 'Unregelm. KG'
    ];

    public function summary(LccProjectVersion $projectVersion, $benchmarkVersionId = null)
    {
        if (!$projectVersion->isInitialized()) {
            return [];
        }

        $projectConstruction = ElcaProjectConstruction::findByProjectVariantId($projectVersion->getProjectVariantId());
        $bgf = $projectConstruction->getGrossFloorSpace();

        $totals = LccProjectTotalSet::find([
            'project_variant_id' => $projectVersion->getProjectVariantId(),
            'calc_method' => $projectVersion->getCalcMethod()
        ])
                                    ->getArrayBy('costs', 'grouping');
        $data = [];
        $totalCosts = 0;

        // production costs
        if ($projectVersion->getCalcMethod() === LccModule::CALC_METHOD_GENERAL) {
            foreach ([300, 400, 500] as $code) {
                $property        = 'costs' . $code;
                $costs           = $projectVersion->$property;
                $data['costs'][] = (object)['name'      => self::$groupingCaptions['costs'] . $code,
                                            'costs'     => $costs,
                                            'unit'      => '€',
                                            'shortName' => self::$groupingShortNames['costs'] . $code
                ];
                $totalCosts += $costs;
            }
        }
        elseif ($projectVersion->getCalcMethod() === LccModule::CALC_METHOD_DETAILED) {
            $projectProdTotalSet = LccProjectTotalSet::findProductionTotals($projectVersion->getProjectVariantId(), LccModule::CALC_METHOD_DETAILED);
            $prodTotals = $projectProdTotalSet->getArrayBy('costs', 'grouping');

            foreach ([300, 400, 500] as $code) {
                $costs           = $prodTotals[LccCost::GROUPING_KGU.$code] ?? 0;
                $data['costs'][] = (object)['name'      => self::$groupingCaptions['costs'] . $code,
                                            'costs'     => $costs,
                                            'unit'      => '€',
                                            'shortName' => self::$groupingShortNames['costs'] . $code
                ];
                $totalCosts += $costs;
            }

        }

        // irregular service costs
        foreach([300,400,500] as $code)
        {
            $grouping = LccCost::GROUPING_KGU . $code;
            $costs = $totals[$grouping] ?? 0;
            $data['irregular'][] = (object)['name' => self::$groupingCaptions[LccCost::GROUPING_KGU] . $code, 'costs' => $costs, 'unit' => '€', 'shortName' => self::$groupingShortNames[LccCost::GROUPING_KGU] . $code];
            $totalCosts += $costs;
        }
        // regular service costs
        foreach([300,400,500] as $code)
        {
            $grouping = LccCost::GROUPING_KGR . $code;
            $costs = $totals[$grouping] ?? 0;
            $data['service'][] = (object)['name' => self::$groupingCaptions[LccCost::GROUPING_KGR] . $code, 'costs' => $costs, 'unit' => '€', 'shortName' => self::$groupingShortNames[LccCost::GROUPING_KGR] . $code];
            $totalCosts += $costs;
        }
        // regular operational costs
        foreach([LccCost::GROUPING_WATER, LccCost::GROUPING_ENERGY, LccCost::GROUPING_CLEANING] as $grouping)
        {
            $costs = $totals[$grouping] ?? 0;
            $data['regular'][] = (object)['name' => self::$groupingCaptions[$grouping], 'costs' => $costs, 'unit' => '€', 'shortName' => self::$groupingShortNames[$grouping]];
            $totalCosts += $costs;
        }
        // totals
        $data['total'] = [(object)['name' => self::$groupingCaptions['total'], 'costs' => $totalCosts, 'unit' => '€']];

        // add total costs per bgf
        $totalCostsPerBgf = $totalCosts / $bgf;

        $score = $this->computeScore($projectVersion, $totalCostsPerBgf, $benchmarkVersionId);
        $groupBenchmark = null !== $score ? $this->groupBenchmark($projectVersion, $score, $benchmarkVersionId) : null;

//        if (LccModule::CALC_METHOD_DETAILED === $projectVersion->getCalcMethod()) {
//            $points = FloatCalc::computeBenchmark([
//                100 => 3300,
//                50 => 4800,
//                10 => 6400,
//            ], $totalCostsPerBgf);
//        }
//        else {
//            // Punkte Kriterium // =WENN(D12=2;(5000-F268)/26;WENN(D12=1;(3800-F268)/18;"Kategorie!"))
//            $points = round(
//                $projectVersion->getCategory() == 2 ? (5000 - $totalCostsPerBgf) / 26 : (3800 - $totalCostsPerBgf) / 18
//            );
//        }

        if ($groupBenchmark) {
            $groupCaption = t(
                'Punkte Kriterium :group:',
                null,
                [':group:' => $groupBenchmark->name]
            );

            if ($groupBenchmark->caption) {
                $groupCaption .= sprintf(' (%s)', $groupBenchmark->caption);
            }
        }
        else {
            $groupCaption = self::$groupingCaptions['totalPoints'];
        }

        $data['rating'] = [
            (object)['name' => self::$groupingCaptions['totalPerBgf'], 'costs' => $totalCostsPerBgf, 'unit' => '€'],
            (object)['name' => $groupCaption, 'costs' => $score, 'unit' => ''],
        ];

        return $data;
    }

    public function computeScore(LccProjectVersion $projectVersion, $totalCostsPerBgf, int $benchmarkVersionId = null): ?float
    {
        $benchmarkVersionId = $projectVersion->getProjectVariant()->getProject()->getBenchmarkVersionId() ?? $benchmarkVersionId;

        $thresholds = LccBenchmarkThresholdSet::findByBenchmarkVersionIdAndCategory($benchmarkVersionId, $projectVersion->getCategory())
            ->getArrayBy('value', 'score');

        $namedThresholds = new NamedScoreThresholds((string)$projectVersion->getCategory(), $thresholds);
        $linearInterpolator = new LinearScoreInterpolator($namedThresholds);

        return $linearInterpolator->computeScore($totalCostsPerBgf);
    }

    public function groupBenchmark(LccProjectVersion $projectVersion, float $score, int $benchmarkVersionId = null)
    {
        $benchmarkVersionId = $projectVersion->getProjectVariant()->getProject()->getBenchmarkVersionId() ?? $benchmarkVersionId;

        $groups = LccBenchmarkGroupSet::findByBenchmarkVersionIdAndCategory($benchmarkVersionId, $projectVersion->getCategory())->getArrayCopy();

        if (0 === \count($groups)) {
            return [];
        }

        $group = current($groups);

        $groupResult = (object)[
            'name'    => $group->getName(),
            'caption' => '',
        ];

        $thresholds = LccBenchmarkGroupThresholdSet::findByGroupId($group->getId(), ['score' => 'ASC'])
                                                   ->getArrayBy('caption', 'score');

        foreach ($thresholds as $thresholdScore => $caption) {
            if ($score < $thresholdScore) {
                break;
            }

            $groupResult->caption = $caption;
        }

        return $groupResult;
    }
}
