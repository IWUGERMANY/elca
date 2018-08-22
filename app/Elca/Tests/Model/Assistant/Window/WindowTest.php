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
namespace Elca\Tests\Model\Assistant\Window;

use Elca\Model\Assistant\Window\Window;
use Elca\Model\Common\Geometry\Rectangle;
use PHPUnit\Framework\TestCase;

class WindowTest extends TestCase
{
    public function test_constructor()
    {
        $window = new Window(
            'Test',
            new Rectangle(1, 1), 0.06,
            0.06, null, null, null
        );

        $this->assertInstanceOf(Window::class, $window);
    }

    /**
     * @dataProvider openingBoundaryProvider
     * @param Window $window
     * @param        $width
     * @param        $height
     * @param        $expected
     */
    public function test_openingBoundary_dimensions(Window $window, $width, $height, $expected)
    {
        $clearOpening = $window->getOpeningBoundary();

        static::assertInstanceOf(Rectangle::class, $clearOpening);
        static::assertEquals($width, $clearOpening->getWidth(), '', 0.001);
        static::assertEquals($height, $clearOpening->getHeight(), '', 0.001);
        static::assertEquals($expected, $clearOpening->getArea(), '', 0.001);
    }

    /**
     * return array
     */
    public function openingBoundaryProvider()
    {
        return [
            [
                new Window(
                    'Test',
                    new Rectangle(1, 2),
                    0.2,
                    0.1,
                    0.1,
                    null, null, null
                ),
                1.4,
                2.4,
                (1 + 2 * .2) * (2 + 2 * .2)
            ],
            [
                new Window(
                    'Test',
                    new Rectangle(1.5, 2.3),
                    0.02,
                    0.1,
                    0.1,
                    null, null, null
                ),
                1.54,
                2.34,
                1.54 * 2.34
            ],
        ];
    }

    public function test_fixed_frame_length()
    {
        $window = $this->given_window_variant_1();

        static::assertEquals(3.8, $window->getFixedFrame()->getLength());
    }

    public function test_shash_frame_length()
    {
        $window = $this->given_window_variant_1();

        static::assertEquals(3.44, $window->getFixedFrame()->getSashFramesLength());
    }

    public function test_fixed_frame_length_variant_2()
    {
        $window = $this->given_window_variant_2();

        static::assertEquals(4.92, $window->getFixedFrame()->getLength());
    }

    public function test_shash_frame_length_variant2()
    {
        $window = $this->given_window_variant_2();

        static::assertEquals(8.36, $window->getFixedFrame()->getSashFramesLength());
    }

    public function test_serialization()
    {
        $window = $this->given_window_variant_2();

        $serialized = serialize($window);
        $unserialized = unserialize($serialized);

        static::assertEquals($window, $unserialized);
        static::assertEquals($window->getFixedFrame(), $unserialized->getFixedFrame());
        static::assertEquals($window->getFixedFrame()->getTiles(), $unserialized->getFixedFrame()->getTiles());
    }

    /**
     * @return Window
     */
    private function given_window_variant_1()
    {
        $window = new Window(
            'test',
            new Rectangle(
                1,
                1
            ),
            0.2,
            0.05,
            null, null, null, 123,
            0.04
        );

        return $window;
    }

    /**
     * @return Window
     */
    private function given_window_variant_2()
    {
        $window = new Window(
            'test',
            new Rectangle(
                1.28,
                1.28
            ),
            0.2,
            0.05,
            null, null, null, 123,
            0.04,
            1,
            0,
            false,
            true,
            0.3
        );

        return $window;
    }
}
