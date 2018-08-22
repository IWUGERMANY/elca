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

namespace Elca\Tests\Model\Common;

use Elca\Model\Common\CategoryClassId;
use PHPUnit\Framework\TestCase;

class CategoryClassIdTest extends TestCase
{
    /**
     * @dataProvider fromStringDataProvider
     */
    public function testFromString(string $fromString, string $result)
    {
        $classId = CategoryClassId::fromString($fromString);

        $this->assertSame($result, (string)$classId);
    }

    public function fromStringDataProvider()
    {
        return [
            ['1', '1'],
            ['1.2', '1.02'],
            ['1.12', '1.12'],
            ['1.2.03', '1.02.03'],
            ['1.02.03', '1.02.03'],
            ['1.2.03.1.3.12.5.22', '1.02.03.01.03.12.05.22'],
        ];
    }

    public function test_parent_returns_parent_classId()
    {
        $this->assertSame('1.02', (string)CategoryClassId::fromString('1.2.3')->parent());
        $this->assertSame('1.02.03', (string)CategoryClassId::fromString('1.2.3.4')->parent());
    }

    public function test_parent_throws_exception_on_first_level_category()
    {
        $this->expectException(\InvalidArgumentException::class);

        CategoryClassId::fromString('1')->parent();
    }

    public function test_nextSibling_returns_next_siblingId()
    {
        $this->assertSame('2', (string)CategoryClassId::fromString('1')->nextSibling());
        $this->assertSame('1.02.04', (string)CategoryClassId::fromString('1.2.3')->nextSibling());
    }
    public function test_previousSibling_returns_previous_siblingId()
    {
        $this->assertSame('2', (string)CategoryClassId::fromString('3')->previousSibling());
        $this->assertSame('1.02.02', (string)CategoryClassId::fromString('1.2.3')->previousSibling());
    }

    public function test_nthLevel_returns_formated_level_string()
    {
        $this->assertSame('1', (string)CategoryClassId::fromString('1.2.3')->nthLevel(1));
        $this->assertSame('02', (string)CategoryClassId::fromString('1.2.3')->nthLevel(2));
        $this->assertSame('03', (string)CategoryClassId::fromString('1.2.3')->nthLevel(3));
    }

    public function test_nthLevel_throws_exception_on_invalid_index_0()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertSame('0', (string)CategoryClassId::fromString('1.2.3')->nthLevel(0));
    }

    public function test_nthLevel_throws_exception_on_invalid_index()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertSame('00', (string)CategoryClassId::fromString('1.2.3')->nthLevel(4));
    }

}
