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

use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectVariant;

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
interface ElcaLcaProcessingObserver
{
    /**
     * Called after cache update
     *
     * @param      $projectId
     * @param null $projectVariantId
     */
    public function afterCacheUpdate($projectId, $projectVariantId = null);

    /**
     * Called after re-computation
     *
     * @param  ElcaProjectVariant $ProjectVariant
     * @param  int         $processDbId  - The associated processDbId
     * @param  int         $lifeTime     - The project lifeTime
     */
    public function afterRecomputation(ElcaProjectVariant $ProjectVariant, $processDbId, $lifeTime);

    /**
     * Called before element computation
     *
     * @param  ElcaElement $Element      - The element to compute
     * @param  int         $processDbId  - The associated processDbId
     * @param  int         $lifeTime     - The project lifeTime
     */
    public function beforeElementProcessing(ElcaElement $Element, $processDbId, $lifeTime);

    /**
     * Called after element computation
     *
     * @param  ElcaElement $Element      - The element to compute
     * @param  ElcaCacheElement $CacheElement  - cached results
     */
    public function afterElementProcessing(ElcaElement $Element, ElcaCacheElement $CacheElement);

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called before final energy demand computation
     *
     * @param  ElcaProjectFinalEnergyDemandSet $FinalEnergyDemands   - The final energy demands to compute
     * @param  int         $processDbId  - The associated processDbId
     * @param  int         $lifeTime     - The project lifeTime
     * @param  int         $ngfEnEv      - The ngf enEv
     */
    public function beforeFinalEnergyDemandProcessing($projectVariantId, ElcaProjectFinalEnergyDemandSet $FinalEnergyDemands, $processDbId, $lifeTime, $ngfEnEv);

    /**
     * Called after final energy demand computation
     *
     * @param  ElcaProjectFinalEnergyDemandSet $FinalEnergyDemands   - The final energy demands to compute
     */
    public function afterFinalEnergyDemandProcessing($projectVariantId, ElcaProjectFinalEnergyDemandSet $FinalEnergyDemands);

    /**
     * Called before final energy supply computation
     *
     * @param  ElcaProjectFinalEnergySupplySet $FinalEnergySupplies   - The final energy demands to compute
     * @param  int         $processDbId  - The associated processDbId
     * @param  int         $lifeTime     - The project lifeTime
     * @param  int         $ngfEnEv      - The ngf enEv
     */
    public function beforeFinalEnergySupplyProcessing($projectVariantId, ElcaProjectFinalEnergySupplySet $FinalEnergySupplies, $processDbId, $lifeTime, $ngfEnEv);

    /**
     * Called after final energy supply computation
     *
     * @param  ElcaProjectFinalEnergySupplySet $FinalEnergySupplies   - The final energy demands to compute
     */
    public function afterFinalEnergySupplyProcessing($projectVariantId, ElcaProjectFinalEnergySupplySet $FinalEnergySupplies);
}
// End ElcaLcaProcessingInterface