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
use Elca\Model\ProcessConfig\Conversion\ConversionType;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\ProcessConversionsRepository;
use Elca\Model\ProcessConfig\Conversion\RequiredConversion;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessConversion;
use Elca\Model\ProcessConfig\ProcessLifeCycleId;
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

    /**
     * @var ProcessConversionsRepository|PHPUnit_Framework_MockObject_MockObject
     */
    private $processConversionsRepository;

    public function testFindRequiredConversions()
    {
        $processConfigId = new ProcessConfigId(1);
        $processDbId = new ProcessDbId(1);
        $this->processLifeCycleRepository
            ->method('findById')
            ->willReturn(
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        $processDbId,
                        [
                            Module::A13 => Unit::piece(),
                            Module::C3  => Unit::kg(),
                        ]
                    )
            );

        $requiredConversions = $this->conversions->findRequiredConversions(
            new ProcessLifeCycleId($processDbId, $processConfigId));

        $this->assertEquals(
            [
                new RequiredConversion(Unit::piece(), Unit::kg()),
            ],
            $requiredConversions->toArray()
        );
    }

    public function testFindAdditionalConversions()
    {
        $conversions = [
            new LinearConversion(Unit::piece(), Unit::kg(), 2),
            new LinearConversion(Unit::m3(), Unit::kg(), 3),
            new LinearConversion(Unit::m2(), Unit::kg(), 5),
        ];

        $processConfigId = new ProcessConfigId(1);
        $processDbId = new ProcessDbId(1);
        $this->processLifeCycleRepository
            ->method('findById')
            ->willReturn(
                    $this->given_a_process_life_cycle(
                        $processConfigId,
                        new ProcessDbId(1),
                        [
                            Module::A13 => Unit::m3(),
                            Module::C4  => Unit::kg(),
                        ],
                        $conversions
                    )
            );

        $additionalConversions = $this->conversions->findAdditionalConversions(
            new ProcessLifeCycleId($processDbId, $processConfigId));

        $this->assertEquals(
            [
                new LinearConversion(Unit::piece(), Unit::kg(), 2),
                new LinearConversion(Unit::m2(), Unit::kg(), 5),
            ],
            $additionalConversions->toArray()
        );
    }

    public function test_registerConversion_callsRepositoryAdd_whenNoConversionWasFound()
    {
        $this->processConversionsRepository
            ->method('findByConversion')
            ->willReturn(null);

        $this->processConversionsRepository->expects($this->once())->method('add');

        $this->conversions->registerConversion(
            new ProcessDbId(1),
            new ProcessConfigId(2),
            new LinearConversion(
                Unit::piece(), Unit::kg(), 10
            )
        );
    }

    public function test_registerConversion_callsRepositorySave_whenAConversionWasFound()
    {
        $processDbId     = new ProcessDbId(1);
        $processConfigId = new ProcessConfigId(2);
        $conversion      = new LinearConversion(Unit::piece(), Unit::kg(), 10);

        $processConversionId = new ProcessLifeCycleId($processDbId, $processConfigId);
        $processConversion   = new ProcessConversion($processDbId, $processConfigId, $conversion);

        $this->processConversionsRepository
            ->method('findByConversion')
            ->willReturn($processConversion);

        $this->processConversionsRepository->expects($this->once())->method('save');

        $this->conversions->registerConversion($processDbId, $processConfigId, $conversion);
    }

    public function test_findProductionConversions()
    {
        $conversions = [
            new ImportedLinearConversion(Unit::kg(), Unit::kg(), 1, ConversionType::production()),
            new LinearConversion(Unit::m3(), Unit::kg(), 3),
        ];

        $processConfigId = new ProcessConfigId(1);
        $processDbId = new ProcessDbId(1);
        $this->processLifeCycleRepository
            ->method('findById')
            ->willReturn(
                $this->given_a_process_life_cycle(
                    $processConfigId,
                    new ProcessDbId(1),
                    [
                        Module::A13 => Unit::kg(),
                        Module::C4  => Unit::kg(),
                    ],
                    $conversions
                )
            );

        $productionConversions = $this->conversions->findProductionConversions(
            new ProcessLifeCycleId($processDbId, $processConfigId));

        $this->assertEquals(
            [
                new ImportedLinearConversion(Unit::kg(), Unit::kg(), 1, ConversionType::production()),
                new LinearConversion(Unit::m3(), Unit::kg(), 3),
            ],
            $productionConversions->toArray()
        );

    }

    public function test_findProductionConversions_even_when_inversed()
    {
        $conversions = [
            new ImportedLinearConversion(Unit::m3(), Unit::m3(), 1, ConversionType::production()),
            new LinearConversion(Unit::m3(), Unit::kg(), 3),
            new LinearConversion(Unit::kg(), Unit::piece(), 1),
        ];

        $processConfigId = new ProcessConfigId(1);
        $processDbId = new ProcessDbId(1);
        $this->processLifeCycleRepository
            ->method('findById')
            ->willReturn(
                $this->given_a_process_life_cycle(
                    $processConfigId,
                    new ProcessDbId(1),
                    [
                        Module::A13 => Unit::m3(),
                        Module::C4  => Unit::m3(),
                    ],
                    $conversions
                )
            );

        $productionConversions = $this->conversions->findProductionConversions(
            new ProcessLifeCycleId($processDbId, $processConfigId));

        $this->assertEquals(
            [
                new ImportedLinearConversion(Unit::m3(), Unit::m3(), 1, ConversionType::production()),
                new LinearConversion(Unit::m3(), Unit::kg(), 3),
            ],
            $productionConversions->toArray()
        );

    }


    protected function setUp()
    {
        $this->processLifeCycleRepository   = $this->createMock(ProcessLifeCycleRepository::class);
        $this->processConversionsRepository = $this->createMock(ProcessConversionsRepository::class);

        $this->conversions = new Conversions($this->processLifeCycleRepository, $this->processConversionsRepository);
    }

    private function given_a_process_life_cycle(
        ProcessConfigId $processConfigId,
        ProcessDbId $processDbId,
        array $processesConf,
        array $conversions = []
    ) {
        $processes = [];
        foreach ($processesConf as $module => $unit) {
            $processes[] = $this->given_process(new Module($module), new Quantity(1, $unit));
        }

        return new ProcessLifeCycle(
            $processConfigId,
            $processDbId,
            $processes,
            $conversions
        );
    }

    private function given_process(Module $module, Quantity $quantitativeReference, ProcessId $processId = null)
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
