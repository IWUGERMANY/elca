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

namespace Elca\Tests\Model\ProcessConfig\Conversion;

use Elca\Model\Common\Unit;
use Elca\Model\ProcessConfig\Conversion\ConversionSet;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\RequiredConversion;
use PHPUnit\Framework\TestCase;

class ConversionSetTest extends TestCase
{
    public function test_add_adds_conversion()
    {
        $set = new ConversionSet();
        $set->add(new RequiredConversion(Unit::kg(), Unit::m()));

        $this->assertTrue($set->has(Unit::kg(), Unit::m()));
    }

    public function test_add_does_not_add_conversion_twice()
    {
        $set = new ConversionSet();
        $set->add(new RequiredConversion(Unit::kg(), Unit::m()));
        $set->add(new RequiredConversion(Unit::kg(), Unit::m()));

        $this->assertCount(1, $set);
    }

    public function test_add_does_not_add_inverted_conversion()
    {
        $set = new ConversionSet();
        $set->add(new RequiredConversion(Unit::kg(), Unit::m()));
        $set->add(new RequiredConversion(Unit::m(), Unit::kg()));

        $this->assertCount(1, $set);
    }

    public function test_find_returns_conversion()
    {
        $set = new ConversionSet();
        $set->add(new LinearConversion(Unit::kg(), Unit::m(), 2));

        /**
         * @var LinearConversion $conversion
         */
        $conversion = $set->find(Unit::kg(), Unit::m());

        $this->assertNotNull($conversion);
        $this->assertSame(2.0, $conversion->factor());
        $this->assertSame(Unit::KILOGRAMM, $conversion->fromUnit()->value());
        $this->assertSame(Unit::METER, $conversion->toUnit()->value());
    }

    public function test_find_returns_inverted_conversion()
    {
        $set = new ConversionSet();
        $set->add(new LinearConversion(Unit::kg(), Unit::m(), 2));

        /**
         * @var LinearConversion $conversion
         */
        $conversion = $set->find(Unit::m(), Unit::kg());

        $this->assertNotNull($conversion);
        $this->assertSame(0.5, $conversion->factor());
        $this->assertSame(Unit::METER, $conversion->fromUnit()->value());
        $this->assertSame(Unit::KILOGRAMM, $conversion->toUnit()->value());
    }
}
