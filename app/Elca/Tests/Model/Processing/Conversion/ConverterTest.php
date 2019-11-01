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

namespace Elca\Tests\Model\Processing\Conversion;

use Elca\Model\Common\Unit;
use Elca\Model\ProcessConfig\Conversion\ConversionException;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Converter;
use Elca\Model\ProcessConfig\ProcessConfigId;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{

    public function test_identity_conversion()
    {
        $converter = new Converter(
            new ProcessConfigId(1),
            [
                new LinearConversion(Unit::piece(), Unit::piece(), 1),
            ]
        );

        static::assertSame(
            1.0,
            $converter
                ->convert(
                    1,
                    Unit::piece(),
                    Unit::piece()
                )
        );
    }

    /**
     * @dataProvider conversionProvider
     */
    public function test_simple_conversions($convFromUnit, $convToUnit, $factor, $fromUnit, $toUnit, $value, $result)
    {
        $converter = new Converter(
            new ProcessConfigId(1),
            [
                new LinearConversion(new Unit($convFromUnit), new Unit($convToUnit), $factor),
            ]
        );

        static::assertEquals(
            $result,
            $converter
                ->convert(
                    $value,
                    new Unit($fromUnit),
                    new Unit($toUnit)
                )
        );
    }


    public function conversionProvider()
    {
        return [
            ['x', 'y', 1, 'x', 'y', 10, 10],
            ['x', 'y', 5, 'x', 'y', 10, 50],
            ['x', 'y', 1, 'y', 'x', 10, 10],
            ['x', 'y', 5, 'y', 'x', 10, 2],

            ['x', 'y', 0.1234, 'x', 'y', 10, 1.234],
            ['x', 'y', 5, 'x', 'y', 1.234, 6.17],
            ['x', 'y', '0.12345678901', 'x', 'y', '1', '0.12345678901'],
            ['x', 'y', '0.12345678901', 'y', 'x', '1.23', '9.9630000898515'],

        ];
    }

    public function test_convert_throws_conversion_exception()
    {
        $converter = new Converter(
            new ProcessConfigId(1),
            []
        );

        $this->expectException(ConversionException::class);

        $converter->convert(1, Unit::fromString('x'), Unit::fromString('y'));
    }
}
