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

/**
 * Created by PhpStorm.
 * User: pronoia
 * Date: 22.12.17
 * Time: 07:04
 */

namespace Elca\Tests\Model\Processing;

use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\ProcessConfig\Conversion\ConversionException;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Converter;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\Processing\ComponentMassCalculator;
use PHPUnit\Framework\TestCase;

class ComponentMassCalculatorTest extends TestCase
{
    /**
     * @var ComponentMassCalculator
     */
    private $calculator;

    public function computeDataProvider()
    {
        return [
            [
                [
                    new LinearConversion(Unit::kg(), Unit::kg(), 1),
                ],
                new Quantity(15, Unit::kg()),
                15.0,
            ],
            [
                [
                    new LinearConversion(Unit::piece(), Unit::kg(), 1.523),
                    new LinearConversion(Unit::piece(), Unit::m2(), 10),
                ],
                new Quantity(15, Unit::piece()),
                22.845,
            ],
        ];
    }

    /**
     * @dataProvider computeDataProvider
     */
    public function test_compute(array $conversions, Quantity $quantity, float $result)
    {
        $converter = new Converter(
            new ProcessConfigId(1),
            $conversions
        );

        $mass = $this->calculator->compute($converter, $quantity);

        $this->assertSame($result, $mass);
    }

    public function test_conversionException_is_thrown()
    {
        $converter = new Converter(
            new ProcessConfigId(1),
            []
        );

        $this->expectException(ConversionException::class);

        $mass = $this->calculator->compute($converter, new Quantity(1, Unit::piece()));
    }


    protected function setUp()
    {
        $this->calculator = new ComponentMassCalculator();
    }
}
