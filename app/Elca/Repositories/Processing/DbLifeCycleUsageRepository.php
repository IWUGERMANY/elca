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

namespace Elca\Repositories\Processing;


use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Db\ElcaProjectLifeCycleUsageSet;
use Elca\Model\Process\Module;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsageId;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsageRepository;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Utils\Model\FactoryHelper;

class DbLifeCycleUsageRepository implements LifeCycleUsageRepository
{
    public function findByProjectId(ProjectId $projectId): LifeCycleUsages
    {
        $dbLifeCycleUsageSet = ElcaProjectLifeCycleUsageSet::findByProjectId($projectId->value());

        $usages = [];
        foreach ($dbLifeCycleUsageSet as $item) {
            $usages[$item->getLifeCycleIdent()] = $this->build($item);
        }

        return new LifeCycleUsages($usages);
    }

    private function build(ElcaProjectLifeCycleUsage $dbLifeCycleUsage) : LifeCycleUsage
    {
        return FactoryHelper::createInstanceWithoutConstructor(
            LifeCycleUsage::class,
            [
                'id' => new LifeCycleUsageId((int)$dbLifeCycleUsage->getId()),
                'projectId' => new ProjectId((int)$dbLifeCycleUsage->getProjectId()),
                'module' => new Module($dbLifeCycleUsage->getLifeCycleIdent()),
                'applyInConstruction' => $dbLifeCycleUsage->getUseInConstruction(),
                'applyInMaintenance' => $dbLifeCycleUsage->getUseInMaintenance(),
                'applyInEnergyDemand' => $dbLifeCycleUsage->getUseInEnergyDemand(),
            ]
        );
    }
}