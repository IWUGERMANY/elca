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

use Elca\Db\ElcaIndicator;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Processing\Benchmark\BnbFixedValuesBenchmarkCalculator;
use Elca\Model\Processing\Benchmark\NamedScoreThresholds;
use PHPUnit\Framework\TestCase;

class BnbFixedValuesBenchmarkCalculatorTest extends TestCase
{
    /**
     * @var NamedScoreThresholds[]
     */
    private $thresholds15804Compliant;

    /**
     * @var NamedScoreThresholds[]
     */
    private $thresholdsNon15804Compliant;

    /**
     * @param $indicatorValues
     * @param $results
     * @dataProvider simpleIndicatorValuesProvider
     */
    public function test_compute_simple_indicators($indicatorValues, $results)
    {
        $calculator = new BnbFixedValuesBenchmarkCalculator($this->thresholds15804Compliant);

        $this->assertEquals(
            $results,
            $calculator->compute(
                IndicatorValue::valuesFromMap($indicatorValues)
            )
        );
    }


    public function test_compute_primary_energy_non_15804_compliant()
    {
        $calculator = new BnbFixedValuesBenchmarkCalculator($this->thresholdsNon15804Compliant);

        $this->assertEquals(
            [
                'peEm' => 25,
                'pet'   => 50,
                'pe'    => 75,
            ],
            $calculator->compute(
                IndicatorValue::valuesFromMap(
                    [
                        'peEm' => 12.5,
                        'pet'  => 50,
                    ]
                )
            )
        );
    }

    public function test_compute_primary_energy_15804_compliant()
    {
        $calculator = new BnbFixedValuesBenchmarkCalculator($this->thresholds15804Compliant);

        $this->assertEquals(
            [
                'pert' => 20,
                'penrt' => 30,
                'pet'   => 40,
                'pe'    => 90,
            ],
            $calculator->compute(
                IndicatorValue::valuesFromMap(
                    [
                        'pert'  => 12.5,
                        'penrt' => 30,
                        'pet'   => 50,
                    ]
                )
            )
        );
    }

    public function test_compute_primary_energy_15804_compliant_incomplete()
    {
        $calculator = new BnbFixedValuesBenchmarkCalculator($this->thresholds15804Compliant);

        $this->assertEquals(
            [
                'pert' => 20,
                'pet'   => 40,
                'pe'    => 60,
            ],
            $calculator->compute(
                IndicatorValue::valuesFromMap(
                    [
                        'pert'  => 12.5,
                        'pet'   => 50,
                    ]
                )
            )
        );
    }

    public function simpleIndicatorValuesProvider()
    {
        return [
            'min_underrun' => [
                [
                    'gwp' => 5,
                    'odp' => 3,
                ],
                [
                    'gwp' => 10,
                    'odp' => 10,
                ],
            ],
            'min'          => [
                [
                    'gwp' => 10,
                    'odp' => 5,
                ],
                [
                    'gwp' => 10,
                    'odp' => 10,
                ],
            ],
            'interpolate'  => [
                [
                    'gwp' => 45,
                    'odp' => 15,
                ],
                [
                    'gwp' => 45,
                    'odp' => 30,
                ],
            ],
            'max'          => [
                [
                    'gwp' => 100,
                    'odp' => 50,
                ],
                [
                    'gwp' => 100,
                    'odp' => 100,
                ],
            ],
            'max_overrun'  => [
                [
                    'gwp' => 110,
                    'odp' => 60,
                ],
                [
                    'gwp' => 100,
                    'odp' => 100,
                ],
            ],
        ];
    }

    protected function setUp()
    {
        $indicatorScoreThresholds = [
            new NamedScoreThresholds(
                IndicatorIdent::GWP,
                [
                    10  => 10,
                    50  => 50,
                    100 => 100,
                ]
            ),
            new NamedScoreThresholds(
                IndicatorIdent::ODP,
                [
                    10  => 5,
                    50  => 25,
                    100 => 50,
                ]
            ),
            new NamedScoreThresholds(
                IndicatorIdent::ADP,
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
