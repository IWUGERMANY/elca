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


use Beibob\Blibs\Interfaces\Logger;
use Elca\Db\ElcaBenchmarkRefProcessConfig;
use Elca\Db\ElcaBenchmarkRefProcessConfigSet;
use Elca\Db\ElcaCacheElementComponent;
use Elca\Db\ElcaCacheFinalEnergyDemand;
use Elca\Db\ElcaCacheFinalEnergyRefModel;
use Elca\Db\ElcaCacheFinalEnergySupply;
use Elca\Db\ElcaCacheItem;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigAttribute;
use Elca\Db\ElcaProcessConversion;
use Elca\Db\ElcaProcessConversionSet;
use Elca\Db\ElcaProcessIndicator;
use Elca\Db\ElcaProcessIndicatorSet;
use Elca\Db\ElcaProcessSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergyRefModel;
use Elca\Db\ElcaProjectFinalEnergyRefModelSet;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Db\ElcaProjectLifeCycleUsageSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorId;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorRepository;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\ProcessName;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfig;
use Elca\Model\ProcessConfig\ProcessConfigAttribute;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessConfigRepository;
use Elca\Model\Processing\ElcaCache;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Processing\IndicatorResult;
use Elca\Model\Processing\IndicatorResults;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Elca\Service\Project\LifeCycleUsageService;
use Exception;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Line;

class ElcaLcaProcessorTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ElcaCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    /**
     * @var ProcessConfigRepository
     */
    private $processConfigRepository;

    /**
     * @var ProcessLifeCycleRepository
     */
    private $processLifeCycleRepository;

    /**
     * @var IndicatorRepository
     */
    private $indicatorRepository;

    private $indicators;

    /**
     * @var LifeCycleUsageService|PHPUnit_Framework_MockObject_MockObject
     */
    private $lifeCycleUsageService;

    /**
     *
     */
    public function setUp()
    {
        $this->logger = $this->createMock(Logger::class);
        $this->cache  = $this->getMockBuilder(ElcaCache::class)->disableOriginalConstructor()->getMock();
        $this->processConfigRepository = $this->createMock(ProcessConfigRepository::class);
        $this->processLifeCycleRepository = $this->createMock(ProcessLifeCycleRepository::class);
        $this->indicatorRepository = $this->createMock(IndicatorRepository::class);
        $this->indicators       = [
            IndicatorIdent::GWP => new Indicator(new IndicatorId(9), 'GWP', new IndicatorIdent(IndicatorIdent::GWP), 'kg', true),
            IndicatorIdent::ODP => new Indicator(new IndicatorId(13), 'ODP', new IndicatorIdent(IndicatorIdent::ODP), 'kg', true),
            IndicatorIdent::PERT => new Indicator(new IndicatorId(16), 'PERT', new IndicatorIdent(IndicatorIdent::PERT), 'MJ', true),
            IndicatorIdent::PENRT => new Indicator(new IndicatorId(19), 'PENRT', new IndicatorIdent(IndicatorIdent::PENRT), 'MJ', true),
            IndicatorIdent::PET => new Indicator(new IndicatorId(34), 'PET', new IndicatorIdent(IndicatorIdent::PET), 'MJ', true),
        ];

        $this->lifeCycleUsageService = $this->createMock(LifeCycleUsageService::class);

        $this->lcaProcessor = new ElcaLcaProcessor([], $this->processConfigRepository, $this->processLifeCycleRepository, $this->indicatorRepository, $this->lifeCycleUsageService, $this->cache, $this->logger);
    }

    public function test_computeElementComponentQuantity_component_quantity_is_component_quantity_multiplied_with_element_quantity(
    )
    {
        $conversion    = $this->given_trivial_process_conversion(Elca::UNIT_STK);
        $processConfig = $this->given_process_config_with_conversions([$conversion]);

        $component = $this->given_component(
            $elementQuantity = 10,
            $componentQuantity = 10,
            $conversion,
            $processConfig,
            $isLayer = false
        );

        $outQuantity = $this->lcaProcessor->computeElementComponentQuantity($component);

        static::assertEquals(
            [$elementQuantity * $componentQuantity, Unit::PIECE],
            [$outQuantity->value(), $outQuantity->unit()->value()]
        );
    }

    public function test_computeElementComponentQuantity_layer_quantity_is_layer_volume_multiplied_with_element_quantity(
    )
    {
        $conversion    = $this->given_trivial_process_conversion(Elca::UNIT_M3);
        $processConfig = $this->given_process_config_with_conversions([$conversion]);

        $component = $this->given_component(
            $elementQuantity = 10,
            $componentQuantity = 10,
            $conversion,
            $processConfig,
            $isLayer = true,
            $layerLength = 2,
            $layerWidth = 2,
            $layerSize = 0.5,
            $layerAreaRatio = 0.2
        );

        $outQuantity = $this->lcaProcessor->computeElementComponentQuantity($component);

        static::assertEquals(
            [
                $elementQuantity * $componentQuantity * $layerLength * $layerWidth * $layerSize * $layerAreaRatio,
                Unit::CUBIC_METER,
            ],
            [
                $outQuantity->value(),
                $outQuantity->unit()->value(),
            ]
        );
    }

    public function test_computeElementComponent_storeElementComponent_will_be_called_once_with_correct_parameters()
    {
        $this->indicatorRepository
            ->method('findForProcessingByProcessDbId')
            ->willReturn([
                new Indicator(new IndicatorId(9), 'GWP', new IndicatorIdent(IndicatorIdent::GWP), 'kg', true),
                new Indicator(new IndicatorId(34), 'PET', new IndicatorIdent(IndicatorIdent::PET), 'MJ', true),
            ]);

        $project = $this->getMockBuilder(ElcaProject::class)->disableOriginalConstructor()->getMock();
        $project->method('getId')
                ->willReturn(99);
        $projectVariant = $this->getMockBuilder(ElcaProjectVariant::class)->disableOriginalConstructor()->getMock();
        $projectVariant->method('getProject')
                       ->willReturn($project);
        $element = $this->getMockBuilder(ElcaElement::class)->disableOriginalConstructor()->getMock();
        $element->method('getProjectVariant')
                ->willReturn($projectVariant);

        $conversion = $this->given_process_conversion(Elca::UNIT_KG, Elca::UNIT_KG, 1);

        $componentQuantity = 1;
        $elementComponent = $this->given_component(
            1,
            $componentQuantity,
            $conversion,
            $this->given_process_config_with_conversions_and_processes(
                [
                    $conversion,
                ],
                [
                    $this->given_process(
                        1,
                        Elca::UNIT_KG,
                        [],
                        ElcaLifeCycle::IDENT_A13,
                        ElcaLifeCycle::PHASE_PROD
                    ),
                ]
            ),
            false, 1, 1, 1, 1,
            $element
        );

        $elementComponent->method('getElement')
                         ->willReturn($element);

        $elementComponent->method('getCalcLca')
                         ->willReturn(true);

        $elementComponent->method('getLifeTime')
                         ->willReturn(Elca::DEFAULT_LIFE_TIME);

        $elementComponent->method('getProcessConfigId')
                         ->willReturn(1);

        $cacheElementItem = $this->getMockBuilder(ElcaCacheElementComponent::class)->disableOriginalConstructor()->getMock();
        $cacheElementItem->method('getItem')
                         ->willReturn($this->getMockBuilder(ElcaCacheItem::class)->disableOriginalConstructor()->getMock());

        $this->cache->expects(static::once())
                    ->method('storeElementComponent')
                    ->with($this->isNull(), $this->isInstanceOf(Quantity::class), 1, 0)
                    ->willReturn(
                        $cacheElementItem
                    );

        $this->lcaProcessor->computeElementComponent($elementComponent, 1, Elca::DEFAULT_LIFE_TIME);
    }

    public function test_computeElementComponent_storeIndicators_will_be_called_given_processes_and_maintenance()
    {
        $this->indicatorRepository
            ->method('findForProcessingByProcessDbId')
            ->willReturn([
                $this->indicators['gwp'],
                $this->indicators['pet'],
            ]);

        $lcUsages = [];
        foreach ([ElcaLifeCycle::IDENT_A13, ElcaLifeCycle::IDENT_C3] as $lc) {
            $lcUsages[] = $lcUsage = $this->createMock(LifeCycleUsage::class);
            $lcUsage->method('module')
                    ->willReturn(new Module($lc));
            $lcUsage->method('applyInMaintenance')
                    ->willReturn(true);
        }

        $this->lifeCycleUsageService
            ->method('findLifeCycleUsagesForProject')
            ->willReturn(new LifeCycleUsages($lcUsages));

        $project = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaProject::class);
        $project->method('getId')
                ->willReturn(99);
        $projectVariant = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaProjectVariant::class);
        $projectVariant->method('getProject')
                       ->willReturn($project);
        $element = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaElement::class);
        $element->method('getProjectVariant')
                ->willReturn($projectVariant);

        $conversion        = $this->given_process_conversion(Elca::UNIT_KG, Elca::UNIT_KG, 1);
        $componentQuantity = 1;

        $elementComponent = $this->given_component(
            1,
            $componentQuantity,
            $conversion,
            $processConfig = $this->given_elca_process_config(),
             false, 1, 1, 1, 1, $element
        );

        $processLifeCycle = $this->given_process_life_cycle([
            new LinearConversion(Unit::kg(), Unit::kg(), 1),
        ], [
            new Process(
                $processId1 = new ProcessId(123),
                Module::a13(),
                new Quantity(1, Unit::kg()),
                $this->createMock(ProcessName::class),
                1,
                IndicatorValue::valuesFromMap([
                    IndicatorIdent::GWP => 5.83,
                ])
            ),
            new Process(
                $processId2 = new ProcessId(234),
                Module::c3(),
                new Quantity(1, Unit::kg()),
                $this->createMock(ProcessName::class),
                1,
                IndicatorValue::valuesFromMap([
                    IndicatorIdent::GWP => 3.23,
                ])
            ),
        ]);

        $this->processLifeCycleRepository->method('findById')
            ->willReturn(
                $processLifeCycle
            );

        $elementComponent->method('getCalcLca')
                         ->willReturn(true);
        $elementComponent->method('getLifeTime')
                         ->willReturn(Elca::DEFAULT_LIFE_TIME);

        $cacheItem        = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheItem::class);
        $cacheElementItem = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheElementComponent::class);
        $cacheElementItem->method('getItem')
                         ->willReturn($cacheItem);

        $this->cache->method('storeElementComponent')
                    ->willReturn(
                        $cacheElementItem
                    );

        $this->cache->expects(static::exactly(3))
                    ->method('storeIndicators')
                    ->withConsecutive(
                        [
                            $cacheItem,
                            new IndicatorResults(
                                Module::a13(),
                                IndicatorResult::valuesFromMap([
                                    9  => 5.83,
                                    34 => null,
                                ]),
                                $processId1,
                                1
                            ),
                            static::isFalse(),
                        ],
                        [
                            $cacheItem,
                            new IndicatorResults(
                                Module::c3(),
                                IndicatorResult::valuesFromMap([
                                    9  => 3.23,
                                    34 => null,
                                ]),
                                $processId2,
                                1
                            ),
                            static::isFalse(),
                        ],
                        [
                            $cacheItem,
                            new IndicatorResults(
                                Module::maintenance(),
                                IndicatorResult::valuesFromMap([
                                    9  => 0,
                                    34 => 0,
                                ]),
                                null,
                                null
                            ),
                            static::isFalse(),
                        ]
                    );

        $this->lcaProcessor->computeElementComponent($elementComponent, 1, Elca::DEFAULT_LIFE_TIME);
    }

    /**
     * @param $componentQuantity
     * @param $convConf
     * @param $convsConf
     * @param $processesConf
     * @param $calcLca
     * @param $lifeTime
     * @param $resultIndicators
     * @dataProvider computedIndicatorResultsProvider
     */
    public function test_computeElementComponent_calculates_indicator_results(
        $componentQuantity,
        $convConf,
        $convsConf,
        $processesConf,
        $calcLca,
        $isExtant,
        $lifeTime,
        $resultIndicators
    ) {
        $this->indicatorRepository
            ->method('findForProcessingByProcessDbId')
            ->willReturn([
                $this->indicators['gwp'],
                $this->indicators['pet'],
            ]);

        $lcUsages = [];
        foreach ($processesConf as $conf) {
            $lcUsages[] = $lcUsage = $this->createMock(LifeCycleUsage::class);
            $lcUsage->method('module')
                    ->willReturn(new Module($conf['lcIdent']));
            $lcUsage->method('applyInMaintenance')
                    ->willReturn(true);
        }

        $this->lifeCycleUsageService
            ->method('findLifeCycleUsagesForProject')
            ->willReturn(new LifeCycleUsages($lcUsages));

        $project = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaProject::class);
        $project->method('getId')
                ->willReturn(99);

        $projectVariant = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaProjectVariant::class);
        $projectVariant->method('getProject')
                       ->willReturn($project);
        $element = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaElement::class);
        $element->method('getProjectVariant')
                ->willReturn($projectVariant);

        foreach ($processesConf as $processId => $proc) {
            $processes[$proc['lcIdent']] = new Process(
                new ProcessId($processId),
                new Module($proc['lcIdent']),
                new Quantity($proc['refValue'], new Unit($proc['refUnit'])),
                $this->createMock(ProcessName::class),
                1,
                IndicatorValue::valuesFromMap($proc['indicators'])
            );
        }

        $conversions = [];
        foreach ($convsConf as $conv) {
            $conversions[] = new LinearConversion(new Unit($conv['in']), new Unit($conv['out']), $conv['factor']);
        }

        $processLifeCycle = $this->given_process_life_cycle($conversions, $processes);

        $this->processLifeCycleRepository->method('findById')
                                         ->willReturn(
                                             $processLifeCycle
                                         );

        $elementComponent = $this->given_component(
            1, // elementQuantity
            $componentQuantity,
            $this->given_process_conversion($convConf['in'], $convConf['out'], $convConf['factor']),
            $processConfig = $this->given_elca_process_config(),
            false, 1, 1, 1, 1, $element
        );

        $elementComponent->method('getCalcLca')
                         ->willReturn($calcLca);
        $elementComponent->method('isExtant')
                         ->willReturn($isExtant);
        $elementComponent->method('getLifeTime')
                         ->willReturn($lifeTime);

        $cacheItem        = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheItem::class);
        $cacheElementItem = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheElementComponent::class);
        $cacheElementItem->method('getItem')
                         ->willReturn($cacheItem);

        $index = 0;
        $this->cache->expects(static::at($index++))
                    ->method('removeElementComponent');

        if (true === $calcLca) {
            $this->cache->expects(static::at($index++))
                        ->method('storeElementComponent')
                        ->willReturn(
                            $cacheElementItem
                        );

            foreach ($resultIndicators as $lifeCycleIdent => $indicators) {
                $this->cache->expects(static::at($index++))
                            ->method('storeIndicators')
                            ->with(
                                static::anything(),
                                new IndicatorResults(
                                    new Module($lifeCycleIdent),
                                    IndicatorResult::valuesFromMap($indicators),
                                    isset($processes[$lifeCycleIdent]) ? $processes[$lifeCycleIdent]->id() : null,
                                    isset($processes[$lifeCycleIdent]) ? 1 : null
                                ),
                                static::anything()
                            );
            }
        }

        $this->lcaProcessor->computeElementComponent($elementComponent, 1, Elca::DEFAULT_LIFE_TIME);
    }

    /**
     *
     */
    public function computedIndicatorResultsProvider()
    {
        $processM2_A13 = [
            'refValue'   => 1,
            'refUnit'    => Elca::UNIT_M2,
            'indicators' => [ElcaIndicator::IDENT_GWP => 5.83],
            'lcIdent'    => ElcaLifeCycle::IDENT_A13,
            'lcPhase'    => ElcaLifeCycle::PHASE_PROD,
        ];
        $processKG_A13 = [
            'refValue'   => 1,
            'refUnit'    => Elca::UNIT_KG,
            'indicators' => [ElcaIndicator::IDENT_GWP => 5.83],
            'lcIdent'    => ElcaLifeCycle::IDENT_A13,
            'lcPhase'    => ElcaLifeCycle::PHASE_PROD,
        ];
        $processKG_A4 = [
            'refValue'   => 1,
            'refUnit'    => Elca::UNIT_KG,
            'indicators' => [ElcaIndicator::IDENT_GWP => 5.83],
            'lcIdent'    => ElcaLifeCycle::IDENT_A4,
            'lcPhase'    => ElcaLifeCycle::PHASE_PROD,
        ];
        $processKG_A5 = [
            'refValue'   => 1,
            'refUnit'    => Elca::UNIT_KG,
            'indicators' => [ElcaIndicator::IDENT_GWP => 5.83],
            'lcIdent'    => ElcaLifeCycle::IDENT_A5,
            'lcPhase'    => ElcaLifeCycle::PHASE_PROD,
        ];
        $processKG_C3 = [
            'refValue'   => 1,
            'refUnit'    => Elca::UNIT_KG,
            'indicators' => [ElcaIndicator::IDENT_GWP => 3.23],
            'lcIdent'    => ElcaLifeCycle::IDENT_C3,
            'lcPhase'    => ElcaLifeCycle::PHASE_EOL
        ];
        $processKG_C1 = [
            'refValue'   => 1,
            'refUnit'    => Elca::UNIT_KG,
            'indicators' => [ElcaIndicator::IDENT_GWP => 3.23],
            'lcIdent'    => ElcaLifeCycle::IDENT_C1,
            'lcPhase'    => ElcaLifeCycle::PHASE_EOL
        ];
        $processKG_C2 = [
            'refValue'   => 1,
            'refUnit'    => Elca::UNIT_KG,
            'indicators' => [ElcaIndicator::IDENT_GWP => 3.23],
            'lcIdent'    => ElcaLifeCycle::IDENT_C2,
            'lcPhase'    => ElcaLifeCycle::PHASE_EOL
        ];

        // [ $componentQuantity,
        //  $conversion,
        //  $conversions,
        //  $processes,
        //  $calcLca,
        //  $lifeTime,
        //  $resultIndicators
        // ]
        return [
            'calcLcaIsFalse' => [
                1,
                [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1],
                [
                    [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1]
                ],
                [$processKG_A13, $processKG_C3],
                false,
                false,
                50,
                [
                    ElcaLifeCycle::IDENT_A13   => [9 => 5.83, 34 => null],
                    ElcaLifeCycle::IDENT_C3    => [9 => 3.23, 34 => null],
                    ElcaLifeCycle::PHASE_MAINT => [9 => 0, 34 => null],
                ],
            ],
            'calcLcaIsTrue' => [
                1,
                [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1],
                [
                    [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1]
                ],
                [$processKG_A13, $processKG_C3],
                true,
                false,
                50,
                [
                    ElcaLifeCycle::IDENT_A13   => [9 => 5.83, 34 => null],
                    ElcaLifeCycle::IDENT_C3    => [9 => 3.23, 34 => null],
                    ElcaLifeCycle::PHASE_MAINT => [9 => 0, 34 => null],
                ],
            ],
            'convertEolIndicators' => [
                1,
                [ 'in' => Elca::UNIT_M2, 'out' => Elca::UNIT_KG, 'factor' => 10],
                [
                    [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1],
                    [ 'in' => Elca::UNIT_M2, 'out' => Elca::UNIT_KG, 'factor' => 10],
                ],
                [$processM2_A13, $processKG_C3],
                true,
                false,
                50,
                [
                    ElcaLifeCycle::IDENT_A13   => [9 => 5.83, 34 => null],
                    ElcaLifeCycle::IDENT_C3    => [9 => 32.3, 34 => null],
                    ElcaLifeCycle::PHASE_MAINT => [9 => 0, 34 => null],
                ],
            ],
            'convertProdAndEolIndicators' => [
                1,
                [ 'in' => Elca::UNIT_M2, 'out' => Elca::UNIT_KG, 'factor' => 10],
                [
                    [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1],
                    [ 'in' => Elca::UNIT_M2, 'out' => Elca::UNIT_KG, 'factor' => 10],
                ],
                [$processKG_A13, $processKG_C3],
                true,
                false,
                50,
                [
                    ElcaLifeCycle::IDENT_A13   => [9 => 58.3, 34 => null],
                    ElcaLifeCycle::IDENT_C3    => [9 => 32.3, 34 => null],
                    ElcaLifeCycle::PHASE_MAINT => [9 => 0, 34 => null],
                ],
            ],
            'computeWithMaintenance' => [
                1,
                [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1],
                [
                    [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1]
                ],
                [$processKG_A13, $processKG_C3],
                true,
                false,
                25,
                [
                    ElcaLifeCycle::IDENT_A13   => [9 => 5.83, 34 => null],
                    ElcaLifeCycle::IDENT_C3    => [9 => 3.23, 34 => null],
                    ElcaLifeCycle::PHASE_MAINT => [9 => (5.83 + 3.23), 34 => null],
                ],
            ],
            'excludeLifeTimeIdents' => [
                1,
                [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1],
                [
                    [ 'in' => Elca::UNIT_KG, 'out' => Elca::UNIT_KG, 'factor' => 1]
                ],
                [$processKG_A13, $processKG_A4, $processKG_A5, $processKG_C1, $processKG_C2, $processKG_C3],
                true,
                false,
                50,
                [
                    ElcaLifeCycle::IDENT_A13   => [9 => 5.83, 34 => null],
                    ElcaLifeCycle::IDENT_C3    => [9 => 3.23, 34 => null],
                    ElcaLifeCycle::PHASE_MAINT => [9 => 0, 34 => null],
                ],
            ],
        ];
    }

    /**
     * @param array $quantities
     * @param       $ngfEnEv
     * @param array $opProcessIndicators
     * @param       $fhsHi
     * @param array $resultIndicators
     * @dataProvider finalEnergyDemandIndicatorsProvider
     */
    public function test_computeFinalEnergyDemand(array $quantities, $ngfEnEv, array $opProcessIndicators, $fhsHi, array $resultIndicators)
    {
        $variantId = 1;
        $processDbId = 1;
        $lifeTime = Elca::DEFAULT_LIFE_TIME;

        $processes = [];
        foreach ($opProcessIndicators as $processId => $indicators) {
            $processes[] = new Process(
                new ProcessId($processId),
                Module::b6(),
                new Quantity(3.6, Unit::MJ()),
                $this->createMock(ProcessName::class),
                1,
                IndicatorValue::valuesFromMap($indicators)
            );
        }

        $processLifeCycle = $this->given_process_life_cycle([
            new LinearConversion(Unit::kWh(), Unit::MJ(), 3.6),
        ], $processes);

        $this->processLifeCycleRepository->method('findById')
                                         ->willReturn(
                                             $processLifeCycle
                                         );
        $processConfig = $this->given_process_config($fhsHi);

        $this->processConfigRepository->method('findById')
                                         ->willReturn($processConfig);

        $finalEnergyDemands = $this->given_finalEnergyDemands($quantities, $processConfig->id());

        $cacheItem        = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheItem::class);
        $cacheDemandItem = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheFinalEnergyDemand::class);
        $cacheDemandItem->method('getItem')
                         ->willReturn($cacheItem);

        /**
         * Expectations
         */
        $index = 0;
        $this->cache->expects(static::at($index++))
                    ->method('removeFinalEnergyDemands')
            ->with(static::equalTo($variantId));

        $this->cache->expects(static::at($index++))
                    ->method('storeFinalEnergyDemand')
                    ->willReturn(
                        $cacheDemandItem
                    );

        foreach ($resultIndicators as $lifeCycleIdent => $indicators) {
            list($lifeCycleIdent, $processId) = explode('_', $lifeCycleIdent);

            $this->cache->expects(static::at($index++))
                        ->method('storeIndicators')
                        ->with(
                            static::anything(),
                            $this->equalTo(new IndicatorResults(
                                new Module($lifeCycleIdent),
                                IndicatorResult::valuesFromMap($indicators),
                                new ProcessId($processId),
                                1
                            ), 0.0001),
                            static::anything()
                        );
        }

        $indicatorIdents = array_keys(current($opProcessIndicators));
        $indicators = [];
        foreach ($indicatorIdents as $indicatorIdent) {
            $indicators[] = $this->indicators[$indicatorIdent];
        }
        $indicators[] = $this->indicators['pet'];

        $this->lcaProcessor->computeFinalEnergyDemand($variantId, $finalEnergyDemands, $processDbId, $lifeTime, $ngfEnEv, $indicators);
    }

    /**
     *
     */
    public function finalEnergyDemandIndicatorsProvider()
    {
        $lifeTime = Elca::DEFAULT_LIFE_TIME;

        // array $quantities
        //       $ngfEnEv
        // array $opProcessIndicators
        //       $fhsHi
        // array $resultIndicators
        return [
            'petAddsUp' => [
                [
                    'heating' => 100,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                        ElcaIndicator::IDENT_ODP => -0.0009,
                        ElcaIndicator::IDENT_PERT => -950.02,
                        ElcaIndicator::IDENT_PENRT => 1008.95,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 100 * $lifeTime,
                        13 => -0.0009 * 100 * $lifeTime,
                        16 => -950.02 * 100 * $lifeTime,
                        19 => 1008.95 * 100 * $lifeTime,
                        34 => (-950.02 + 1008.95) * 100 * $lifeTime,
                    ]
                ],
            ],
            'quantitiesAddUp' => [
                [
                    'heating' => 100,
                    'water' => 50,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                        ElcaIndicator::IDENT_ODP => -0.0009,
                        ElcaIndicator::IDENT_PERT => -950.02,
                        ElcaIndicator::IDENT_PENRT => 1008.95,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 150 * $lifeTime,
                        13 => -0.0009 * 150 * $lifeTime,
                        16 => -950.02 * 150 * $lifeTime,
                        19 => 1008.95 * 150 * $lifeTime,
                        34 => (-950.02 + 1008.95) * 150 * $lifeTime,
                    ]
                ],
            ],
            'fhsHiIsBeingReflected' => [
                [
                    'heating' => 100,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                    ],
                ],
                2,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 100 / 2 * $lifeTime,
                        34 => null,
                    ]
                ],
            ],
            'ngfEnEvIsBeingReflected' => [
                [
                    'heating' => 100,
                ],
                10,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 10 * 100 * $lifeTime,
                        34 => null,
                    ]
                ],
            ],
            'multipleProcesses' => [
                [
                    'heating' => 1,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                    ],
                    2 => [
                        ElcaIndicator::IDENT_GWP => 1.02,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6 .'_1' => [
                        9  => 5.83 * $lifeTime,
                        34 => null,
                    ],
                    ElcaLifeCycle::IDENT_B6 .'_2' => [
                        9  => 1.02 * $lifeTime,
                        34 => null,
                    ],
                ],
            ]
        ];
    }

    /**
     * @param       $quantity
     * @param       $enEvRatio
     * @param array $opProcessIndicators
     * @param array $processConfigAttributes
     * @param array $resultIndicators
     * @dataProvider finalEnergySupplyIndicatorsProvider
     */
    public function test_computeFinalEnergySupply($quantity, $enEvRatio, array $opProcessIndicators, array $processConfigAttributes, array $resultIndicators)
    {
        $variantId = 1;
        $processDbId = 1;
        $lifeTime = Elca::DEFAULT_LIFE_TIME;

        $processes = [];
        foreach ($opProcessIndicators as $processId => $indicators) {
            $processes[] = new Process(
                new ProcessId($processId),
                Module::b6(),
                new Quantity(3.6, Unit::MJ()),
                $this->createMock(ProcessName::class),
                1,
                IndicatorValue::valuesFromMap($indicators)
            );
        }

        $processLifeCycle = $this->given_process_life_cycle([
            new LinearConversion(Unit::kWh(), Unit::MJ(), 3.6),
        ], $processes);

        $this->processLifeCycleRepository->method('findById')
                                         ->willReturn(
                                             $processLifeCycle
                                         );
        $processConfig = $this->given_process_config(1);

        foreach ($processConfigAttributes as $attributeIdent => $attributeValue) {
            $this->processConfigRepository->method('findAttributeForId')
                                             ->willReturn(
                                                 new ProcessConfigAttribute(
                                                     $processConfig->id(),
                                                     $attributeIdent,
                                                     $attributeValue
                                                 )
                                         );
        }

        $finalEnergySupplies = $this->given_finalEnergySupplies($quantity, $enEvRatio, $processConfig->id());

        $cacheItem        = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheItem::class);
        $cacheSupplyItem = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheFinalEnergySupply::class);
        $cacheSupplyItem->method('getItem')
                        ->willReturn($cacheItem);

        /**
         * Expectations
         */
        $index = 0;
        $this->cache->expects(static::at($index++))
                    ->method('removeFinalEnergySupplies')
                    ->with(static::equalTo($variantId));

        $this->cache->expects(static::at($index++))
                    ->method('storeFinalEnergySupply')
                    ->willReturn(
                        $cacheSupplyItem
                    );

        foreach ($resultIndicators as $lifeCycleIdent => $indicators) {
            list($lifeCycleIdent, $processId) = explode('_', $lifeCycleIdent);

            $this->cache->expects(static::at($index++))
                        ->method('storeIndicators')
                        ->with(
                            static::anything(),
                            $this->equalTo(new IndicatorResults(
                                new Module($lifeCycleIdent),
                                IndicatorResult::valuesFromMap($indicators),
                                new ProcessId($processId),
                                1
                            ), 0.0001),
                            static::anything()
                        );
        }

        $indicatorIdents = array_keys(current($opProcessIndicators));
        $indicators = [];
        foreach ($indicatorIdents as $indicatorIdent) {
            $indicators[] = $this->indicators[$indicatorIdent];
        }
        $indicators[] = $this->indicators['pet'];
        $this->lcaProcessor->computeFinalEnergySupply($variantId, $finalEnergySupplies, $lifeTime, $processDbId, 1, $indicators);
    }



    /**
     *
     */
    public function finalEnergySupplyIndicatorsProvider()
    {
        $lifeTime = Elca::DEFAULT_LIFE_TIME;

        //       $quantity
        //       $enEvRatio
        // array $opProcessIndicators
        // array $processConfigAttributes
        // array $resultIndicators
        return [
            'petAddsUp' => [
                100,
                0,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                        ElcaIndicator::IDENT_ODP => -0.0009,
                        ElcaIndicator::IDENT_PERT => -950.02,
                        ElcaIndicator::IDENT_PENRT => 1008.95,
                    ],
                ],
                [],
                [
                    ElcaLifeCycle::IDENT_D.'_1' => [
                        9  => 5.83 * 100 * $lifeTime,
                        13 => -0.0009 * 100 * $lifeTime,
                        16 => -950.02 * 100 * $lifeTime,
                        19 => 1008.95 * 100 * $lifeTime,
                        34 => (-950.02 + 1008.95) * 100 * $lifeTime,
                    ]
                ],
            ],
            'enEvRatio' => [
                100,
                0.5,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                    ],
                ],
                [],
                [
                    ElcaLifeCycle::IDENT_D.'_1' => [
                        9  => 5.83 * 50 * $lifeTime,
                        34 => null
                    ]
                ],
            ],
            'invertedValues' => [
                100,
                0,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                        ElcaIndicator::IDENT_ODP => -0.0009,
                        ElcaIndicator::IDENT_PERT => -950.02,
                        ElcaIndicator::IDENT_PENRT => 1008.95,
                    ],
                ],
                [
                    ElcaProcessConfigAttribute::IDENT_OP_INVERT_VALUES => true
                ],
                [
                    ElcaLifeCycle::IDENT_D.'_1' => [
                        9  => 5.83 * 100 * $lifeTime * -1,
                        13 => -0.0009 * 100 * $lifeTime * -1,
                        16 => -950.02 * 100 * $lifeTime * -1,
                        19 => 1008.95 * 100 * $lifeTime * -1,
                        34 => (-950.02 + 1008.95) * 100 * $lifeTime * -1,
                    ]
                ],
            ],
        ];
    }

    /**
     * @param       $refModelIdent
     * @param array $quantities
     * @param       $ngfEnEv
     * @param array $opProcessIndicators
     * @param       $fhsHi
     * @param array $resultIndicators
     * @dataProvider finalEnergyRefModelIndicatorsProvider
     */
    public function test_computeFinalEnergyRefModel($refModelIdent, $quantities, $ngfEnEv, array $opProcessIndicators, $fhsHi, array $resultIndicators)
    {
        $variantId = 1;
        $processDbId = 1;
        $projectId = 1;
        $lifeTime = Elca::DEFAULT_LIFE_TIME;

        $variant = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaProjectVariant::class);
        $variant->method('getId')
                ->willReturn($variantId);
        $variant->method('getProjectId')
                ->willReturn($projectId);

        $processes = [];
        foreach ($opProcessIndicators as $processId => $indicators) {
            $processes[] = new Process(
                new ProcessId($processId),
                Module::b6(),
                new Quantity(3.6, Unit::MJ()),
                $this->createMock(ProcessName::class),
                1,
                IndicatorValue::valuesFromMap($indicators)
            );
        }

        $processLifeCycle = $this->given_process_life_cycle([
            new LinearConversion(Unit::kWh(), Unit::MJ(), 3.6),
        ], $processes);

        $this->processLifeCycleRepository->method('findById')
                                         ->willReturn(
                                             $processLifeCycle
                                         );
        $processConfig = $this->given_elca_process_config($fhsHi);

        $benchmarkRefProcessConfigSet = new ElcaBenchmarkRefProcessConfigSet();
        $benchmarkRefProcessConfig = $this->getMockBuilder(ElcaBenchmarkRefProcessConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdent', 'getProcessConfigId', 'getProcessConfig'])
            ->getMock();

        $benchmarkRefProcessConfig->method('getIdent')
                                  ->willReturn($refModelIdent);
        $benchmarkRefProcessConfig->method('getProcessConfigId')
                                  ->willReturn($processConfig->getId());
        $benchmarkRefProcessConfig->method('getProcessConfig')
                                  ->willReturn($processConfig);

        $benchmarkRefProcessConfigSet->add($benchmarkRefProcessConfig);

        $finalEnergyRefModels = $this->given_finalEnergyRefModels($refModelIdent, $quantities);

        $cacheItem        = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheItem::class);
        $cacheRefModelItem = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaCacheFinalEnergyRefModel::class);
        $cacheRefModelItem->method('getItem')
                        ->willReturn($cacheItem);

        /**
         * Expectations
         */
        $index = 0;
        $this->cache->expects(static::at($index++))
                    ->method('removeFinalEnergyRefModels')
                    ->with(static::equalTo($variantId));

        $this->cache->expects(static::at($index++))
                    ->method('storeFinalEnergyRefModel')
                    ->willReturn(
                        $cacheRefModelItem
                    );

        foreach ($resultIndicators as $lifeCycleIdent => $indicators) {
            list($lifeCycleIdent, $processId) = explode('_', $lifeCycleIdent);

            $this->cache->expects(static::at($index++))
                        ->method('storeIndicators')
                        ->with(
                            static::anything(),
                            $this->equalTo(new IndicatorResults(
                                new Module($lifeCycleIdent),
                                IndicatorResult::valuesFromMap($indicators),
                                new ProcessId($processId),
                                1
                            ), 0.0001),
                            static::anything()
                        );
        }

        $indicatorIdents = array_keys(current($opProcessIndicators));
        $indicators = [];
        foreach ($indicatorIdents as $indicatorIdent) {
            $indicators[] = $this->indicators[$indicatorIdent];
        }
        $indicators[] = $this->indicators['pet'];
        $this->lcaProcessor->computeFinalEnergyReferenceModel(
            $variant,
            $ngfEnEv,
            $lifeTime,
            $processDbId,
            $finalEnergyRefModels,
            $benchmarkRefProcessConfigSet,
            $indicators
        );
    }



    /**
     *
     */
    public function finalEnergyRefModelIndicatorsProvider()
    {
        $lifeTime = Elca::DEFAULT_LIFE_TIME;

        //       $refModelIdent
        // array $quantities
        //       $ngfEnEv
        // array $opProcessIndicators
        //       $fhsHi
        // array $resultIndicators
        return [
            'petAddsUp' => [
                ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                [
                    'heating' => 100,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                        ElcaIndicator::IDENT_ODP => -0.0009,
                        ElcaIndicator::IDENT_PERT => -950.02,
                        ElcaIndicator::IDENT_PENRT => 1008.95,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 100 * $lifeTime,
                        13 => -0.0009 * 100 * $lifeTime,
                        16 => -950.02 * 100 * $lifeTime,
                        19 => 1008.95 * 100 * $lifeTime,
                        34 => (-950.02 + 1008.95) * 100 * $lifeTime,
                    ]
                ],
            ],
            'quantitiesAddUp' => [
                ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                [
                    'heating' => 100,
                    'water' => 50,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                        ElcaIndicator::IDENT_ODP => -0.0009,
                        ElcaIndicator::IDENT_PERT => -950.02,
                        ElcaIndicator::IDENT_PENRT => 1008.95,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 150 * $lifeTime,
                        13 => -0.0009 * 150 * $lifeTime,
                        16 => -950.02 * 150 * $lifeTime,
                        19 => 1008.95 * 150 * $lifeTime,
                        34 => (-950.02 + 1008.95) * 150 * $lifeTime,
                    ]
                ],
            ],
            'fhsHiIsBeingReflected' => [
                ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                [
                    'heating' => 100,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                    ],
                ],
                2,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 100 / 2 * $lifeTime,
                        34 => null,
                    ]
                ],
            ],
            'ngfEnEvIsBeingReflected' => [
                ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                [
                    'heating' => 100,
                ],
                10,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6.'_1' => [
                        9  => 5.83 * 10 * 100 * $lifeTime,
                        34 => null,
                    ]
                ],
            ],
            'multipleProcesses' => [
                ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                [
                    'heating' => 1,
                ],
                1,
                [
                    1 => [
                        ElcaIndicator::IDENT_GWP => 5.83,
                    ],
                    2 => [
                        ElcaIndicator::IDENT_GWP => 1.02,
                    ],
                ],
                1,
                [
                    ElcaLifeCycle::IDENT_B6 .'_1' => [
                        9  => 5.83 * $lifeTime,
                        34 => null,
                    ],
                    ElcaLifeCycle::IDENT_B6 .'_2' => [
                        9  => 1.02 * $lifeTime,
                        34 => null,
                    ],
                ],
            ]
        ];
    }


    //******************************************************************************************************************
    //******************************************************************************************************************
    //******************************************************************************************************************

    /**
     * @param array $quantities
     * @param       $processConfigId
     * @return ElcaProjectFinalEnergyDemandSet
     */
    protected function given_finalEnergyDemands(array $quantities, ProcessConfigId $processConfigId)
    {
        $finalEnergyDemands = new ElcaProjectFinalEnergyDemandSet();

        $finalEnergyDemand = $this->getMockBuilder(ElcaProjectFinalEnergyDemand::class)
                                  ->disableOriginalConstructor()
                                  ->setMethods(['getProcessConfig', 'getProcessConfigId', 'getId', 'getHeating', 'getWater'])
                                  ->getMock();
        $finalEnergyDemand->method('getProcessConfigId')
                          ->willReturn($processConfigId->value());
        $finalEnergyDemand->method('getId')
                          ->willReturn(123);

        foreach ($quantities as $ident => $quantity) {
            $finalEnergyDemand->method('get'.ucfirst($ident))
                              ->willReturn($quantity);
        }

        $finalEnergyDemands->add($finalEnergyDemand);

        return $finalEnergyDemands;
    }

    /**
     * @param $quantity
     * @param $enEvRatio
     * @param $processConfig
     * @return ElcaProjectFinalEnergySupplySet
     */
    protected function given_finalEnergySupplies($quantity, $enEvRatio, ProcessConfigId $processConfigId)
    {
        $finalEnergySupplies = new ElcaProjectFinalEnergySupplySet();

        $finalEnergySupply = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaProjectFinalEnergySupply::class);
        $finalEnergySupply->method('getProcessConfigId')
                          ->willReturn($processConfigId->value());
        $finalEnergySupply->method('getId')
                          ->willReturn(123);
        $finalEnergySupply->method('getQuantity')
                          ->willReturn($quantity);
        $finalEnergySupply->method('getEnEvRatio')
                          ->willReturn($enEvRatio);

        $finalEnergySupplies->add($finalEnergySupply);

        return $finalEnergySupplies;
    }

    /**
     * @param array $refModelConf
     * @return ElcaProjectFinalEnergyRefModelSet
     */
    protected function given_finalEnergyRefModels($refModelIdent, array $quantities)
    {
        $finalEnergyRefModels = new ElcaProjectFinalEnergyRefModelSet();

        $finalEnergyRefModel = $this->getMockBuilder(ElcaProjectFinalEnergyRefModel::class)
                                    ->disableOriginalConstructor()
                                    ->enableArgumentCloning()
                                    ->setMethods(['getId', 'getIdent', 'getHeating', 'getWater'])
                                    ->getMock();

        $finalEnergyRefModel->method('getIdent')
                            ->willReturn($refModelIdent);

        $finalEnergyRefModel->method('getId')
                            ->willReturn(123);

        foreach ($quantities as $qtyIdent => $quantity) {
            $finalEnergyRefModel->method('get'.ucfirst($qtyIdent))
                                ->willReturn($quantity);
        }

        $finalEnergyRefModels->add($finalEnergyRefModel);

        return $finalEnergyRefModels;
    }

    /**
     * @param $inUnit
     * @param $outUnit
     * @param $factor
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function given_process_conversion($inUnit, $outUnit, $factor)
    {
        $conversion = $this->getMockBuilder(ElcaProcessConversion::class)
                           ->disableOriginalConstructor()
                           ->setMethods(['isTrivial', 'getInUnit', 'getOutUnit', 'getFactor'])
                           ->getMock();

        $conversion->method('isTrivial')
                   ->willReturn($inUnit === $outUnit);
        $conversion->method('getInUnit')
                   ->willReturn($inUnit);
        $conversion->method('getOutUnit')
                   ->willReturn($outUnit);
        $conversion->method('getFactor')
                   ->willReturn($factor);

        return $conversion;
    }

    /**
     * @param      $elementQuantity
     * @param      $componentQuantity
     * @param      $conversion
     * @param      $processConfig
     * @param bool $isLayer
     * @param int  $layerLength
     * @param int  $layerWidth
     * @param int  $layerSize
     * @param int  $layerAreaRatio
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function given_component(
        $elementQuantity,
        $componentQuantity,
        $conversion,
        $processConfig,
        $isLayer = false,
        $layerLength = 1,
        $layerWidth = 1,
        $layerSize = 1,
        $layerAreaRatio = 1,
        $elementMock = null
    ) {
        if (null == $elementMock) {
            $element = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaElement::class);
        } else {
            $element = $elementMock;
        }

        $element->method('getQuantity')
                ->willReturn($elementQuantity);

        $component = $this->getMockWithoutInvokingTheOriginalConstructor(ElcaElementComponent::class);

        $component->method('getElement')
                  ->willReturn($element);
        $component->method('getQuantity')
                  ->willReturn($componentQuantity);
        $component->method('getProcessConversion')
                  ->willReturn($conversion);
        $component->method('getProcessConfig')
                  ->willReturn(
                      $processConfig
                  );
        $component->method('getProcessConfigId')
                  ->willReturn(
                      $processConfig->getId()
                  );
        $component->method('isLayer')
                  ->willReturn($isLayer);

        if ($isLayer) {
            $component->method('getLayerLength')
                      ->willReturn($layerLength);
            $component->method('getLayerWidth')
                      ->willReturn($layerWidth);
            $component->method('getLayerSize')
                      ->willReturn($layerSize);
            $component->method('getLayerAreaRatio')
                      ->willReturn($layerAreaRatio);
        }

        $component->method('isExtant')
                  ->willReturn(false);

        return $component;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function given_trivial_process_conversion($unit = Elca::UNIT_STK)
    {
        return $this->given_process_conversion($unit, $unit, 1);
    }

    private function given_process_config_with_conversions($conversions, $fhsHi = 1, array $attributes = [])
    {
        $processConversionSet = new ElcaProcessConversionSet();
        foreach ($conversions as $conversion) {
            $processConversionSet->add($conversion);
        }

        $processConfig = $this->getMockBuilder(ElcaProcessConfig::class)
                              ->disableOriginalConstructor()
                              ->setMethods(['getId', 'getProcessConversions', 'getProcessesByProcessDbId', 'getFHsHi', 'getAttributeValue'])
                              ->getMock();

        $processConfig->method('getId')
                      ->willReturn(1);

        $processConfig->method('getProcessConversions')
                      ->willReturn($processConversionSet);

        $processConfig->method('getFHsHi')
                      ->willReturn($fhsHi);

        foreach ($attributes as $ident => $attributeValue) {
            $processConfig->method('getAttributeValue')
                          ->with(static::equalTo($ident))
                          ->willReturn($attributeValue);
        }

        return $processConfig;
    }

    private function given_elca_process_config(float $fhsHi = null, array $attributes = [])
    {
        $processConfig = $this->createMock(ElcaProcessConfig::class);

        $processConfig->method('getId')
                      ->willReturn(1);

        if (null !== $fhsHi) {
            $processConfig->method('getFHsHi')
                          ->willReturn($fhsHi);
        }

        foreach ($attributes as $ident => $attributeValue) {
            $processConfig->method('getAttributeValue')
                          ->with(static::equalTo($ident))
                          ->willReturn($attributeValue);
        }

        return $processConfig;
    }

    public function given_process_config(float $fhsHi = null)
    {
        static $id = 0;

        $processConfig = $this->createMock(ProcessConfig::class);

        $processConfig->method('id')
                      ->willReturn(new ProcessConfigId(++$id));

        $processConfig->method('energyEfficiency')
            ->willReturn($fhsHi);

        return $processConfig;
    }


    /**
     * @return ProcessLifeCycle
     */
    private function given_process_life_cycle(array $conversions, array $processes = [])
    {
        $processLifeCycle = $this->getMockBuilder(ProcessLifeCycle::class)
                              ->disableOriginalConstructor()
                              ->setMethods(['findProcessById', 'processes', 'conversions', 'processConfigId'])
                              ->getMock();

        $processLifeCycle->method('conversions')
            ->willReturn($conversions);

        $processLifeCycle->method('processConfigId')
                         ->willReturn($this->createMock(ProcessConfigId::class));

        $processLifeCycle
            ->method('processes')
            ->willReturn(array_values($processes));

        $processLifeCycle->method('findProcessById')
            ->willReturnCallback(function (ProcessId $processId) use ($processes) {
                foreach ($processes as $process) {
                    if ($processId->equals($process->id())) {
                        return $process;
                    }
                }

                return null;
            });

        return $processLifeCycle;
    }

    private function given_process_config_with_conversions_and_processes(array $conversions, array $processes, $fhsHi = 1, array $attributes = [])
    {
        $processConfig = $this->given_process_config_with_conversions($conversions, $fhsHi, $attributes);

        $processSet = new ElcaProcessSet();
        foreach ($processes as $process) {
            $processSet->add($process);
        }

        $processConfig->method('getProcessesByProcessDbId')
                      ->willReturn($processSet);


        return $processConfig;
    }

    /**
     * @param $indicatorId
     * @param $indicatorIdent
     * @return \PHPUnit_Framework_MockObject_MockObject|ElcaIndicator
     */
    private function given_indicator($indicatorId, $indicatorIdent)
    {
        $indicator = $this->getMockBuilder(ElcaIndicator::class)
                          ->disableOriginalConstructor()
                          ->getMock();
        $indicator->method('getId')
                  ->willReturn($indicatorId);
        $indicator->method('getIdent')
                  ->willReturn($indicatorIdent);

        return $indicator;
    }

    /**
     * @param $indicatorId
     * @param $value
     * @return \PHPUnit_Framework_MockObject_MockObject|ElcaProcessIndicator
     */
    private function given_process_indicator($indicatorId, $value)
    {
        $processIndicator = $this->getMockBuilder(ElcaProcessIndicator::class)
                                 ->disableOriginalConstructor()
                                 ->setMethods(['getIndicatorId', 'getValue'])
                                 ->getMock();

        $processIndicator->method('getIndicatorId')
                         ->willReturn($indicatorId);
        $processIndicator->method('getValue')
                         ->willReturn($value);

        return $processIndicator;
    }

    /**
     * @param      $refValue
     * @param      $refUnit
     * @param      $processIndicators
     * @param null $lifeCycleIdent
     * @param null $lifeCyclePhase
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function given_process(
        $refValue,
        $refUnit,
        $processIndicators,
        $lifeCycleIdent = null,
        $lifeCyclePhase = null,
        $processId = null
    ) {
        $indicatorSet        = new ElcaIndicatorSet();
        $processIndicatorSet = new ElcaProcessIndicatorSet();

        foreach ($processIndicators as $indicatorId => $processIndicator) {
            foreach ($processIndicator as $ident => $value) {
                $indicatorSet->add($this->given_indicator($indicatorId, $ident));
                $processIndicatorSet->add($this->given_process_indicator($indicatorId, $value));
                $indicatorId++;
            }
        }

        $process = $this->getMockBuilder(ElcaProcess::class)
                        ->disableOriginalConstructor()
                        ->enableArgumentCloning()
                        ->getMock();
        $process->method('getRefValue')
                ->willReturn($refValue);
        $process->method('getRefUnit')
                ->willReturn($refUnit);
        $process->method('getIndicators')
                ->willReturn($indicatorSet);
        $process->method('getProcessIndicators')
                ->willReturn($processIndicatorSet);

        if (null !== $lifeCycleIdent && null !== $lifeCyclePhase) {
            $process->method('getLifeCycleIdent')
                    ->willReturn($lifeCycleIdent);
            $process->method('getLifeCyclePhase')
                    ->willReturn($lifeCyclePhase);
        }

        if (null !== $processId) {
            $process->method('getId')
                    ->willReturn($processId);
        }

        return $process;
    }

    private function given_pet_indicator()
    {
        $petIndicator = $this->getMockBuilder(ElcaIndicator::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $petIndicator->method('getId')
                     ->willReturn(34);

        return $petIndicator;
    }

    private function getMockWithoutInvokingTheOriginalConstructor($class)
    {
        return $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
    }


}
