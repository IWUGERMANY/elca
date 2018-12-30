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

namespace Elca\Model\Processing;

use Beibob\Blibs\DbObjectCache;
use Beibob\Blibs\File;
use Beibob\Blibs\Interfaces\Logger;
use Elca\Db\ElcaBenchmarkRefProcessConfig;
use Elca\Db\ElcaBenchmarkRefProcessConfigSet;
use Elca\Db\ElcaCacheProjectVariant;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaProcessConfigAttribute;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergyRefModel;
use Elca\Db\ElcaProjectFinalEnergyRefModelSet;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectTransport;
use Elca\Db\ElcaProjectTransportMean;
use Elca\Db\ElcaProjectTransportMeanSet;
use Elca\Db\ElcaProjectTransportSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Indicator\IndicatorRepository;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessConfigRepository;
use Elca\Model\ProcessConfig\UsefulLife;
use Elca\Model\Processing\Element\ElementComponentQuantity;
use Elca\Model\Project\ProjectId;
use Elca\Service\Project\LifeCycleUsageService;
use Exception;

/**
 * ElcaLcaProcessor is a class for lca related computations
 *
 * Main entry points are
 *
 *     computeElement(ElcaElement)
 *           computes lca for a single element
 *
 *     computeFinalEnergyDemand(ElcaProjectVariant)
 *           computes lca for all final energy demands
 *
 * After calling these methods, you should call
 *
 *     udpateCache()
 *
 * to aggregate the new results in the element type hierarchy
 *
 * If an element was deleted within a variant, reaggration of the element type hierarchy
 * can be triggered by
 *
 *     updateElementTypeTree(int $projectVariantId, int $elementTypeNodeId)
 *
 * whereby element type node should be the element type nodeId of the deleted
 * element.
 *
 * It is possible to register for calculation events through the
 * ElcaLcaProcessingInterface, which can be registered with
 *
 *     registerLcaProcessor(ElcaLcaProcessingInterface)
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @see     ElcaCache
 * @see     ElcaLcaProcessingObserver
 */
class ElcaLcaProcessor
{
    /**
     * Reference to Cache
     */
    private $cache;

    /**
     * Log
     */
    private $logger;

    /**
     * @var ElcaLcaProcessingObserver[]
     */
    private $processors = [];

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

    /**
     * @var LifeCycleUsageService
     */
    private $lifeCycleUsageService;

    /**
     * Constructor
     *
     * @param array                      $processors
     * @param ProcessConfigRepository    $processConfigRepository
     * @param ProcessLifeCycleRepository $processLifeCycleRepository
     * @param IndicatorRepository        $indicatorRepository
     * @param LifeCycleUsageService      $lifeCycleUsageService
     * @param ElcaCache                  $cache
     * @param Logger                     $logger
     */
    public function __construct(
        array $processors, ProcessConfigRepository $processConfigRepository,
        ProcessLifeCycleRepository $processLifeCycleRepository, IndicatorRepository $indicatorRepository,
        LifeCycleUsageService $lifeCycleUsageService, ElcaCache $cache, Logger $logger
    ) {
        $this->processors                 = $processors;
        $this->cache                      = $cache;
        $this->logger                     = $logger;
        $this->processConfigRepository    = $processConfigRepository;
        $this->processLifeCycleRepository = $processLifeCycleRepository;
        $this->indicatorRepository        = $indicatorRepository;
        $this->lifeCycleUsageService      = $lifeCycleUsageService;
    }
    // End __construct

