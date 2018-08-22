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

namespace Elca\Service\Admin;

use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaLifeCycleSet;

class LifeCycleUsageSpecificationService
{
    /**
     * @param ElcaBenchmarkVersion $benchmarkVersion
     */
    public function initLifeCycleUsageSpecification(ElcaBenchmarkVersion $benchmarkVersion)
    {
        if (null === $benchmarkVersion->getProcessDbId()) {
            return;
        }

        $lifeCycles = ElcaLifeCycleSet::findByProcessDbId(
            $benchmarkVersion->getProcessDbId(),
            ['p_order' => 'ASC']
        )->getArrayCopy('ident');

        $allLcIdents = array_merge(
            ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults
        );

        // iterate over lifecycles to keep order
        foreach ($allLcIdents as $lcIdent => $lifeCycle) {

            if (!isset($lifeCycles[$lcIdent]) ||
                ElcaBenchmarkLifeCycleUsageSpecification::existsByBenchmarkVersionIdAndLifeCycleIdent(
                    $benchmarkVersion->getId(), $lcIdent)
            ) {
                continue;
            }

            ElcaBenchmarkLifeCycleUsageSpecification::create(
                $benchmarkVersion->getId(),
                $lcIdent,
                isset(ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent]) && ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent],
                isset(ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent]) && ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent],
                isset(ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent]) && ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent]
            );
        }
    }
}
