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
namespace Lcc\Model\Processing;

use Bnb\Model\Event\WaterUpdated;
use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectVariant;
use Elca\Model\Event\Event;
use Elca\Model\Event\EventName;
use Elca\Model\Event\EventSubscriber;
use Elca\Model\Processing\ElcaLcaProcessingObserver;
use Lcc\Db\LccElementCost;
use Lcc\Db\LccProjectVersion;
use Lcc\LccModule;

/**
 * LcaProcessor interface
 *
 * Register your lca processors with
 *
 *   $this->container->get(ElcaLcaProcessor::class)->registerLcaProcessor($Processor);
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class LccProcessingObserver implements ElcaLcaProcessingObserver, EventSubscriber
{
    /**
     * Called after cache update
     *
     * @param      $projectId
     * @param null $projectVariantId
     */
    public function afterCacheUpdate($projectId, $projectVariantId = null)
    {
    }

    /**
     * Called after re-computation
     *
     * @param  ElcaProjectVariant $ProjectVariant
     * @param  int         $processDbId  - The associated processDbId
     * @param  int         $lifeTime     - The project lifeTime
     */
    public function afterRecomputation(ElcaProjectVariant $ProjectVariant, $processDbId, $lifeTime)
    {
        /**
         * Find project versions and compute lcc
         */
        $ProjectVersion = LccProjectVersion::findByPK($ProjectVariant->getId(), LccModule::CALC_METHOD_GENERAL);
        if ($ProjectVersion->isInitialized()) {
            $ProjectVersion->computeLcc();
        }

        $ProjectVersion = LccProjectVersion::findByPK($ProjectVariant->getId(), LccModule::CALC_METHOD_DETAILED);
        if ($ProjectVersion->isInitialized()) {
            $ProjectVersion->computeLcc();
        }
    }
    // End projectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called before element computation
     *
     * @param  ElcaElement $Element      - The element to compute
     * @param  int         $processDbId  - The associated processDbId
     * @param  int         $lifeTime     - The project lifeTime
     */
    public function beforeElementProcessing(ElcaElement $Element, $processDbId, $lifeTime) {}

    /**
     * Called after element computation
     *
     * @param  ElcaElement $element      - The element to compute
     * @param  ElcaCacheElement $CacheElement  - cached results
     */
    public function afterElementProcessing(ElcaElement $element, ElcaCacheElement $CacheElement)
    {
        $elementCost = LccElementCost::findByElementId($element->getId());

        if (!$elementCost->isInitialized()) {
            return;
        }

        $elementCost->update();

        $ProjectVersion = LccProjectVersion::findByPK($element->getProjectVariantId(), LccModule::CALC_METHOD_DETAILED);
        if ($ProjectVersion->isInitialized()) {
            $ProjectVersion->computeLcc();
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called before final energy demand computation
     *
     * @param  ElcaProjectFinalEnergyDemandSet $finalEnergyDemands   - The final energy demands to compute
     * @param  int         $processDbId  - The associated processDbId
     * @param  int         $lifeTime     - The project lifeTime
     * @param  int         $ngfEnEv      - The ngf enEv
     */
    public function beforeFinalEnergyDemandProcessing($projectVariantId, ElcaProjectFinalEnergyDemandSet $finalEnergyDemands, $processDbId, $lifeTime, $ngfEnEv) {}

    /**
     * Called after final energy demand computation
     *
     * @param ElcaProjectFinalEnergyDemandSet $finalEnergyDemands
     * @throws \Exception
     */
    public function afterFinalEnergyDemandProcessing($projectVariantId, ElcaProjectFinalEnergyDemandSet $finalEnergyDemands)
    {
        /**
         * Find project version
         */
        $projectVersion = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_DETAILED);
        if (!$projectVersion->isInitialized()) {
            return;
        }

        $method = new DetailedMethod($projectVariantId, $projectVersion);
        $method->updateFinalEnergyDemands($finalEnergyDemands);
        $method->computeLcc();
    }

    /**
     * Called before final energy supply computation
     *
     * @param  ElcaProjectFinalEnergySupplySet $FinalEnergySupplies - The final energy demands to compute
     * @param  int                             $processDbId         - The associated processDbId
     * @param  int                             $lifeTime            - The project lifeTime
     * @param  int                             $ngfEnEv             - The ngf enEv
     */
    public function beforeFinalEnergySupplyProcessing($projectVariantId, ElcaProjectFinalEnergySupplySet $FinalEnergySupplies, $processDbId, $lifeTime, $ngfEnEv) {}

    /**
     * Called after final energy supply computation
     *
     * @param  ElcaProjectFinalEnergySupplySet $finalEnergySupplies - The final energy demands to compute
     */
    public function afterFinalEnergySupplyProcessing($projectVariantId, ElcaProjectFinalEnergySupplySet $finalEnergySupplies)
    {
        /**
         * Find project version
         */
        $projectVersion = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_DETAILED);
        if (!$projectVersion->isInitialized()) {
            return;
        }

        $method = new DetailedMethod($projectVariantId, $projectVersion);
        $method->updateFinalEnergySupplies($finalEnergySupplies);
        $method->computeLcc();
    }


    /**
     * @param Event $event
     * @return bool
     */
    public function isSubscribedTo(Event $event)
    {
        if ($event instanceof WaterUpdated) {
            return true;
        }

        return false;
    }

    /**
     * @param Event $event
     */
    public function handle(Event $event)
    {
        $name = EventName::fromEvent($event);
        $methodName = sprintf('on%s', $name);
        
        $this->$methodName($event);
    }

    /**
     *
     */
    public function onWaterUpdated(WaterUpdated $event)
    {
        /**
         * Find project version
         */
        $projectVersion = LccProjectVersion::findByPK($event->projectVariantId(), LccModule::CALC_METHOD_DETAILED);
        if (!$projectVersion->isInitialized()) {
            return;
        }
        
        $method = new DetailedMethod($event->projectVariantId(), $projectVersion);
        $method->updateAll();
    }
}
// End LccProcessing