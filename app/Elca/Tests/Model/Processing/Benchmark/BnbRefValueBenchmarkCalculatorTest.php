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

namespace Elca\Tests\Model\Processing\Benchmark;

use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Processing\Benchmark\BnbRefValueBenchmarkCalculator;
use Elca\Model\Processing\Benchmark\NamedScoreThresholds;
use PHPUnit\Framework\TestCase;

class BnbRefValueBenchmarkCalculatorTest extends TestCase
{
    /**
     * @var NamedScoreThresholds[]
     */
    private $thresholds15804Compliant;

    /**
     * @var NamedScoreThresholds[]
     */
    private $thresholdsNon15804Compliant;

    public function test_computation()
    {
        $calculator = new BnbRefValueBenchmarkCalculator(
            $this->thresholds15804Compliant,
            IndicatorValue::valuesFromMap([
                'gwp' => 2,
                'odp' => 2,
                'adp' => 2,
            ])
        );

        $this->assertEquals(
            [
                'gwp' =>  12.5,
                'odp' =>  25,
                'adp' =>  25,
            ],
            $calculator->compute(
                IndicatorValue::valuesFromMap([
                    'gwp' => 50,
                    'odp' => 50,
                    'adp' => 50,
                ]),
                IndicatorValue::valuesFromMap([
                    'gwp' => 2,
                    'odp' => 2,
                    'adp' => 2,
                ])
            )
        );
    }


    protected function setUp()
    {
        $indicatorScoreThresholds = [
            new NamedScoreThresholds(
                'gwp',
                [
                    10  => 10,
                    50  => 50,
                    100 => 100,
                ]
            ),
            new NamedScoreThresholds(
                'odp',
                [
                    10  => 5,
                    50  => 25,
                    100 => 50,
                ]
            ),
            new NamedScoreThresholds(
                'adp',
                [
                    10  => 5,
                    50  => 25,
                    100 => 50,
                ]
            ),
        ];


        $this->thresholds15804Compliant = array_merge($indicatorScoreThresholds, [
            new NamedScoreThresholds(
                'pet',
                [
                    4  => 4,
                    20 => 20,
                    40 => 40,
                ]
            ),
            new NamedScoreThresholds(
                'pert',
                [
                    2  => 2,
                    10 => 10,
                    20 => 20,
                ]
            ),
            new NamedScoreThresholds(
                'penrt',
                [
                    6  => 6,
                    30 => 30,
                    60 => 60,
                ]
            ),
        ]);

        $this->thresholdsNon15804Compliant = array_merge($indicatorScoreThresholds, [
            new NamedScoreThresholds(
                'pet',
                [
                    10  => 10,
                    50  => 50,
                    100 => 100,
                ]
            ),
            new NamedScoreThresholds(
                'peEm',
                [
                    5  => 5,
                    25 => 25,
                    50 => 50,
                ]
            ),
            new NamedScoreThresholds(
                'peNEm',
                [
                    10  => 10,
                    50  => 50,
                    100 => 10,
                ]
            ),
        ]);
    }
}