    /**
     * Recomputes a project variant
     *
     * @param ElcaProjectVariant $variant
     * @param  int               $processDbId
     * @param  int               $lifeTime
     * @return ElcaLcaProcessor
     */
    public function computeProjectVariant(ElcaProjectVariant $variant, $processDbId = null, $lifeTime = null)
    {
        if (!$lifeTime || !$processDbId) {
            $Project     = $variant->getProject();
            $lifeTime    = $lifeTime ? $lifeTime : $Project->getLifeTime();
            $processDbId = $processDbId ? $processDbId : $Project->getProcessDbId();
        }

        /**
         * Elements
         */
        foreach (ElcaElementSet::findByProjectVariantId($variant->getId()) as $Element) {
            $this->computeElement($Element, $processDbId, $lifeTime);
        }

        /**
         * Final energy demands
         */
        $this->computeFinalEnergy($variant);

        /**
         * Transports
         */
        $this->computeTransports($variant);

        /**
         * Notify observers
         */
        foreach ($this->processors as $processor) {
            $processor->afterRecomputation($variant, $processDbId, $lifeTime);
        }

        $this->logger->debug('CURRENT MEMORY USAGE: ' . File::formatFileSize(memory_get_usage()), __METHOD__);

        return $this;
    }
    // End computeProjectVariant

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Computes the lca and mass for a single element and its components
     *
     * @param  ElcaElement $Element
     * @param  int         $processDbId
     * @param  int         $lifeTime
     * @param null         $compositeItemId
     * @return ElcaLcaProcessor
     */
    public function computeElement(ElcaElement $Element, $processDbId = null, $lifeTime = null, $compositeItemId = null)
    {
        if (!$lifeTime || !$processDbId) {
            $Project     = $Element->getProjectVariant()->getProject();
            $lifeTime    = $lifeTime ? $lifeTime : $Project->getLifeTime();
            $processDbId = $processDbId ? $processDbId : $Project->getProcessDbId();
        }

        /**
         * Invoke additional lca processors
         */
        foreach ($this->processors as $Processor) {
            $Processor->beforeElementProcessing($Element, $processDbId, $lifeTime);
        }

        /**
         * Update/insert new results
         */
        $CacheElement = $this->cache->storeElement(
            $Element,
            0,
            $Element->getQuantity(),
            $Element->getRefUnit(),
            $compositeItemId
        );

        if ($Element->isComposite()) {
            /**
             * Compute indicators for each associated element
             */
            foreach ($Element->getCompositeElements([], true) as $SubCompositeElement) {
                $this->computeElement(
                    $SubCompositeElement->getElement(),
                    $processDbId,
                    $lifeTime,
                    $CacheElement->getItemId()
                );
            }
        } else {
            /**
             * Compute indicators for each component
             */
            foreach ($Element->getComponents() as $Component) {
                $this->computeElementComponent($Component, $processDbId, $lifeTime);
            }
        }

        /**
         * Since the mass of an element gets computed by the updateCache procedure,
         * free the cache results of the element to avoid displaying a zero mass
         */
        DbObjectCache::freeByObject($CacheElement);

        /**
         * Invoke additional lca processors
         */
        foreach ($this->processors as $Processor) {
            $Processor->afterElementProcessing($Element, $CacheElement);
        }

        return $this;
    }
    // End computeElement


