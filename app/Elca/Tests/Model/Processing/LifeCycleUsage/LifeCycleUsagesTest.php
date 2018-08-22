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

namespace Elca\Tests\Model\Processing\LifeCycleUsage;

use Elca\Model\Process\Module;
use Elca\Model\Process\Stage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsageId;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use PHPUnit\Framework\TestCase;

class LifeCycleUsagesTest extends TestCase
{
    public function test_moduleIsAppliedInConstruction_returns_true()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::A13,
                    true
                ),
            ]
        );

        $this->assertTrue($usages->moduleIsAppliedInConstruction(Module::a13()));
    }

    public function test_moduleIsAppliedInConstruction_returns_false()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::A13,
                    false
                ),
            ]
        );

        $this->assertFalse($usages->moduleIsAppliedInConstruction(Module::a13()));
    }

    public function test_moduleIsAppliedInEnergy_returns_true()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::B6,
                    false, false, true
                ),
            ]
        );

        $this->assertTrue($usages->moduleIsAppliedInEnergy(Module::b6()));
    }

    public function test_moduleIsAppliedInEnergy_returns_false()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::B6,
                    false, false, false
                ),
            ]
        );

        $this->assertFalse($usages->moduleIsAppliedInEnergy(Module::b6()));
    }

    public function test_moduleIsAppliedInMaintenance_returns_true()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::A13,
                    false, true, false
                ),
            ]
        );

        $this->assertTrue($usages->moduleIsAppliedInMaintenance(Module::a13()));
    }
    public function test_moduleIsAppliedInMaintenance_returns_false()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::A13,
                    false, false, false
                ),
            ]
        );

        $this->assertFalse($usages->moduleIsAppliedInMaintenance(Module::a13()));
    }

    public function test_moduleIsAppliedInTotals_returns_true()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::A13,
                    true, false, false
                ),
                $this->given_life_cycle_usage(
                    Module::B6,
                    false, false, true
                ),
            ]
        );

        $this->assertTrue($usages->moduleIsAppliedInTotals(Module::a13()));
        $this->assertTrue($usages->moduleIsAppliedInTotals(Module::b6()));
    }

    public function test_moduleIsAppliedInTotals_returns_false()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(
                    Module::A13,
                    false, false, false
                ),
            ]
        );

        $this->assertFalse($usages->moduleIsAppliedInTotals(Module::a13()));
    }

    public function test_modulesAppliedInTotal()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(Module::A13,true, false, false),
                $this->given_life_cycle_usage(Module::B6,false, false, true),
                $this->given_life_cycle_usage(Module::C3,true, false, false),
                $this->given_life_cycle_usage(Module::D,false, false, false),
                $this->given_life_cycle_usage(Stage::PROD,true, false, false),
            ]
        );

        $this->assertEquals([
            Module::a13(),
            Module::b6(),
            Module::c3(),
            Module::production(),
            ],
            $usages->modulesAppliedInTotal()
        );
    }

    public function test_modulesAppliedInMaintenance()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(Module::A13,true, true, false),
                $this->given_life_cycle_usage(Module::B6,false, false, true),
                $this->given_life_cycle_usage(Module::C3,true, true, false),
                $this->given_life_cycle_usage(Module::D,false, false, false),
            ]
        );

        $this->assertEquals([
            Module::a13(),
            Module::c3(),
        ],
            $usages->modulesAppliedInMaintenance()
        );
    }

    public function test_modulesAppliedInEol()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(Module::A13,true, false, false),
                $this->given_life_cycle_usage(Module::B6,false, false, true),
                $this->given_life_cycle_usage(Module::C3,true, false, false),
                $this->given_life_cycle_usage(Module::D,false, false, false),
            ]
        );

        $this->assertEquals([
            Module::c3(),
        ],
            array_values($usages->modulesAppliedInEol())
        );
    }

    public function test_stagesAppliedInTotal()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(Module::A13,true, false, false),
                $this->given_life_cycle_usage(Module::B6,false, false, true),
                $this->given_life_cycle_usage(Module::C3,true, false, false),
                $this->given_life_cycle_usage(Module::D,false, false, false),
            ]
        );

        $this->assertEquals([
            Stage::production(),
            Stage::usage(),
            Stage::endOfLife(),
        ],
            array_values($usages->stagesAppliedInTotal())
        );
    }
    public function test_stagesAppliedInTotal_includes_maintenance()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(Module::A13,true, true, false),
                $this->given_life_cycle_usage(Module::B6,false, false, true),
            ]
        );

        $this->assertEquals([
            Stage::production(),
            Stage::usage(),
            Stage::maintenance(),
        ],
            array_values($usages->stagesAppliedInTotal())
        );
    }

    public function test_hasStageRec_returns_true()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(Module::A13,true, false, false),
                $this->given_life_cycle_usage(Module::B6,false, false, true),
                $this->given_life_cycle_usage(Module::C3,true, false, false),
                $this->given_life_cycle_usage(Module::D,true, false, false),
            ]
        );

        $this->assertTrue($usages->hasStageRec());
    }

    public function test_hasStageRec_returns_false()
    {
        $usages = new LifeCycleUsages(
            [
                $this->given_life_cycle_usage(Module::A13,true, false, false),
                $this->given_life_cycle_usage(Module::B6,false, false, true),
                $this->given_life_cycle_usage(Module::C3,true, false, false),
                $this->given_life_cycle_usage(Module::D,false, false, false),
            ]
        );

        $this->assertFalse($usages->hasStageRec());
    }

    private function given_life_cycle_usage(
        string $module, bool $applyInConstruction = false, bool $applyInMaintenance = false,
        bool $applyInEnergy = false
    ): LifeCycleUsage {
        static $id = 0;

        return new LifeCycleUsage(
            new LifeCycleUsageId(++$id),
            new ProjectId(1),
            new Module($module),
            $applyInConstruction,
            $applyInMaintenance,
            $applyInEnergy
        );
    }
}
