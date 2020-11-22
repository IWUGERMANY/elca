<?php
/**
 *  This file is part of the eLCA project
 *
 *  eLCA
 *  A web based life cycle assessment application
 *
 *  Copyright (c) 2017 Tobias Lode <tobias@beibob.de>
 *                BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 *  eLCA is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  eLCA is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Elca\Tests\Model\ProcessConfig\LifeCycle;

use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\ProcessName;
use Elca\Model\Process\Stage;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\RequiredConversion;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\ProcessConfigId;
use PHPUnit\Framework\TestCase;

class ProcessLifeCycleTest extends TestCase
{
    /**
     * @var ProcessDbId
     */
    private $processDbId;

    public function setUp()
    {
        $this->processDbId = new ProcessDbId(1);
    }

    public function test_units_return_all_distinct_units()
    {
        $processes = $this->setupProcesses([
            ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
            ['module' => Module::B6, 'refUnit' => Unit::KILOWATTHOUR],
            ['module' => Module::C3, 'refUnit' => Unit::KILOGRAMM],
            ['module' => Module::D, 'refUnit' => Unit::CUBIC_METER],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame(
            [
                Unit::KILOGRAMM,
                Unit::KILOWATTHOUR,
                Unit::CUBIC_METER,

            ],
            array_keys($processLifeCycle->units())
        );
    }


    /**
     * @dataProvider requiredUnitsProvider
     */
    public function test_requiredUnits(array $processData, array $requiredUnits)
    {
        $processes = $this->setupProcesses($processData);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame($requiredUnits, array_keys($processLifeCycle->requiredUnits()));
    }

    /**
     * @dataProvider requiredConversionsProvider
     */
    public function test_requiredConversions(array $processData, array $requiredConversions)
    {
        $processes = $this->setupProcesses($processData);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame(
            $requiredConversions,
            array_map(
                function (RequiredConversion $conversion) {
                    return [(string)$conversion->fromUnit(), (string)$conversion->toUnit()];
                },
                $processLifeCycle->requiredConversions()
            )
        );
    }

    public function requiredUnitsProvider()
    {
        return [
            'identity'             => [
                [
                    ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
                    ['module' => Module::C3, 'refUnit' => Unit::KILOGRAMM],
                ],
                [Unit::KILOGRAMM],
            ],
            'kg, Stk'              => [
                [
                    ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
                    ['module' => Module::C3, 'refUnit' => Unit::PIECE],
                ],
                [Unit::KILOGRAMM, Unit::PIECE],
            ],
            'kg, Stk, m3'          => [
                [
                    ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
                    ['module' => Module::C3, 'refUnit' => Unit::PIECE],
                    ['module' => Module::D, 'refUnit' => Unit::CUBIC_METER],
                ],
                [Unit::KILOGRAMM, Unit::PIECE, Unit::CUBIC_METER],
            ],
            'ignoring usage stage' => [
                [
                    ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
                    ['module' => Module::B6, 'refUnit' => Unit::PIECE],
                    ['module' => Module::C3, 'refUnit' => Unit::KILOGRAMM],
                ],
                [Unit::KILOGRAMM],
            ],
        ];
    }

    public function requiredConversionsProvider()
    {
        return [
            'empty'  => [
                [
                    ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
                    ['module' => Module::C3, 'refUnit' => Unit::KILOGRAMM],
                ],
                [
                ]
            ],
            'kg, Stk'     => [
                [
                    ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
                    ['module' => Module::C3, 'refUnit' => Unit::PIECE],
                ],
                [
                    [Unit::PIECE, Unit::KILOGRAMM],
                ],
            ],
            'kg, Stk, m3' => [
                [
                    ['module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
                    ['module' => Module::C3, 'refUnit' => Unit::PIECE],
                    ['module' => Module::D, 'refUnit' => Unit::CUBIC_METER],
                ],
                [
                    [Unit::PIECE, Unit::KILOGRAMM],
                    [Unit::CUBIC_METER, Unit::KILOGRAMM],
                ],
            ],
        ];
    }

    public function test_requiredConversions_returns_required_conversion_instance()
    {
        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            [
                $this->given_a_process(
                    Module::a13(),
                    new Quantity(1, Unit::m3())
                ),
                $this->given_a_process(
                    Module::c4(),
                    new Quantity(1, Unit::kg())
                ),
            ]
        );

        $this->assertContainsOnlyInstancesOf(RequiredConversion::class, $processLifeCycle->requiredConversions());
    }

    public function test_requiredConversions_returns_linear_conversion_instance()
    {
        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            [
                $this->given_a_process(
                    Module::a13(),
                    new Quantity(1, Unit::m3())
                ),
                $this->given_a_process(
                    Module::c4(),
                    new Quantity(1, Unit::kg())
                ),
            ],
            [
                new LinearConversion(Unit::kg(), Unit::m3(), 1.2)
            ]
        );

        $conversion = current($processLifeCycle->requiredConversions());
        $this->assertInstanceOf(LinearConversion::class, $conversion);
        $this->assertSame(1 / 1.2, $conversion->factor());
    }

    /**
     * @dataProvider knownConversionsDataProvider
     */
    public function test_requiredConversions_prefers_known_conversion_types($prodUnit, $eolUnit, array $existingConversions, Conversion $resultConversion)
    {
        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            [
                $this->given_a_process(
                    Module::a13(),
                    new Quantity(1, $prodUnit)
                ),
                $this->given_a_process(
                    Module::c4(),
                    new Quantity(1, $eolUnit)
                ),
            ],
            $existingConversions
        );

        $conversion = current($processLifeCycle->requiredConversions());
        $this->assertTrue($conversion->equals($resultConversion));
        $this->assertTrue($conversion->type()->isKnown());
    }

    public function knownConversionsDataProvider(): array
    {
        return [
            'asIs' => [Unit::m3(), Unit::kg(), [], new RequiredConversion(Unit::m3(), Unit::kg())],
            'inverted' => [Unit::kg(), Unit::m3(), [], new RequiredConversion(Unit::m3(), Unit::kg())],
            'existing' => [Unit::kg(), Unit::m3(), [new LinearConversion(Unit::m3(), Unit::kg(), 1)], new LinearConversion(Unit::m3(), Unit::kg(), 1)],
        ];
    }

    public function test_usageProcesses_returns_only_usage_processes()
    {
        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            [
                $this->given_a_process(
                    Module::a13(),
                    Quantity::inKg(1)
                ),
                $this->given_a_process(
                    Module::b6(),
                    Quantity::inKWh(1)
                ),
                $this->given_a_process(
                    Module::c4(),
                    Quantity::inKg(1)
                ),
            ],
            []
        );

        $this->assertContainsOnlyInstancesOf(Process::class, $processLifeCycle->usageProcesses());
        $this->assertSame(
            [Module::B6],
            array_map(
                function (Process $process) {
                    return (string)$process->module();
                },
                array_values($processLifeCycle->usageProcesses())
            )
        );
    }

    public function test_findByProcessId_returns_process_with_that_id()
    {
        $processDefinitions = [
            ['id' => 123, 'module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 234, 'module' => Module::B6, 'refUnit' => Unit::KILOWATTHOUR],
            ['id' => 345, 'module' => Module::C3, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 456, 'module' => Module::D, 'refUnit' => Unit::CUBIC_METER],
        ];

        $processes = $this->setupProcesses($processDefinitions);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        foreach ($processDefinitions as $conf) {
            $process = $processLifeCycle->findProcessById(new ProcessId($conf['id']));

            $this->assertNotNull($process);
            $this->assertSame($conf['id'], $process->id()->value());
            $this->assertSame($conf['module'], $process->module()->value());
            $this->assertSame($conf['refUnit'], $process->quantitativeReference()->unit()->value());
        }
    }

    public function test_findByProcessId_returns_null()
    {
        $processes = $this->setupProcesses([
            ['id' => 123, 'module' => Module::A13, 'refUnit' => Unit::KILOGRAMM],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertNull($processLifeCycle->findProcessById(new ProcessId(456)));
    }

    public function test_quantitativeReference_returns_quantity_from_a13_process()
    {
        $processes = $this->setupProcesses([
            ['id' => 123, 'module' => Module::A13, 'refValue' => 2, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 124, 'module' => Module::C3, 'refValue' => 1, 'refUnit' => Unit::CUBIC_METER],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame(
            '2 kg',
            (string)$processLifeCycle->quantitativeReference()
        );
    }

    public function test_quantitativeReference_returns_quantity_from_a1_process()
    {
        $processes = $this->setupProcesses([
            ['id' => 123, 'module' => Module::A1, 'refValue' => 2, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 124, 'module' => Module::A2, 'refValue' => 2, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 125, 'module' => Module::A3, 'refValue' => 2, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 126, 'module' => Module::C3, 'refValue' => 1, 'refUnit' => Unit::CUBIC_METER],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame(
            '2 kg',
            (string)$processLifeCycle->quantitativeReference()
        );
    }

    public function test_quantitativeReference_returns_quantity_from_prod_process_by_default()
    {
        $processes = $this->setupProcesses([
            ['id' => 123, 'module' => Module::A13, 'refValue' => 2, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 126, 'module' => Module::B6, 'refValue' => 1, 'refUnit' => Unit::KILOWATTHOUR],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame(
            '2 kg',
            (string)$processLifeCycle->quantitativeReference()
        );
    }

    public function test_quantitativeReference_returns_quantity_from_usage_process_when_requested()
    {
        $processes = $this->setupProcesses([
            ['id' => 123, 'module' => Module::A13, 'refValue' => 2, 'refUnit' => Unit::KILOGRAMM],
            ['id' => 126, 'module' => Module::B6, 'refValue' => 1, 'refUnit' => Unit::KILOWATTHOUR],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame(
            '1 kWh',
            (string)$processLifeCycle->quantitativeReference(Stage::usage())
        );
    }

    public function test_quantitativeReference_returns_quantity_from_b6_process_when_prod_is_not_available()
    {
        $processes = $this->setupProcesses([
            ['id' => 126, 'module' => Module::B6, 'refValue' => 1, 'refUnit' => Unit::KILOWATTHOUR],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertSame(
            '1 kWh',
            (string)$processLifeCycle->quantitativeReference()
        );
    }



    public function test_quantitativeReference_returns_null_when_no_production_prods_available_and_explicit_requested()
    {
        $processes = $this->setupProcesses([
            ['id' => 126, 'module' => Module::B6, 'refValue' => 1, 'refUnit' => Unit::KILOWATTHOUR],
        ]);

        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            $processes
        );

        $this->assertNull(
            $processLifeCycle->quantitativeReference(Stage::production())
        );
    }

    public function test_additionalConversions_returns_non_required_conversions()
    {
        $processLifeCycle = new ProcessLifeCycle(
            new ProcessConfigId(1),
            $this->processDbId,
            [
                $this->given_a_process(
                    Module::a13(),
                    new Quantity(1, Unit::kg())
                ),
                $this->given_a_process(
                    Module::c4(),
                    new Quantity(1, Unit::kg())
                ),
            ],
            [
                new LinearConversion(Unit::m3(), Unit::kg(), 200),
            ]
        );

        $conversion = current($processLifeCycle->additionalConversions());
        $this->assertInstanceOf(LinearConversion::class, $conversion);
        $this->assertSame(200.0, $conversion->factor());
    }

    protected function setupProcesses(array $processData): array
    {
        $processes = [];
        foreach ($processData as $processDatum) {
            $processes[] = $this->given_a_process(
                new Module($processDatum['module']),
                new Quantity($processDatum['refValue'] ?? 1, new Unit($processDatum['refUnit'])),
                isset($processDatum['id']) ? new ProcessId($processDatum['id']) : null
            );
        }

        return $processes;
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
