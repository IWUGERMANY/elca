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

namespace Elca\Service\Project\ProjectVariant;

use Beibob\Blibs\DbHandle;
use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectIndicatorBenchmarkSet;
use Elca\Db\ElcaProjectKwk;
use Elca\Db\ElcaProjectLocation;
use Elca\Db\ElcaProjectTransportSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Processing\ElcaProjectVariantObserver;
use Elca\Service\Element\ElementService;
use Exception;

class ProjectVariantService
{
    /**
     * @var DbHandle
     */
    private $dbh;

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    /**
     * @var array
     */
    private $projectVariantObservers;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @param array            $projectVariantObservers
     * @param DbHandle         $dbh
     * @param ElcaLcaProcessor $lcaProcessor
     * @param ElementService   $elementService
     */
    public function __construct(array $projectVariantObservers, DbHandle $dbh, ElcaLcaProcessor $lcaProcessor, ElementService $elementService)
    {
        $this->projectVariantObservers = $projectVariantObservers;
        $this->dbh = $dbh;
        $this->lcaProcessor = $lcaProcessor;
        $this->elementService = $elementService;
    }

    /**
     * Creates a deep copy from a projectVariant
     *
     * @param ElcaProjectVariant $projectVariant
     * @param  int               $projectId   new project id
     * @param null               $phaseId
     * @param  bool              $useSameName -> namen 1:1 kopieren
     * @param null               $accessGroupId
     * @return ElcaProjectVariant|null
     * @throws Exception
     */
    public function copy(ElcaProjectVariant $projectVariant, $projectId, $phaseId = null, $useSameName = false, $accessGroupId = null)
    {
        if (!$projectVariant->isInitialized() || !$projectId)
            return null;

        try {
            $this->dbh->begin();

            $copy = ElcaProjectVariant::create(
                $projectId,
                $phaseId ? $phaseId : $projectVariant->getPhaseId(),
                $useSameName ? $projectVariant->getName() : t('Kopie von').' '.$projectVariant->getName(),
                $projectVariant->getDescription()
            );
            $location = ElcaProjectLocation::findByProjectVariantId($projectVariant->getId());
            if (!$location->isInitialized()) {
                throw new Exception('Copy ProjectVariant: no ProjectLocation found for id '.$projectVariant->getId());
            }
            $location->copy($copy->getId());

            $construction = ElcaProjectConstruction::findByProjectVariantId($projectVariant->getId());
            if (!$construction->isInitialized())
                throw new Exception('Copy ProjectVariant no ProjectConstruction found for id ' . $projectVariant->getId());
            $construction->copy($copy->getId());

            /**
             * Final energy demands
             * @var ElcaProjectFinalEnergyDemand $finalEnergyDemand
             */
            $finalEnergyDemandSet = ElcaProjectFinalEnergyDemandSet::findByProjectVariantId($projectVariant->getId());
            foreach ($finalEnergyDemandSet as $finalEnergyDemand) {
                if ($finalEnergyDemand->isKwk()) {
                    continue;
                }

                $finalEnergyDemand->copy($copy->getId());
            }

            // copy kwk and kwk demands
            $projectKwk = ElcaProjectKwk::findByProjectVariantId($projectVariant->getId());
            if ($projectKwk->isInitialized()) {
                $projectKwk->copy($copy->getId());
            }

            /**
             * Final energy supplies
             */
            $FinalEnergySupplySet = ElcaProjectFinalEnergySupplySet::findByProjectVariantId($projectVariant->getId());
            foreach ($FinalEnergySupplySet as $FinalEnergySupply)
                $FinalEnergySupply->copy($copy->getId());

            $ProjectEnEv = ElcaProjectEnEv::findByProjectVariantId($projectVariant->getId());
            if ($ProjectEnEv->isInitialized())
                $ProjectEnEv->copy($copy->getId());

            if ($finalEnergyDemandSet->count() || $FinalEnergySupplySet->count())
                $this->lcaProcessor->computeFinalEnergy($copy);

            /**
             * Transports
             */
            $ProjectTransportSet = ElcaProjectTransportSet::findByProjectVariantId($projectVariant->getId());
            foreach ($ProjectTransportSet as $Transport) {
                $Transport->copy($copy->getId());
            }

            if ($ProjectTransportSet->count())
                $this->lcaProcessor->computeTransports($copy);

            /**
             * Deep copy of all project variant elements
             */
            $ElementSet = ElcaElementSet::find(['project_variant_id' => $projectVariant->getId()]);
            foreach ($ElementSet as $element) {
                /**
                 * Skip if element has composite elements. This will be copied by the
                 * composite element itself
                 */
                if ($element->hasCompositeElement())
                    continue;

                /**
                 * Skip if element is a sub assistant element. This will be copied by the
                 * assistant element itself
                 */
                $assistantElement = ElcaAssistantElement::findByElementId($element->getId());
                if ($assistantElement->isInitialized() && $assistantElement->getMainElementId() !== $element->getId()) {
                    continue;
                }

                $this->elementService->copyElementFrom(
                    $element,
                    $element->getOwnerId(),
                    $copy->getId(),
                    $accessGroupId,
                    true, // copy name as-is
                    true, // copy cache items
                    null,
                    null,
                    true
                );
            }

            /**
             * Copy all indicator benchmarks
             */
            foreach (ElcaProjectIndicatorBenchmarkSet::find(['project_variant_id' => $projectVariant->getId()]) as $Benchmark)
                $Benchmark->copy($copy->getId());

            /**
             * Trigger observer
             */
            foreach ($this->projectVariantObservers as $observer) {
                if (!$observer instanceof ElcaProjectVariantObserver) {
                    continue;
                }
                $observer->onProjectVariantCopy($projectVariant, $copy);
            }

            if ($ElementSet->count() || $finalEnergyDemandSet->count()) {
                $this->lcaProcessor->updateCache($copy->getProjectId(), $copy->getId());
            }

            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }

        return $copy;
    }
    // End copy

}