    /**
     * Computes lca and the mass for the given element component
     *
     * @param  ElcaElementComponent $component
     * @param  int                  $processDbId
     * @param  int                  $projectLifeTime - project lifeTime for maintenance calculation
     * @throws Exception
     * @return ElcaLcaProcessor
     */
    public function computeElementComponent(
        ElcaElementComponent $component, $processDbId = null, $projectLifeTime = null
    ) {
        $project = $component->getElement()->getProjectVariant()->getProject();

        $projectLifeTime = $projectLifeTime ?? $project->getLifeTime();
        $processDbId     = $processDbId ?? $project->getProcessDbId();

        /**
         * Init result set
         */
        $componentResults = null;

        $this->cache->removeElementComponent($component->getId());

        /**
         * Skip this component if lca is not enabled
         */
        if ($component->getCalcLca()) {
            $lifeCycleUsages = $this->lifeCycleUsageService->findLifeCycleUsagesForProject(new ProjectId($project->getId()));

            $processLifeCycle = $this->processLifeCycleRepository->findById(
                new ProcessConfigId($component->getProcessConfigId()),
                new ProcessDbId($processDbId)
            );

            /**
             * Compute the quantity of the component
             */
            $quantity = $this->computeElementComponentQuantity($component);

            $componentLcaCalculator = new ElementComponentLcaCalculator(
                $lifeCycleUsages,
                $projectLifeTime,
                $this->indicatorRepository->findForProcessingByProcessDbId(new ProcessDbId($processDbId)),
                $this->logger
            );

            $componentResults = $componentLcaCalculator
                ->compute(
                    $processLifeCycle,
                    $quantity,
                    new UsefulLife(
                        (int)$component->getLifeTime(),
                        (int)$component->getLifeTimeDelay()
                    ),
                    $component->isExtant()
                );
        }

        /**
         * Store indicator results
         */
        if (null !== $componentResults) {
            $cacheItemComponent = $this->cache->storeElementComponent(
                $component->getId(),
                $componentResults->quantity(),
                $componentResults->mass(),
                $componentResults->numReplacements()
            )->getItem();

            $isExtant = $component->isExtant();
            foreach ($componentResults->indicatorResults() as $indicatorResults) {
                $this->cache->storeIndicators(
                    $cacheItemComponent,
                    $indicatorResults,
                    $isExtant && $indicatorResults->module()->stage()->isProduction(),
                    $componentResults->a13HasBeenAggregated() && $indicatorResults->module()->isA1A2OrA3()
                );
            }
        }

        return $this;
    }

    /**
     * Conputes the quantity for an ElementComponent
     *
     * @param ElcaElementComponent $elcaElementComponent
     * @return Quantity
     */
    public function computeElementComponentQuantity(ElcaElementComponent $elcaElementComponent): Quantity
    {
        $elementComponent = ElementComponentQuantity::fromElcaElementComponent($elcaElementComponent);

        /**
         * Convert quantity into outUnits
         */
        $convertedQuantity = $elementComponent->convertedQuantity();

        $this->logger->debug(
            sprintf(
                '%s %s: apply conversion: %s >> %s',
                $elcaElementComponent->isLayer()
                    ? $elcaElementComponent->getLayerPosition() . '. Layer'
                    : 'Component',
                $elcaElementComponent->getProcessConfig()->getName(),
                $elementComponent->quantity(),
                $convertedQuantity
            ),
            __METHOD__
        );

        return $convertedQuantity;
    }

    /**
     * Computes all final energy related demands and supplies for the given project variant
     *
     * @param  ElcaProjectVariant $projectVariant
     * @return ElcaLcaProcessor
     */
    public function computeFinalEnergy(ElcaProjectVariant $projectVariant)
    {
        $project     = $projectVariant->getProject();
        $processDbId = $project->getProcessDbId();
        $lifeTime    = $project->getLifeTime();
        $ngfEnEv     = ElcaProjectEnEv::findByProjectVariantId($projectVariant->getId())->getNgf();
        $indicators  = $this->indicatorRepository->findForProcessingByProcessDbId(new ProcessDbId($processDbId));

        $finalEnergyDemands = ElcaProjectFinalEnergyDemandSet::findByProjectVariantId($projectVariant->getId());
        $this->computeFinalEnergyDemand(
            $projectVariant->getId(),
            $finalEnergyDemands,
            $processDbId,
            $lifeTime,
            $ngfEnEv,
            $indicators
        );


        $finalEnergySupplies = ElcaProjectFinalEnergySupplySet::findByProjectVariantId($projectVariant->getId());
        $this->computeFinalEnergySupply(
            $projectVariant->getId(),
            $finalEnergySupplies,
            $lifeTime,
            $processDbId,
            $ngfEnEv,
            $indicators
        );

        /**
         * Calculate reference model indicators
         */
        $this->computeFinalEnergyReferenceModel(
            $projectVariant,
            $ngfEnEv,
            $lifeTime,
            $processDbId,
            ElcaProjectFinalEnergyRefModelSet::findByProjectVariantId($projectVariant->getId()),
            ElcaBenchmarkRefProcessConfigSet::find(['benchmark_version_id' => $project->getBenchmarkVersionId()]),
            $indicators
        );

        return $this;
    }


