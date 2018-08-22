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

namespace Elca\Model\Common\Geometry;

use Assert\Assertion;

/**
 * Area
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Rectangle implements Shape
{
    /**
     * @var number
     */
    private $width;

    /**
     * @var number
     */
    private $height;

    /**
     * @param number $width
     * @param number $height
     */
    public function __construct($width, $height)
    {
        Assertion::numeric($width, null, 'width');
        Assertion::numeric($height, null, 'height');

        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @return number
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return number
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return number
     */
    public function getArea()
    {
        return $this->width * $this->height;
    }

    /**
     * @return number
     */
    public function getPerimeter()
    {
        return 2 * $this->width + 2 * $this->height;
    }
}
