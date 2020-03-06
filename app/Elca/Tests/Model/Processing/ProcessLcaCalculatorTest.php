<?php

namespace Elca\Tests\Model\Processing;

use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorId;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Process\ProcessId;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Converter;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\Processing\IndicatorResult;
use Elca\Model\Processing\ProcessLcaCalculator;
use Elca\Model\Processing\ProcessNotFoundInLifeCycleException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class ProcessLcaCalculatorTest extends TestCase
{
    /**
     * @var Process|PHPUnit_Framework_MockObject_MockObject
     */
    private $process;

    /**
     * @var ProcessLcaCalculator
     */
    private $calculator;

    /**
     * @var ProcessLifeCycle|PHPUnit_Framework_MockObject_MockObject
     */
    private $processLifeCycle;

    /**
     * @var Indicator[]
     */
    private $indicators;

    public function test_compute_throws_exception_if_given_process_is_not_contained_in_process_life_cycle()
    {
        $this->processLifeCycle
            ->method('findProcessById')
            ->willReturn(null);

        $this->expectException(ProcessNotFoundInLifeCycleException::class);

        $this->calculator->compute(
            $this->process,
            new Quantity(1, Unit::kg())
        );
    }

    /**
     * @dataProvider computationsProvider
     */
    public function test_compute_returns_indicator_values(
        Quantity $inQuantity, Quantity $processQuantity, array $conversions, array $processIndicatorValues,
        array $resultValues
    ) {
        $this->processLifeCycle
            ->method('findProcessById')
            ->willReturn($this->process);

        $this->processLifeCycle
            ->method('converter')
            ->willReturn(
                new Converter(
                    new ProcessConfigId(1),
                    $conversions
                )
            );

        $this->process
            ->method('quantitativeReference')
            ->willReturn($processQuantity);

        $this->process
            ->method('moduleRatio')
            ->willReturn(1);

        $this->process
            ->method('indicatorValueFor')
            ->will(
                $this->returnCallback(
                    function (IndicatorIdent $ident) use ($processIndicatorValues) {
                        return new IndicatorValue($ident, $processIndicatorValues[(string)$ident] ?? null);
                    }
                )
            );

        $this->assertArraySubset(
            $resultValues,
            IndicatorResult::valuesToMap(
                $this->calculator->compute(
                    $this->process,
                    $inQuantity
                )
            )
        );
    }

    public function computationsProvider()
    {
        return [
            'identity_conversion_results_are_identical' => [
                new Quantity(1, Unit::m2()),
                new Quantity(1, Unit::m2()),
                [],
                [
                    'gwp'   => 5.837842,
                    'odp'   => 0.000906685727193583,
                    'pert'  => 95040,
                    'penrt' => 100817.944141264,
                ],
                [
                    9  => 5.837842,
                    13 => 0.000906685727193583,
                    16 => 95040.0,
                    19 => 100817.944141264,
                ],
            ],
            'identity_conversion_pet_adds_up'           => [
                new Quantity(1, Unit::m2()),
                new Quantity(1, Unit::m2()),
                [],
                [
                    'gwp'   => 5.837842,
                    'odp'   => 0.000906685727193583,
                    'pert'  => 95040,
                    'penrt' => 100817.944141264,
                ],
                [
                    34 => 95040 + 100817.944141264,
                ],
            ],
            'unit_conversion_results_are_identical'    => [
                new Quantity(1, Unit::piece()),
                new Quantity(1, Unit::m2()),
                [
                    new LinearConversion(Unit::m2(), Unit::piece(), 1),
                ],
                [
                    'gwp'   => 5.837842,
                    'odp'   => -0.000906685727193583,
                    'pert'  => -95040,
                    'penrt' => 100817.944141264,
                ],
                [
                    9  => 5.837842,
                    13 => -0.000906685727193583,
                    16 => -95040.0,
                    19 => 100817.944141264,
                    34 => -95040 + 100817.944141264,
                ],
            ],

            'unit_conversion_results_are_converted' => [
                new Quantity(1, Unit::piece()),
                new Quantity(1, Unit::m2()),
                [
                    new LinearConversion(Unit::piece(), Unit::m2(), 10.121),
                ],
                [
                    'gwp'   => 5.837842,
                    'odp'   => 0.000906685727193583,
                    'pert'  => 95040,
                    'penrt' => 100817.944141264,
                ],
                [
                    9  => 5.837842 * 10.121,
                    13 => 0.000906685727193583 * 10.121,
                    16 => 95040.0 * 10.121,
                    19 => 100817.944141264 * 10.121,
                    34 => (95040.0 + 100817.944141264) * 10.121,
                ],
            ],
            'ref_unit_conversion'                   => [
                new Quantity(1, Unit::piece()),
                new Quantity(100, Unit::m2()),
                [
                    new LinearConversion(Unit::piece(), Unit::m2(), 1),
                ],
                [
                    'gwp' => 5.84,
                ],
                [
                    9 => 0.0584,
                ],
            ],

            'with small values' => [
                new Quantity(0.1, Unit::m3()),
                new Quantity(1, Unit::m3()),
                [
                    new LinearConversion(Unit::m3(), Unit::kg(), 680.8),
                ],
                [
                    'gwp'  => 0.000000030319708884001,
                    'pert'  => 0.0021864739106768,
                    'penrt' => 0.0000284609175,
                ],
                [
                    9  => 0.0000000030319708884001,
                    16 => 0.00021864739106768,
                    19 => 0.00000284609175,
                    34 => 0.00022149348281768,
                ],
            ],

        ];
    }

    protected function setUp()
    {
        $this->processLifeCycle = $this->createMock(ProcessLifeCycle::class);
        $this->indicators       = [
            new Indicator(new IndicatorId(9), 'GWP', new IndicatorIdent(IndicatorIdent::GWP), 'kg', true),
            new Indicator(new IndicatorId(13), 'ODP', new IndicatorIdent(IndicatorIdent::ODP), 'kg', true),
            new Indicator(new IndicatorId(16), 'PERT', new IndicatorIdent(IndicatorIdent::PERT), 'MJ', true),
            new Indicator(new IndicatorId(19), 'PENRT', new IndicatorIdent(IndicatorIdent::PENRT), 'MJ', true),
            new Indicator(new IndicatorId(34), 'PET', new IndicatorIdent(IndicatorIdent::PET), 'MJ', true),
        ];

        $this->calculator = new ProcessLcaCalculator($this->processLifeCycle, $this->indicators);

        $this->process = $this->createMock(Process::class);
        $this->process
            ->method('id')
            ->willReturn($this->createMock(ProcessId::class));
    }
}
