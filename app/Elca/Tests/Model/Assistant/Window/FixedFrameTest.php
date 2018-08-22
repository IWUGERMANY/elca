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

use Elca\Model\Assistant\Window\FixedFrame;
use Elca\Model\Common\Geometry\Rectangle;
use PHPUnit\Framework\TestCase;

class FixedFrameTest extends TestCase
{
    /**
     * @dataProvider tileFramesProvider
     */
    public function test_number_of_tile_frames($sashFrameWidth, $mullions, $transoms, $fixedMullionsTransoms, $numberOfTiles, $tileFrameWidth, $tileFrameHeight)
    {
        $frame = new FixedFrame(
            new Rectangle(1.1, 1.1),
            0.05,
            null, null,
            $sashFrameWidth,
            $mullions,
            $transoms,
            $fixedMullionsTransoms
        );

        static::assertSame($numberOfTiles, $frame->numberOfTileFrames());
    }

    /**
     * @dataProvider tileFramesProvider
     */
    public function test_tile_frame_width($sashFrameWidth, $mullions, $transoms, $fixedMullionsTransoms, $numberOfTiles, $tileFrameWidth, $tileFrameHeight)
    {
        $frame = new FixedFrame(
            new Rectangle(1.1, 1.1),
            0.05,
            null, null,
            $sashFrameWidth,
            $mullions,
            $transoms,
            $fixedMullionsTransoms
        );

        static::assertEquals($tileFrameWidth, current($frame->tileWidths()));
    }

    /**
     * @dataProvider tileFramesProvider
     */
    public function test_tile_frame_height($sashFrameWidth, $mullions, $transoms, $fixedMullionsTransoms, $numberOfTiles, $tileFrameWidth, $tileFrameHeight)
    {
        $frame = new FixedFrame(
            new Rectangle(1.1, 1.1),
            0.05,
            null, null,
            $sashFrameWidth,
            $mullions,
            $transoms,
            $fixedMullionsTransoms
        );

        static::assertEquals($tileFrameHeight, current($frame->tileHeights()));
    }

    /**
     *
     */
    public function tileFramesProvider()
    {
        return [
            [0, 0, 0, false, 1, 1, 1],
            [0, 1, 1, false, 4, 0.475, 0.475],
            [0, 1, 1, true, 4, 0.475, 0.475],
            [0.04, 0, 0, false, 1, 1, 1],
            [0.04, 0, 0, true, 1, 1, 1],
            [0.04, 1, 0, true, 2, .475, 1],
        ];

    }

    /**
     * @dataProvider fixedFrameLengthProvider
     */
    public function test_fixedFrame_length($width, $height, $frameWidth, $mullions, $transoms, $fixedMullionsTransoms, $topLightHeight, $length)
    {
        $frame = new FixedFrame(
            new Rectangle($width, $height),
            $frameWidth,
            null, null,
            0,
            $mullions,
            $transoms,
            $fixedMullionsTransoms,
            $topLightHeight > 0,
            $topLightHeight
        );

        static::assertEquals($length, $frame->getLength());
    }

    /**
     *
     */
    public function fixedFrameLengthProvider()
    {
        return [
            [1, 1, .05, 0, 0, false, 0, 2 * (1 - .05) + 2 * (1 - .05)],
            [1, 1, .05, 1, 0, false, 0, 2 * (1 - .05) + 2 * (1 - .05) + (1 - 2 * .05) ],
            [1, 1, .05, 0, 1, false, 0, 2 * (1 - .05) + 2 * (1 - .05) + (1 - 2 * .05) ],
            [1, 1, .05, 2, 0, false, 0, 2 * (1 - .05) + 2 * (1 - .05) + 2 * (1 - 2 * .05)],
            [1, 1, .05, 1, 1, false, 0, 2 * (1 - .05) + 2 * (1 - .05) + (1 - 2 * .05) + (1 - 2 * .05) - 0.05],
            [1, 1, .05, 2, 3, false, 0, 2 * (1 - .05) + 2 * (1 - .05) + 2 * (1 - 2 * .05) + 3 * (1 - 2 * .05) - 2 * 3 * 0.05],

            [1, 3, .05, 0, 0, false, 0, 2 * (1 - .05) + 2 * (3 - .05)],
            [1, 3, .05, 1, 0, false, 0, 2 * (1 - .05) + 2 * (3 - .05) + (3 - 2 * .05) ],
            [1, 3, .05, 0, 1, false, 0, 2 * (1 - .05) + 2 * (3 - .05) + (1 - 2 * .05) ],
            [1, 3, .05, 2, 0, false, 0, 2 * (1 - .05) + 2 * (3 - .05) + 2 * (3 - 2 * .05)],
            [1, 3, .05, 1, 1, false, 0, 2 * (1 - .05) + 2 * (3 - .05) + (3 - 2 * .05) + (1 - 2 * .05) - 0.05],
            [1, 3, .05, 2, 3, false, 0, 2 * (1 - .05) + 2 * (3 - .05) + 2 * (3 - 2 * .05) + 3 * (1 - 2 * .05) - 2 * 3 * 0.05],

            [3, 1, .05, 0, 0, false, 0.3, 2 * (3 - .05) + 2 * (1 - .05)],
            [3, 1, .05, 0, 0, true, 0.3, 2 * (3 - .05) + 2 * (1 - .05) + (3 - 2 * 0.05)],
        ];

    }

