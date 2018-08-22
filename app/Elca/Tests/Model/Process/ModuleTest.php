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

namespace Elca\Tests\Model\Process;

use Elca\Model\Process\Module;
use Elca\Model\Process\Stage;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    /**
     * @dataProvider moduleStageProvider
     */
    public function testStage($module, $stage)
    {
        $module = new Module($module);
        $stage = new Stage($stage);

        $this->assertTrue($module->stage()->equals($stage));
    }

    /**
     * @dataProvider moduleStageProvider
     */
    public function testIsLegacy($moduleStr, $stageStr)
    {
        $module = new Module($moduleStr);

        $this->assertSame($moduleStr === $stageStr, $module->isLegacy());
    }

    public function moduleStageProvider()
    {
        return [
            ['prod', 'prod',],
            ['op', 'op',],
            ['eol', 'eol',],
            ['A1', 'prod',],
            ['A2', 'prod',],
            ['A3', 'prod',],
            ['A1-3', 'prod',],
            ['A4', 'prod',],
            ['A5', 'prod',],
            ['B1', 'op',],
            ['B2', 'op',],
            ['B3', 'op',],
            ['B4', 'op',],
            ['B5', 'op',],
            ['B6', 'op',],
            ['B7', 'op',],
            ['C1', 'eol',],
            ['C2', 'eol',],
            ['C3', 'eol',],
            ['C4', 'eol',],
            ['D', 'rec',],
        ];
    }


}
