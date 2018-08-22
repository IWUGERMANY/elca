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

use Elca\Model\Process\Stage;
use PHPUnit\Framework\TestCase;

class StageTest extends TestCase
{
    public function testProduction()
    {
        $stage = Stage::production();
        $this->assertSame(Stage::PROD, $stage->value());
    }

    public function testUsage()
    {
        $stage = Stage::usage();
        $this->assertSame(Stage::USE, $stage->value());
    }

    public function testEndOfLife()
    {
        $stage = Stage::endOfLife();
        $this->assertSame(Stage::EOL, $stage->value());
    }

    public function testRecycling()
    {
        $stage = Stage::recycling();
        $this->assertSame(Stage::REC, $stage->value());
    }

    public function testMaint()
    {
        $stage = Stage::maintenance();
        $this->assertSame(Stage::MAINT, $stage->value());
    }

    public function testTotal()
    {
        $stage = Stage::total();
        $this->assertSame(Stage::TOTAL, $stage->value());
    }

    public function testIsProduction()
    {
        $stage = Stage::production();
        $this->assertTrue($stage->isProduction());
    }

    public function testIsUsage()
    {
        $stage = Stage::usage();
        $this->assertTrue($stage->isUsage());
    }

    public function testIsEndOfLife()
    {
        $stage = Stage::endOfLife();
        $this->assertTrue($stage->isEndOfLife());
    }

    public function testIsRecycling()
    {
        $stage = Stage::recycling();
        $this->assertTrue($stage->isRecycling());
    }

    public function testIsMaintenance()
    {
        $stage = Stage::maintenance();
        $this->assertTrue($stage->isMaintenance());
    }

    public function testIsTotal()
    {
        $stage = Stage::total();
        $this->assertTrue($stage->isTotal());
    }
}
