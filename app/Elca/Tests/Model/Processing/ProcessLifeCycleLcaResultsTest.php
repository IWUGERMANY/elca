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

namespace Elca\Tests\Model\Processing;

use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\Stage;
use Elca\Model\Processing\IndicatorResult;
use Elca\Model\Processing\IndicatorResults;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Processing\ProcessLifeCycleLcaResults;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ProcessLifeCycleLcaResultsTest extends TestCase
{
    /**
     * @var ProcessLifeCycleLcaResults
     */
    private $lcaResults;

    /**
     * @var LifeCycleUsages
     */
    private $lifeCycleUsages;

    /**
     *
     */
    public function setUp()
    {
        $a1Usage = $this->getMockWithoutInvokingTheOriginalConstructor(LifeCycleUsage::class);
        $a1Usage->method('module')
                 ->willReturn(Module::a1());
        $a1Usage->method('applyInMaintenance')
                 ->willReturn(true);

        $a2Usage = $this->getMockWithoutInvokingTheOriginalConstructor(LifeCycleUsage::class);
        $a2Usage->method('module')
                 ->willReturn(Module::a2());
        $a2Usage->method('applyInMaintenance')
                 ->willReturn(true);

        $a3Usage = $this->getMockWithoutInvokingTheOriginalConstructor(LifeCycleUsage::class);
        $a3Usage->method('module')
                 ->willReturn(Module::a3());
        $a3Usage->method('applyInMaintenance')
                 ->willReturn(true);

        $a13Usage = $this->getMockWithoutInvokingTheOriginalConstructor(LifeCycleUsage::class);
        $a13Usage->method('module')
                 ->willReturn(Module::a13());
        $a13Usage->method('applyInMaintenance')
                 ->willReturn(true);

        $c3Usage = $this->getMockWithoutInvokingTheOriginalConstructor(LifeCycleUsage::class);
        $c3Usage->method('module')
                ->willReturn(Module::c3());
        $c3Usage->method('applyInMaintenance')
                ->willReturn(true);

        $dUsage = $this->getMockWithoutInvokingTheOriginalConstructor(LifeCycleUsage::class);
        $dUsage->method('module')
               ->willReturn(Module::d());
        $dUsage->method('applyInMaintenance')
               ->willReturn(false);

        $this->lifeCycleUsages = new LifeCycleUsages(
            [
                ElcaLifeCycle::IDENT_A1 => $a1Usage,
                ElcaLifeCycle::IDENT_A2 => $a2Usage,
                ElcaLifeCycle::IDENT_A3 => $a3Usage,
                ElcaLifeCycle::IDENT_A13 => $a13Usage,
                ElcaLifeCycle::IDENT_C3  => $c3Usage,
                ElcaLifeCycle::IDENT_D   => $dUsage,
            ]
        );

        $this->lcaResults = new ProcessLifeCycleLcaResults(new Quantity(1, Unit::piece()), 2);
        $this->lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::a13(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(1234, Uuid::uuid4()),
                1
            )
        );

        $this->lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::c3(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(5678, Uuid::uuid4()),
                .5
            )
        );

        $this->lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::d(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(9012, Uuid::uuid4()),
                1
            )
        );
    }


    public function test_initial_state()
    {
        $this->assertSame(1, $this->lcaResults->quantity()->value());
        $this->assertSame(Unit::PIECE, (string)$this->lcaResults->quantity()->unit());
        $this->assertSame(2.0, $this->lcaResults->mass());

        $this->assertEquals(
            [
                ElcaLifeCycle::PHASE_PROD => [
                    ElcaLifeCycle::IDENT_A13 => [
                        1234 => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                ],
                ElcaLifeCycle::PHASE_EOL  => [
                    ElcaLifeCycle::IDENT_C3 => [
                        5678 => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                ],
                ElcaLifeCycle::PHASE_REC  => [
                    ElcaLifeCycle::IDENT_D => [
                        9012 => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                ],
            ],
            $this->indicatorResultsToArray($this->lcaResults->indicatorResults())
        );
        $this->assertEquals(
            [
                1234 => 1,
                5678 => 0.5,
                9012 => 1,
            ],
            \iterator_to_array($this->lcaResults->processModuleRatios())
        );

        $this->assertFalse($this->lcaResults->a13HasBeenAggregated());
    }

    public function test_hasProcess_returns_true()
    {
        $this->assertTrue($this->lcaResults->hasProcess(new ProcessId(1234)));
    }

    public function test_hasProcess_returns_false()
    {
        $this->assertFalse($this->lcaResults->hasProcess(new ProcessId(12345)));
    }

    public function test_processRatio_returns_process_value()
    {
        $this->assertSame(1.0, $this->lcaResults->moduleRatioFor(new ProcessId(1234)));
        $this->assertSame(0.5, $this->lcaResults->moduleRatioFor(new ProcessId(5678)));
    }

    public function test_aggregateMaintenance_called_with_no_replacements_returns_zero_values()
    {
        $this->lcaResults->aggregateMaintenance(0, $this->lifeCycleUsages);

        $results = $this->indicatorResultsToArray($this->lcaResults->indicatorResults());

        $this->assertArrayHasKey(Stage::MAINT, $results);
        $this->assertEquals(
            [
                Stage::MAINT => [
                    null => [
                        1 => 0,
                        2 => 0,
                    ],
                ],
            ],
            $results[Stage::MAINT ]
        );
    }

    public function test_aggregateMaintenance_adds_A13_and_C3_indicators_but_ignores_D()
    {
        $this->lcaResults->aggregateMaintenance(1, $this->lifeCycleUsages);

        $results = $this->indicatorResultsToArray($this->lcaResults->indicatorResults());

        $this->assertArrayHasKey(ElcaLifeCycle::PHASE_MAINT, $results);
        $this->assertEquals(
            [
                Stage::MAINT => [
                    null => [
                        1 => 23 + 23,
                        2 => 12 + 12,
                    ],
                ],
            ],
            $results[Stage::MAINT]
        );
    }

    public function test_aggregateMaintenance_aggregates_according_to_numReplacements()
    {
        $numReplacements = 1;
        $this->lcaResults->aggregateMaintenance($numReplacements, $this->lifeCycleUsages);

        $results = $this->indicatorResultsToArray($this->lcaResults->indicatorResults());

        $this->assertArrayHasKey(ElcaLifeCycle::PHASE_MAINT, $results);
        $this->assertEquals(
            [
                Stage::MAINT => [
                    null => [
                        1 => $numReplacements * (23 + 23),
                        2 => $numReplacements * (12 + 12),
                    ],
                ],
            ],
            $results[Stage::MAINT]
        );
    }

    public function test_aggregateMaintenance_aggregates_does_not_aggregate_a13_twice_when_single_a1_a2_or_a3_are_given()
    {
        $numReplacements = 1;

        $lcaResults = $this->given_lcaResults_with_single_and_aggregated_a13();
        $lcaResults->aggregateMaintenance($numReplacements, $this->lifeCycleUsages);

        $results = $this->indicatorResultsToArray($lcaResults->indicatorResults());

        $this->assertArrayHasKey(ElcaLifeCycle::PHASE_MAINT, $results);
        $this->assertEquals(
            [
                Stage::MAINT => [
                    null => [
                        1 => $numReplacements * (23 + 23 + 23), // values from a1, a2 and a3
                        2 => $numReplacements * (12 + 12 + 12), // values from a1, a2 and a3
                    ],
                ],
            ],
            $results[Stage::MAINT]
        );
    }

    public function test_addProcessIndicatorResults_aggregates_a1_into_a13()
    {
        $lcaResults = new ProcessLifeCycleLcaResults(new Quantity(1, Unit::piece()), 2);
        $lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::a1(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(1234, Uuid::uuid4()),
                1
            )
        );

        $this->assertEquals(
            [
                ElcaLifeCycle::PHASE_PROD => [
                    ElcaLifeCycle::IDENT_A13 => [
                        '1234' => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                    ElcaLifeCycle::IDENT_A1 => [
                        '1234' => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                ],
            ],
            $this->indicatorResultsToArray($lcaResults->indicatorResults())
        );

        $this->assertTrue($lcaResults->a13HasBeenAggregated());
    }

    public function test_addProcessIndicatorResults_aggregates_a1_and_a2_into_a13()
    {
        $lcaResults = new ProcessLifeCycleLcaResults(new Quantity(1, Unit::piece()), 2);
        $lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::a1(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(1234, Uuid::uuid4()),
                1
            )
        );
        $lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::a2(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(1235, Uuid::uuid4()),
                1
            )
        );

        $this->assertEquals(
            [
                ElcaLifeCycle::PHASE_PROD => [
                    ElcaLifeCycle::IDENT_A13 => [
                        '1234' => [
                            1 => 46,
                            2 => 24,
                        ],
                    ],
                    ElcaLifeCycle::IDENT_A1 => [
                        '1234' => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                    ElcaLifeCycle::IDENT_A2 => [
                        '1235' => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                ],
            ],
            $this->indicatorResultsToArray($lcaResults->indicatorResults())
        );

        $this->assertTrue($lcaResults->a13HasBeenAggregated());
    }

    public function test_addProcessIndicatorResults_aggregates_a1_a2_a3_into_a13()
    {
        $lcaResults = $this->given_lcaResults_with_single_and_aggregated_a13();

        $this->assertEquals(
            [
                ElcaLifeCycle::PHASE_PROD => [
                    ElcaLifeCycle::IDENT_A13 => [
                        '1234' => [
                            1 => 69,
                            2 => 36,
                        ],
                    ],
                    ElcaLifeCycle::IDENT_A1 => [
                        '1234' => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                    ElcaLifeCycle::IDENT_A2 => [
                        '1235' => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                    ElcaLifeCycle::IDENT_A3 => [
                        '1236' => [
                            1 => 23,
                            2 => 12,
                        ],
                    ],
                ],
            ],
            $this->indicatorResultsToArray($lcaResults->indicatorResults())
        );

        $this->assertTrue($lcaResults->a13HasBeenAggregated());
    }

    private function getMockWithoutInvokingTheOriginalConstructor($class)
    {
        return $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param IndicatorResults[] $indicatorResultsSet
     * @return array
     */
    private function indicatorResultsToArray(array $indicatorResultsSet): array
    {
        $mapped = [];

        foreach ($indicatorResultsSet as $indicatorResults) {
            $stage     = (string)$indicatorResults->module()->stage();
            $module    = (string)$indicatorResults->module();
            $processId = $indicatorResults->hasProcessId()
                ? $indicatorResults->processId()->value()
                : null;

            $mapped[$stage][$module][$processId] = IndicatorResult::valuesToMap($indicatorResults);
        }

        return $mapped;
    }

    private function given_lcaResults_with_single_and_aggregated_a13(): ProcessLifeCycleLcaResults
    {
        $lcaResults = new ProcessLifeCycleLcaResults(new Quantity(1, Unit::piece()), 2);
        $lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::a1(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(1234, Uuid::uuid4()),
                1
            )
        );
        $lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::a2(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(1235, Uuid::uuid4()),
                1
            )
        );

        $lcaResults->addProcessIndicatorResults(
            new IndicatorResults(
                Module::a3(),
                IndicatorResult::valuesFromMap(
                    [
                        1 => 23,
                        2 => 12,
                    ]
                ),
                new ProcessId(1236, Uuid::uuid4()),
                1
            )
        );

        return $lcaResults;
    }
}
