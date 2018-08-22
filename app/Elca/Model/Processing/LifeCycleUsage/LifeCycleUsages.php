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

namespace Elca\Model\Processing\LifeCycleUsage;

use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Model\Process\Module;
use Elca\Model\Process\Stage;

class LifeCycleUsages implements \IteratorAggregate
{
    /**
     * @var LifeCycleUsage[]
     */
    private $usages;

    /**
     * @param LifeCycleUsage[] $usages
     */
    public function __construct(array $usages)
    {
        foreach ($usages as $usage) {
            $this->usages[(string)$usage->module()] = $usage;
        }
    }

    public function moduleIsAppliedInConstruction(Module $module): bool
    {
        if (isset($this->usages[(string)$module])) {
            return $this->usages[(string)$module]->applyInConstruction();
        }

        if ($module->isMaintenance()) {
            return true;
        }

        return false;
    }

    public function moduleIsAppliedInEnergy(Module $module): bool
    {
        if (!isset($this->usages[(string)$module])) {
            return false;
        }

        return $this->usages[(string)$module]->applyInEnergyDemand();
    }

    public function moduleIsAppliedInMaintenance(Module $module): bool
    {
        if (!isset($this->usages[(string)$module])) {
            return false;
        }

        return $this->usages[(string)$module]->applyInMaintenance();
    }


    public function moduleIsAppliedInTotals(Module $module): bool
    {
        return $this->moduleIsAppliedInConstruction($module) || $this->moduleIsAppliedInEnergy($module);
    }

    /**
     * @return Module[]
     */
    public function modulesAppliedInTotal(): array
    {
        $list = [];
        foreach ($this->usages as $usage) {
            if ($this->moduleIsAppliedInTotals($usage->module())) {
                $list[] = $usage->module();
            }
        }

        return $list;
    }

    /**
     * @return Module[]
     */
    public function modulesAppliedInMaintenance(): array
    {
        $list = [];
        foreach ($this->usages as $usage) {
            if ($this->moduleIsAppliedInMaintenance($usage->module())) {
                $list[] = $usage->module();
            }
        }

        return $list;
    }

    /**
     * @return Module[]
     */
    public function modulesAppliedInEol(): array
    {
        $list = [];
        foreach ([Module::C3, Module::C4, Stage::EOL] as $ident) {
            $module = new Module($ident);

            if ($this->moduleIsAppliedInConstruction($module)) {
                $list[$ident] = $module;
            }
        }

        return $list;
    }

    /**
     * @return Stage[]
     */
    public function stagesAppliedInTotal(): array
    {
        $stages = [];
        foreach ($this->modulesAppliedInTotal() as $module) {
            $stage                  = $module->stage();
            $stages[(string)$stage] = $stage;
        }

        if (\count($this->modulesAppliedInMaintenance()) !== 0) {
            $stages[Stage::MAINT] = Stage::maintenance();
        }

        return $stages;
    }

    public function hasStageRec(): bool
    {
        return isset($this->usages[Module::D]) &&
               (
                   $this->usages[Module::D]->applyInConstruction() ||
                   $this->usages[Module::D]->applyInMaintenance()
               );
    }

    public function isDefault(): bool
    {
        foreach ($this->usages as $moduleName => $projectLifeCycleUsage) {
            if (ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$moduleName] !== $projectLifeCycleUsage->applyInConstruction(
                )) {
                return false;
            }

            if (ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$moduleName] !== $projectLifeCycleUsage->applyInMaintenance(
                )) {
                return false;
            }

            if (ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$moduleName] !== $projectLifeCycleUsage->applyInEnergyDemand(
                )) {
                return false;
            }
        }

        return true;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->usages);
    }
}