    /**
     * @dataProvider sashFrameLengthProvider
     */
    public function test_sashFrames_length($width, $height, $mullions, $transoms, $fixedMullionsTransoms, $topLightHeight, $length)
    {
        $frame = new FixedFrame(
            new Rectangle($width, $height),
            0.05,
            null, 123,
            0.04,
            $mullions,
            $transoms,
            $fixedMullionsTransoms,
            $topLightHeight > 0,
            $topLightHeight
        );

        static::assertEquals($length, $frame->getSashFramesLength());
    }

    /**
     *
     */
    public function sashFrameLengthProvider()
    {
        return [
            // no fixed mullions and transoms, no top light
            [1.1, 1.1, 0, 0, false, 0, (1 + .92) * 2],
            [1.1, 1.1, 1, 0, false, 0, (.5 + .92) * 2 * 2],
            [1.1, 1.1, 0, 1, false, 0, (.5 + .92) * 2 * 2],
            [1.1, 1.1, 1, 1, false, 0, (.5 + .42) * 2 * 4],

            // with top light
            [1.1, 1.1, 0, 0, false, .3, (1 + .7 - .08) * 2 + (1 + .22) * 2],
            [1.1, 1.1, 1, 0, false, .3, (.5 + .7 - .08) * 4 + (1 + .22) * 2],
            [1.1, 1.1, 0, 1, false, .3, (1 + .35 - .08) * 4 + (1 + .22) * 2],
            [1.1, 1.1, 1, 1, false, .3, (.5 + .35 - .08) * 4 * 2 + (1 + .22) * 2],

            // fixed mullions and transoms, no top light
            [1.1, 1.1, 0, 0, true, 0, (1 + .92) * 2],
            [1.1, 1.1, 1, 0, true, 0, (.5 - .025 + .92) * 2 * 2],
            [1.1, 1.1, 0, 1, true, 0, (.5 + .92 - .025) * 2 * 2],
            [1.1, 1.1, 1, 1, true, 0, (.5 - .025 + .42 - .025) * 2 * 4],

            // fixed mullions and transoms, with top light
            [1.1, 1.1, 0, 0, true, .3, (1 + .7 - .08) * 2 + (1 + .22 - .05) * 2],
            [1.1, 1.1, 1, 0, true, .3, (.5 - .025 + .7 - .08) * 4 + (1 + .22 - .05) * 2],
            [1.1, 1.1, 0, 1, true, .3, (1 + .35 - .08 - .025) * 4 + (1 + .22 - .05) * 2],
            [1.1, 1.1, 1, 1, true, .3, (.5 - .025 + .35 - .08 - .025) * 4 * 2 + (1 + .22 - .05) * 2],
        ];

    }


    /**
     * @dataProvider frameAreaProvider
     */
    public function test_frame_area($width, $height, $sashFrameWidth, $mullions, $transoms, $fixedMullionsTransoms, $topLightHeight, $area)
    {
        $frame = new FixedFrame(
            new Rectangle($width, $height),
            0.05,
            null, 123,
            $sashFrameWidth,
            $mullions,
            $transoms,
            $fixedMullionsTransoms,
            $topLightHeight > 0,
            $topLightHeight
        );

        static::assertEquals($area, $frame->getArea());
    }

    /**
     *
     */
    public function frameAreaProvider()
    {
        return [
            // no top light, no sashframe
            [1.1, 1.1, 0, 0, 0, false, 0, 1.1 * 1.1 - 1 * 1],
            [1.1, 1.1, 0, 1, 0, false, 0, 1.1 * 1.1 - 1 * 1 + 1 * .05],
            [1.1, 1.1, 0, 0, 1, false, 0, 1.1 * 1.1 - 1 * 1 + 1 * .05],
            [1.1, 1.1, 0, 1, 1, false, 0, 1.1 * 1.1 - 1 * 1 + 1 * .05 + (1 - .05) * .05],

            // with sashframe, no top light
            [1.1, 1.1, .04, 0, 0, false, 0, (1.1 * 1.1 - 1 * 1) + (1 * 1 - .92 * .92)],
            [1.1, 1.1, .04, 1, 0, false, 0, (1.1 * 1.1 - 1 * 1) + 2 * (.5 * 1 - .42 * .92)],
            [1.1, 1.1, .04, 0, 1, false, 0, (1.1 * 1.1 - 1 * 1) + 2 * (1 * .5 - .92 * .42)],
            [1.1, 1.1, .04, 1, 1, false, 0, (1.1 * 1.1 - 1 * 1) + 4 * (.5 * .5 - .42 * .42)],

            // with sashframe and fixed mullions and transoms, no top light
            [1.1, 1.1, .04, 0, 0, true, 0, (1.1 * 1.1 - 1 * 1) + (1 * 1 - .92 * .92)],
            [1.1, 1.1, .04, 1, 0, true, 0, (1.1 * 1.1 - 1 * 1 + 1 * .05) + 2 * (.475 * 1 - .395 * .92)],
            [1.1, 1.1, .04, 0, 1, true, 0, (1.1 * 1.1 - 1 * 1 + 1 * .05) + 2 * (1 * .475 - .92 * .395)],
            [1.1, 1.1, .04, 1, 1, true, 0, (1.1 * 1.1 - 1 * 1 + 1 * .05 + (1 - .05) * .05) + 4 * (.475 * .475 - .395 * .395)],
            [1.1, 1.1, .04, 2, 1, true, 0, (1.1 * 1.1 - 1 * 1 + 1 * .05 + 2 * (1 - .05) * .05)
                                           + 6 * (.3 * .475 - .22 * .395)],

//            // fixed mullions and transoms, top light
            [1.1, 1.1, .04, 0, 0, true, .3, (1.1 * 1.1 - 1 * 1) + 1 * .05
                                            + (1 * .7 - .92 * .62)
                                            + (1 * .25 - .92 * .17)
            ],
        ];

    }


}
