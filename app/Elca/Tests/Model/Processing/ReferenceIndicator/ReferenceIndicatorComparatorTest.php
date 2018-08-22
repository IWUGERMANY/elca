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

namespace Elca\Tests\Model\Processing\ReferenceIndicator;

use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Indicator\ReferenceIndicatorValue;
use Elca\Model\Processing\ReferenceIndicator\ReferenceIndicatorComparator;
use PHPUnit\Framework\TestCase;

class ReferenceIndicatorComparatorTest extends TestCase
{

    public function test_construct_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ReferenceIndicatorComparator(
            new ReferenceIndicatorValue(
                new IndicatorIdent('abc'),
                0, 0, 0
            ),
            new IndicatorValue(
                new IndicatorIdent('xyz'),
                0
            )
        );
    }

    /**
     * @dataProvider deviationDataProvider
     */
    public function test_deviation($refAvgValue, $value, $deviation)
    {
        $indicatorIdent = new IndicatorIdent('gwp');

        $comparator = new ReferenceIndicatorComparator(
            new ReferenceIndicatorValue(
                $indicatorIdent,
                $refAvgValue, $refAvgValue, $refAvgValue
            ),
            new IndicatorValue(
                $indicatorIdent,
                $value
            )
        );

        $this->assertSame($deviation, $comparator->deviation());
    }

    /**
     * @dataProvider compareDataProvider
     */
    public function testCompare($refMinValue, $refAvgValue, $refMaxValue, $value, $result)
    {
        $indicatorIdent = new IndicatorIdent('gwp');

        $comparator = new ReferenceIndicatorComparator(
            new ReferenceIndicatorValue(
                $indicatorIdent,
                $refMinValue, $refAvgValue, $refMaxValue
            ),
            new IndicatorValue(
                $indicatorIdent,
                $value
            )
        );

        $this->assertSame($result, $comparator->compare());
    }

    public function deviationDataProvider()
    {
        return [
            [1.0, 1.0, 0.0],
            [1.0, .5, -.5],
            [-1.0, -.5, .5],
            [.5, 1.0, 1.0],
        ];
    }

    public function compareDataProvider()
    {
        return [
            [1.0, 1.0, 1.0, 1.0, 0],
            [1.0, 1.0, 1.0, .5, 1],
            [1.0, 1.0, 1.0, 1.5, -1],

            [1.0, 1.0, 1.0, 1.0 + 1/3 + .0001, -1],
            [1.0, 1.0, 1.0, 1.0 - 1/3 - .0001, 1],
        ];
    }

}
