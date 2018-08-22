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
use PHPUnit\Framework\TestCase;

class IndicatorScoreThresholdsTest extends TestCase
{
    /**
     * @var NamedScoreThresholds
     */
    private $thresholds;

    public function test_scores()
    {
        $this->assertSame(
            [
                10,
                20,
                30,
                40,
            ],
            $this->thresholds->scores()
        );
    }

    public function test_minScore()
    {
        $this->assertSame(10, $this->thresholds->minScore());
    }

    public function test_maxScore()
    {
        $this->assertSame(40, $this->thresholds->maxScore());
    }

    public function test_minScoreValue()
    {
        $this->assertSame(39.9, $this->thresholds->minScoreValue());
    }

    public function test_maxScoreValue()
    {
        $this->assertSame(50.16, $this->thresholds->maxScoreValue());
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->thresholds = new NamedScoreThresholds(
            IndicatorIdent::GWP,
            [
                10 => 39.9,
                20 => 43.32,
                30 => 46.74,
                40 => 50.16,
            ]
        );
    }


}
