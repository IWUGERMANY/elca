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

namespace Elca\Service\Project;

use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecificationSet;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Db\ElcaProjectLifeCycleUsageSet;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsageRepository;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;

class LifeCycleUsageService
{
    /**
     * @var LifeCycleUsageRepository
     */
    private $lifeCycleUsageRepository;

    /**
     * @var LifeCycleUsages[]
     */
    private $lifeCycleUsages;

    public function __construct(LifeCycleUsageRepository $lifeCycleUsageRepository)
    {
        $this->lifeCycleUsageRepository = $lifeCycleUsageRepository;
    }

    public function findLifeCycleUsagesForProject(ProjectId $projectId): LifeCycleUsages
    {
        if (!isset($this->lifeCycleUsages[(string)$projectId])) {
            $this->lifeCycleUsages[(string)$projectId] = $this->lifeCycleUsageRepository->findByProjectId($projectId);
        }

        return $this->lifeCycleUsages[(string)$projectId];
    }

    /**
     * @param ElcaProject $project
     * @return bool - returns true if something has changed
     */
    public function updateForProject(ElcaProject $project)
    {
        $benchmarkVersionId = $project->getBenchmarkVersionId();

        if ($benchmarkVersionId !== null) {
            return $this->updateFromBenchmarkVersionId($project->getId(), $benchmarkVersionId);
        }

        return $this->setDefaultsByProcessDbId($project->getId(), $project->getProcessDbId());
    }

    /**
     * @param $oldProjectId
     * @param $newProjectId
     */
    public function copyFromProject($oldProjectId, $newProjectId)
    {
        foreach (ElcaProjectLifeCycleUsageSet::findByProjectId($oldProjectId) as $usage) {
            ElcaProjectLifeCycleUsage::create(
                $newProjectId,
                $usage->getLifeCycleIdent(),
                $usage->getUseInConstruction(),
                $usage->getUseInMaintenance(),
                $usage->getUseInEnergyDemand()
            );
        }
    }

    /**
     * @param $projectId
     * @param $benchmarkVersionId
     */
    private function updateFromBenchmarkVersionId($projectId, $benchmarkVersionId)
    {
        $changed = false;

        /**
         * Copy specifications from benchmark version
         *
         * @var ElcaBenchmarkLifeCycleUsageSpecification $spec
         */
        foreach (ElcaBenchmarkLifeCycleUsageSpecificationSet::findByBenchmarkVersionId($benchmarkVersionId) as $spec) {
            $lifeCycleUsage = ElcaProjectLifeCycleUsage::findByProjectIdAndLifeCycleIdent(
                $projectId,
                $spec->getLifeCycleIdent()
            );

            if ($lifeCycleUsage->isInitialized()) {

                if ($lifeCycleUsage->getUseInConstruction() !== $spec->getUseInConstruction()) {
                    $lifeCycleUsage->setUseInConstruction($spec->getUseInConstruction());
                    $changed = true;
                }

                if ($lifeCycleUsage->getUseInMaintenance() !== $spec->getUseInMaintenance()) {
                    $lifeCycleUsage->setUseInMaintenance($spec->getUseInMaintenance());
                    $changed = true;
                }

                if ($lifeCycleUsage->getUseInEnergyDemand() !== $spec->getUseInEnergyDemand()) {
                    $lifeCycleUsage->setUseInEnergyDemand($spec->getUseInEnergyDemand());
                    $changed = true;
                }

                if ($changed) {
                    $lifeCycleUsage->update();
                }
            }
            else {
                ElcaProjectLifeCycleUsage::create(
                    $projectId,
                    $spec->getLifeCycleIdent(),
                    $spec->getUseInConstruction(),
                    $spec->getUseInMaintenance(),
                    $spec->getUseInEnergyDemand()
                );
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param $projectId
     * @param $processDbId
     */
    private function setDefaultsByProcessDbId($projectId, $processDbId)
    {
        $changed = false;

        $lifeCycles = ElcaLifeCycleSet::findByProcessDbId($processDbId)
                                   ->getArrayCopy('ident');

        $allLcIdents = array_merge(
            ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults
        );

        foreach ($allLcIdents as $lcIdent => $foo) {
            if (!isset($lifeCycles[$lcIdent])) {
                continue;
            }

            $lifeCycleUsage = ElcaProjectLifeCycleUsage::findByProjectIdAndLifeCycleIdent(
                $projectId,
                $lcIdent
            );

            $useInConstr = isset(ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent]) &&
                           ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent];
            $useInMaint  = isset(ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent])  &&
                           ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent];
            $useInEnergy = isset(ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent]) &&
                           ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent];

            if ($lifeCycleUsage->isInitialized()) {

                if ($lifeCycleUsage->getUseInConstruction() !== $useInConstr) {
                    $lifeCycleUsage->setUseInConstruction($useInConstr);
                    $changed = true;
                }

                if ($lifeCycleUsage->getUseInMaintenance() !== $useInMaint) {
                    $lifeCycleUsage->setUseInMaintenance($useInMaint);
                    $changed = true;
                }

                if ($lifeCycleUsage->getUseInEnergyDemand() !== $useInEnergy) {
                    $lifeCycleUsage->setUseInEnergyDemand($useInEnergy);
                    $changed = true;
                }

                if ($changed) {
                    $lifeCycleUsage->update();
                }

                $lifeCycleUsage->setUseInConstruction($useInConstr);
                $lifeCycleUsage->setUseInMaintenance($useInMaint);
                $lifeCycleUsage->setUseInEnergyDemand($useInEnergy);
                $lifeCycleUsage->update();
            } else {
                ElcaProjectLifeCycleUsage::create(
                    $projectId,
                    $lcIdent,
                    $useInConstr,
                    $useInMaint,
                    $useInEnergy
                );

                $changed = true;
            }
        }

        return $changed;
    }
}