    /**
     * @throws Exception
     * @param                                 $projectVariantId
     * @param ElcaProjectFinalEnergyDemandSet $finalEnergyDemandSet
     * @param                                 $processDbId
     * @param                                 $lifeTime
     * @param                                 $ngfEnEv
     */
    public function computeFinalEnergyDemand(
        $projectVariantId,
        ElcaProjectFinalEnergyDemandSet $finalEnergyDemandSet,
        $processDbId,
        $lifeTime,
        $ngfEnEv,
        array $indicators
    ) {
        /**
         * Invoke additional lca processors
         */
        foreach ($this->processors as $Processor) {
            $Processor->beforeFinalEnergyDemandProcessing($projectVariantId, $finalEnergyDemandSet, $processDbId, $lifeTime, $ngfEnEv);
        }

        /**
         * Delete old values
         */
        $this->cache->removeFinalEnergyDemands($projectVariantId);

        $calculator = new FinalEnergyLcaCalculator(
            $indicators,
            $this->logger
        );

        /**
         * Calculate new values
         */
        foreach ($finalEnergyDemandSet as $finalEnergyDemand) {
            $processConfigId  = new ProcessConfigId($finalEnergyDemand->getProcessConfigId());

            $processLifeCycle = $this->processLifeCycleRepository->findById(
                $processConfigId,
                new ProcessDbId($processDbId)
            );

            $qE = 0;
            foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
                $qE += (float)$finalEnergyDemand->__get($property);
            }

            if (!$processConfig = $this->processConfigRepository->findById($processConfigId)) {
                continue;
            }

            if (!$fHsHi = $processConfig->energyEfficiency()) {
                $fHsHi = 1;
            }

            $quantity = $qE / $fHsHi * $ngfEnEv * $lifeTime;

            $lcaResults = $calculator->compute($processLifeCycle, new Quantity($quantity, Unit::kWh()));

            $cacheItemDemand = $this->cache->storeFinalEnergyDemand(
                $finalEnergyDemand->getId(),
                $qE,
                Elca::UNIT_KWH
            )->getItem();

            foreach ($lcaResults->indicatorResults() as $indicatorResults) {
                $this->cache->storeIndicators(
                    $cacheItemDemand,
                    $indicatorResults
                );
            }
        }

