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

namespace Elca\Tests\Service;

use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\ProcessName;
use Elca\Model\ProcessConfig\Conversion\ConversionSet;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\RecommendedConversion;
use Elca\Model\ProcessConfig\Conversion\RequiredConversion;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Service\ProcessConfig\Conversions;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class ConversionsTest extends TestCase
{
    /**
     * @var Conversions|PHPUnit_Framework_MockObject_MockObject
     */
    private $conversions;

    /**
     * @var ProcessLifeCycleRepository|PHPUnit_Framework_MockObject_MockObject
     */
    private $processLifeCycleRepository;

    public function testFindAllRequiredConversions()
    {
        $conversions = [
            new LinearConversion(Unit::piece(), Unit::kg(), 1),
        ];
        $processConfigId = new ProcessConfigId(1);
        $this->processLifeCycleRepository
            ->method('findAllByProcessConfigId')
            ->willReturn(
                [
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(1),
                        [
                            Module::A13 => Unit::kg(),
                            Module::C3  => Unit::kg(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(2),
                        [
                            Module::A13 => Unit::m3(),
                            Module::C4  => Unit::kg(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(3),
                        [
                            Module::A13 => Unit::kg(),
                            Module::C3  => Unit::m3(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(4),
                        [
                            Module::A13 => Unit::piece(),
                            Module::C3  => Unit::kg(),
                        ],
                        $conversions
                    ),
                ]
            );

        $requiredConversions = $this->conversions->findAllRequiredConversions($processConfigId);

        $this->assertEquals(
            [
                new RequiredConversion(Unit::m3(), Unit::kg()),
                new LinearConversion(Unit::piece(), Unit::kg(), 1),
            ],
            $requiredConversions->toArray()
        );
    }

    public function testFindAllAdditionalConversions()
    {
        $conversions = [
            new LinearConversion(Unit::piece(), Unit::kg(), 2),
            new LinearConversion(Unit::m3(), Unit::kg(), 3),
            new LinearConversion(Unit::m2(), Unit::kg(), 5),
        ];

        $processConfigId = new ProcessConfigId(1);
        $this->processLifeCycleRepository
            ->method('findAllByProcessConfigId')
            ->willReturn(
                [
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(1),
                        [
                            Module::A13 => Unit::piece(),
                            Module::C3  => Unit::kg(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(2),
                        [
                            Module::A13 => Unit::m3(),
                            Module::C4  => Unit::kg(),
                        ],
                        $conversions
                    ),
                ]
            );

        $additionalConversions = $this->conversions->findAllAdditionalConversions($processConfigId);

        $this->assertEquals(
            [
                new LinearConversion(Unit::m2(), Unit::kg(), 5),
            ],
            $additionalConversions->toArray()
        );
    }

    public function test_findRecommendedConversions_returns_empty_set()
    {
        $conversions = [
            new LinearConversion(Unit::m3(), Unit::kg(), 5),
            new LinearConversion(Unit::m2(), Unit::kg(), 5),
        ];

        $processConfigId = new ProcessConfigId(1);
        $this->processLifeCycleRepository
            ->method('findAllByProcessConfigId')
            ->willReturn(
                [
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(1),
                        [
                            Module::A13 => Unit::kg(),
                            Module::C3  => Unit::m3(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(2),
                        [
                            Module::A13 => Unit::kg(),
                            Module::C4  => Unit::m2(),
                        ],
                        $conversions
                    ),
                ]
            );

        $recommendedConversions = $this->conversions->findRecommendedConversions($processConfigId);

        $this->assertEmpty($recommendedConversions->toArray());
    }

    public function test_findRecommendedConversions_without_additionalConversions()
    {
        $conversions = [
            new LinearConversion(Unit::piece(), Unit::kg(), 5),
            new LinearConversion(Unit::m3(), Unit::kg(), 3),
        ];

        $processConfigId = new ProcessConfigId(1);
        $this->processLifeCycleRepository
            ->method('findAllByProcessConfigId')
            ->willReturn(
                [
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(1),
                        [
                            Module::A13 => Unit::piece(),
                            Module::C3  => Unit::kg(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(2),
                        [
                            Module::A13 => Unit::m3(),
                            Module::C4  => Unit::kg(),
                        ],
                        $conversions
                    ),
                ]
            );

        $recommendedConversions = $this->conversions->findRecommendedConversions($processConfigId);

        $this->assertEquals(
            [
                new RecommendedConversion(Unit::piece(), Unit::m3()),
            ],
            $recommendedConversions->toArray()
        );
    }

    public function test_findRecommendedConversions_with_additionalConversions()
    {
        $conversions = [
            new LinearConversion(Unit::piece(), Unit::kg(), 5),
            new LinearConversion(Unit::m3(), Unit::kg(), 3),
            new LinearConversion(Unit::piece(), Unit::m3(), 5),
        ];

        $processConfigId = new ProcessConfigId(1);
        $this->processLifeCycleRepository
            ->method('findAllByProcessConfigId')
            ->willReturn(
                [
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(1),
                        [
                            Module::A13 => Unit::piece(),
                            Module::C3  => Unit::kg(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(2),
                        [
                            Module::A13 => Unit::m3(),
                            Module::C4  => Unit::kg(),
                        ],
                        $conversions
                    ),
                ]
            );

        $recommendedConversions = $this->conversions->findRecommendedConversions($processConfigId);

        $this->assertEmpty($recommendedConversions->toArray());
    }

    public function test_findRecommendedConversions_with_inverted_existing_conversion()
    {
        $conversions = [
            new LinearConversion(Unit::m3(), Unit::kg(), 3),
        ];

        $processConfigId = new ProcessConfigId(1);
        $this->processLifeCycleRepository
            ->method('findAllByProcessConfigId')
            ->willReturn(
                [
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(1),
                        [
                            Module::A13 => Unit::kg(),
                            Module::C3  => Unit::kg(),
                        ],
                        $conversions
                    ),
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(2),
                        [
                            Module::A13 => Unit::m3(),
                            Module::C4  => Unit::kg(),
                        ],
                        $conversions
                    ),
                ]
            );

        $recommendedConversions = $this->conversions->findRecommendedConversions($processConfigId);

        $this->assertEmpty($recommendedConversions->toArray());
    }

    protected function setUp()
    {
        $this->processLifeCycleRepository = $this->createMock(ProcessLifeCycleRepository::class);

        $this->conversions = new Conversions($this->processLifeCycleRepository);
    }

    private function given_a_process_life_cycle(
        ProcessConfigId $processConfigId, ProcessDbId $processDbId, array $processesConf, array $conversions = []
    ) {
        $processes = [];
        foreach ($processesConf as $module => $unit) {
            $processes[] = $this->given_a_process(new Module($module), new Quantity(1, $unit));
        }

        return new ProcessLifeCycle(
            $processConfigId,
            $processDbId,
            $processes,
            $conversions
        );
    }

    private function given_a_process(Module $module, Quantity $quantitativeReference, ProcessId $processId = null)
    {
        static $processIdCounter = 0;

        return new Process(
            $processId ?? new ProcessId(++$processIdCounter),
            $module,
            $quantitativeReference,
            new ProcessName('blurb')
        );
    }
}
