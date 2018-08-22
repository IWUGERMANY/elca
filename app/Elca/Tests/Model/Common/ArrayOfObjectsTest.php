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


use Elca\Model\Common\Transform\ArrayOfObjects;
use PHPUnit\Framework\TestCase;

class ArrayOfObjectsTest extends TestCase
{
    public function test_group_by_properties_with_collection_array()
    {
        $list = $this->given_array_of_objects();

        $groupBy = new ArrayOfObjects($list);
        $result  = $groupBy->groupBy(['category', 'indicator'], ['phase' => 'value', 'unit']);

        static::assertEquals(
            [
                'Bodenbelag' => [
                    'GWP' => (object)[
                        'prod' => 123,
                        'eol'  => 321,
                        'unit' => 'kg CO2 eq',
                    ],
                    'ADP' => (object)[
                        'prod' => 1234,
                        'eol'  => 3210,
                        'unit' => 'kg X eq',
                    ],
                ],
            ],
            $result
        );
    }

    public function test_group_by_properties_generator_with_collection_array()
    {
        $list = $this->given_array_of_objects();

        $groupBy = new ArrayOfObjects($list);
        $result  = $groupBy->groupBy(['category', 'indicator'], ['phase' => 'value', 'unit']);

        static::assertEquals(
            [
                'Bodenbelag' => [
                    'GWP' => (object)[
                        'prod' => 123,
                        'eol'  => 321,
                        'unit' => 'kg CO2 eq',
                    ],
                    'ADP' => (object)[
                        'prod' => 1234,
                        'eol'  => 3210,
                        'unit' => 'kg X eq',
                    ],
                ],
            ],
            $result
        );
    }

    public function test_group_by_properties_with_callback()
    {
        $list = $this->given_array_of_objects();

        $groupBy = new ArrayOfObjects($list);
        $result  = $groupBy->groupBy(
            ['category', 'indicator'],
            function ($current, $item) {
                $current->{$item->phase} = $item->value;
                $current->unit           = $item->unit;
                $current->foo            = 'bar';
            }
        );

        static::assertEquals(
            [
                'Bodenbelag' => [
                    'GWP' => (object)[
                        'prod' => 123,
                        'eol'  => 321,
                        'unit' => 'kg CO2 eq',
                        'foo'  => 'bar',
                    ],
                    'ADP' => (object)[
                        'prod' => 1234,
                        'eol'  => 3210,
                        'unit' => 'kg X eq',
                        'foo'  => 'bar',
                    ],
                ],
            ],
            $result
        );
    }

    public function test_group_by_properties_with_callback_returns_list_of_new_objects()
    {
        $list = $this->given_array_of_objects();

        $groupBy = new ArrayOfObjects($list);
        $result  = $groupBy->groupBy(
            ['category', 'indicator'],
            function ($current, $item) {
                $current->{$item->phase} = $item->value;
                $current->unit           = $item->unit;
                $current->foo            = 'bar';
            }
        );

        static::assertEquals(
            [
                'Bodenbelag' => [
                    'GWP' => (object)[
                        'prod' => 123,
                        'eol'  => 321,
                        'unit' => 'kg CO2 eq',
                        'foo'  => 'bar',
                    ],
                    'ADP' => (object)[
                        'prod' => 1234,
                        'eol'  => 3210,
                        'unit' => 'kg X eq',
                        'foo'  => 'bar',
                    ],
                ],
            ],
            $result
        );
    }

    protected function given_array_of_objects()
    {
        $do1            = new \stdClass();
        $do1->category  = 'Bodenbelag';
        $do1->phase     = 'prod';
        $do1->indicator = 'GWP';
        $do1->unit      = 'kg CO2 eq';
        $do1->value     = 123;

        $do2            = new \stdClass();
        $do2->category  = 'Bodenbelag';
        $do2->phase     = 'eol';
        $do2->indicator = 'GWP';
        $do2->unit      = 'kg CO2 eq';
        $do2->value     = 321;

        $do3            = new \stdClass();
        $do3->category  = 'Bodenbelag';
        $do3->phase     = 'prod';
        $do3->indicator = 'ADP';
        $do3->unit      = 'kg X eq';
        $do3->value     = 1234;

        $do4            = new \stdClass();
        $do4->category  = 'Bodenbelag';
        $do4->phase     = 'eol';
        $do4->indicator = 'ADP';
        $do4->unit      = 'kg X eq';
        $do4->value     = 3210;

        return [$do1, $do2, $do3, $do4];
    }
}
