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

namespace Elca\Tests\Model\Processing\Benchmark;


use Elca\Db\ElcaIndicator;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Processing\Benchmark\NamedScoreThresholds;
use Elca\Model\Processing\Benchmark\LinearScoreInterpolator;
use PHPUnit\Framework\TestCase;

class LinearInterpolatorTest extends TestCase
{
    /**
     * @param $indicatorValue
     * @param $score
     * @dataProvider descendingComputationProvider
     */
    public function test_computation_descending_values($indicatorValue, $score)
    {
        $thresholds = new NamedScoreThresholds(
            ElcaIndicator::IDENT_GWP,
            [
                10  => 79.8,
                20  => 74.1,
                30  => 68.4,
                40  => 62.7,
                50  => 57,
                60  => 53.58,
                70  => 50.16,
                80  => 46.74,
                90  => 43.32,
                100 => 39.9,
            ]
        );

        $benchmark = new LinearScoreInterpolator($thresholds);

        $this->assertEquals($score, $benchmark->computeScore($indicatorValue), '', 0.0000000001);
    }

    /**
     * @param $indicatorValue
     * @param $score
     * @dataProvider ascendingComputationProvider
     */
    public function test_computation_ascending_values($indicatorValue, $score)
    {
        $thresholds = new NamedScoreThresholds(
            ElcaIndicator::IDENT_GWP,
            [
                10  => 39.9,
                20  => 43.32,
                30  => 46.74,
                40  => 50.16,
                50  => 53.58,
                60  => 57,
                70  => 62.7,
                80  => 68.4,
                90  => 74.1,
                100 => 79.8,
            ]
        );

        $benchmark = new LinearScoreInterpolator($thresholds);

        $this->assertEquals($score, $benchmark->computeScore($indicatorValue), '', 0.0000000001);
    }

    /**
     *
     */
    public function descendingComputationProvider()
    {
        return [
            [80, 10],
            [79.8, 10],
            [74.1, 20],
            [55, 55.847953216374],
            [39.9, 100],
            [30.9, 100],
        ];
    }

    /**
     *
     */
    public function ascendingComputationProvider()
    {
        return [
            [80, 100],
            [79.8, 100],
            [74.1, 90],
            [55, 54.152046783626],
            [39.9, 10],
            [30.9, 10],
        ];
    }
}