        /**
         * Invoke additional lca processors
         */
        foreach ($this->processors as $Processor) {
            $Processor->afterFinalEnergyDemandProcessing($projectVariantId, $finalEnergyDemandSet);
        }
    }

    /**
     * @param                                 $projectVariantId
     * @param ElcaProjectFinalEnergySupplySet $finalEnergySupplies
     * @param                                 $lifeTime
     * @param                                 $processDbId
     */
    public function computeFinalEnergySupply(
        $projectVariantId,
        ElcaProjectFinalEnergySupplySet $finalEnergySupplies,
        $lifeTime,
        $processDbId,
        $ngfEnEv,
        array $indicators
    ) {
        /**
         * Invoke additional lca processors
         */
        foreach ($this->processors as $Processor) {
            $Processor->beforeFinalEnergySupplyProcessing($projectVariantId, $finalEnergySupplies, $processDbId, $lifeTime, $ngfEnEv);
        }

        /**
         * Delete old values
         */
        $this->cache->removeFinalEnergySupplies($projectVariantId);

        $calculator = new FinalEnergyLcaCalculator(
            $indicators,
            $this->logger
        );

        /**
         * Calculate new values
         *
         * @var ElcaProjectFinalEnergySupply $finalEnergySupply
         */
        foreach ($finalEnergySupplies as $finalEnergySupply) {
            $processConfigId  = new ProcessConfigId($finalEnergySupply->getProcessConfigId());
            $processLifeCycle = $this->processLifeCycleRepository->findById(
                $processConfigId,
                new ProcessDbId($processDbId)
            );

            $qE = $finalEnergySupply->getQuantity() * (1 - $finalEnergySupply->getEnEvRatio());

            $processConfigAttribute = $this->processConfigRepository
                ->findAttributeForId(
                    $processConfigId,
                    ElcaProcessConfigAttribute::IDENT_OP_INVERT_VALUES
                );

            $inverter = $processConfigAttribute->value() ? -1 : 1;

            $quantity = $qE * $lifeTime * $inverter;

            $lcaResults = $calculator->compute($processLifeCycle, new Quantity($quantity, Unit::kWh()));

            $cacheItemSupply = $this->cache->storeFinalEnergySupply(
                $finalEnergySupply->getId(),
                $qE,
                Elca::UNIT_KWH
            )->getItem();

            foreach ($lcaResults->indicatorResults() as $indicatorResults) {
                $this->cache->storeIndicators(
                    $cacheItemSupply,
                    $indicatorResults->changeModule(Module::d())
                );
            }
        }

        /**
         * Invoke additional lca processors
         */
        foreach ($this->processors as $Processor) {
            $Processor->afterFinalEnergySupplyProcessing($projectVariantId, $finalEnergySupplies);
        }
    }

    /**
     * @param ElcaProjectVariant                $projectVariant
     * @param                                   $ngfEnEv
     * @param                                   $lifeTime
     * @param                                   $processDbId
     * @param ElcaProjectFinalEnergyRefModelSet $finalEnergyRefModels
     * @param ElcaBenchmarkRefProcessConfigSet  $benchmarkRefProcessConfigSet
     */
    public function computeFinalEnergyReferenceModel(
        ElcaProjectVariant $projectVariant,
        $ngfEnEv,
        $lifeTime,
        $processDbId,
        ElcaProjectFinalEnergyRefModelSet $finalEnergyRefModels,
        ElcaBenchmarkRefProcessConfigSet $benchmarkRefProcessConfigSet,
        array $indicators
    ) {
        /**
         * Delete old data
         */
        $this->cache->removeFinalEnergyRefModels($projectVariant->getId());

        $calculator = new FinalEnergyLcaCalculator(
            $indicators,
            $this->logger
        );

        /**
         * @var ElcaProjectFinalEnergyRefModel $refModel
         */
        foreach ($finalEnergyRefModels as $refModel) {

            /**
             * @var ElcaBenchmarkRefProcessConfig $benchmarkRefProcessConfig
             */
            if (!$benchmarkRefProcessConfig = $benchmarkRefProcessConfigSet->search('ident', $refModel->getIdent())) {
                $this->logger->warning(
                    'Found no processConfig for ref model '.$refModel->getIdent()
                    .' in project '.$projectVariant->getProject()->getName().' ['.$projectVariant->getProjectId()
                    .' / '.$projectVariant->getId().']',
                    __METHOD__
                );

                continue;
            }

            $processLifeCycle = $this->processLifeCycleRepository->findById(
                new ProcessConfigId($benchmarkRefProcessConfig->getProcessConfigId()),
                new ProcessDbId($processDbId)
            );

            $processConfig = $benchmarkRefProcessConfig->getProcessConfig();

            if (!$fHsHi = $processConfig->getFHsHi()) {
                $fHsHi = 1;
            }

            $qE = 0;
            foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
                $qE += (float)$refModel->$property;
            }

            $quantity = $qE / $fHsHi * $ngfEnEv * $lifeTime;

            $lcaResults = $calculator->compute($processLifeCycle, new Quantity($quantity, Unit::kWh()));

            $cacheItemRefModel = $this->cache->storeFinalEnergyRefModel(
                $refModel->getId(),
                $qE,
                Unit::KILOWATTHOUR
            )->getItem();

            foreach ($lcaResults->indicatorResults() as $indicatorResults) {
                $this->cache->storeIndicators(
                    $cacheItemRefModel,
                    $indicatorResults
                );
            }
        }
    }

    /**
     * Computes all transports for the given project variant
     *
     * @param  ElcaProjectVariant
     * @return ElcaLcaProcessor
     * @throws \Exception
     */
    public function computeTransports(ElcaProjectVariant $ProjectVariant)
    {
        $project   = $ProjectVariant->getProject();
        $processDb = $project->getProcessDb();

        /**
         * Delete old values
         */
        $this->cache->removeTransportMeans($ProjectVariant->getId());

        $transports = ElcaProjectTransportSet::findByProjectVariantId($ProjectVariant->getId());
        $indicators = $this->indicatorRepository->findForProcessingByProcessDbId(new ProcessDbId($processDb->getId()));

        /**
         * Calculate new values
         *
         * @var ElcaProjectTransport $Transport
         */
        foreach ($transports as $Transport) {
            $means        = ElcaProjectTransportMeanSet::findByProjectTransportId($Transport->getId());
            $quantity     = $Transport->getQuantity();
            $includeInLca = $Transport->getCalcLca();

            /** @var ElcaProjectTransportMean $transportMean */
            foreach ($means as $transportMean) {
                $processLifeCycle = $this->processLifeCycleRepository->findById(
                    new ProcessConfigId($transportMean->getProcessConfigId()),
                    new ProcessDbId($processDb->getId())
                );

                $processLcaCalculator = new ProcessLcaCalculator(
                    $processLifeCycle,
                    $indicators,
                    $this->logger
                );


                $result             = $quantity * $transportMean->getDistance() * $transportMean->getEfficiency();
                $cacheItemTransport = $this->cache->storeTransportMean(
                    $transportMean->getId(),
                    $result,
                    Elca::UNIT_TKM,
                    $includeInLca
                )->getItem();

                foreach ($processLifeCycle->processes() as $process) {
                    if (($processDb->isEn15804Compliant() && !$process->module()->isA4()) ||
                        (!$processDb->isEn15804Compliant() && !$process->module()->stage()->isUsage())
                    ) {
                        continue;
                    }

                    $indicatorResults = $processLcaCalculator->compute($process, new Quantity($result, Unit::tkm()));

                    $this->cache->storeIndicators(
                        $cacheItemTransport,
                        $indicatorResults
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Finishs the processing by updating the cache
     *
     * @param null $projectVariantId
     *
     * @throws Exception
     * @return ElcaLcaProcessor
     */
    public function updateCache($projectId, $projectVariantId = null)
    {
        if ($projectVariantId) {
            $CacheProjectVariantItem = ElcaCacheProjectVariant::findByProjectVariantId($projectVariantId)->getItem();
            $CacheProjectVariantItem->setIsOutdated(true);
            $CacheProjectVariantItem->update();
        }

        $this->cache->update($projectId);

        foreach ($this->processors as $processor) {
            $processor->afterCacheUpdate($projectId, $projectVariantId);
        }

        return $this;
    }
    // End updateCache


    /**
     * Reaggregates the element type tree for a given project variant and element type
     *
     * @param  int $projectVariantId
     * @param  int $elementTypeNodeId
     * @return ElcaLcaProcessor
     */
    public function updateElementTypeTree($projectVariantId, $elementTypeNodeId)
    {
        $this->cache->updateElementTypeTree($projectVariantId, $elementTypeNodeId);

        return $this;
    }
    // End updateElementTypeTree


    /**
     * Reaggregates the project variantor root cache item
     *
     * @param  int $projectVariantId
     * @return ElcaLcaProcessor
     */
    public function updateProjectVariant($projectVariantId)
    {
        $this->cache->updateProjectVariant($projectVariantId);

        return $this;
    }
}
// End EcoLcaProcessor
