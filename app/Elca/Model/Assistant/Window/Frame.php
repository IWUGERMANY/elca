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

namespace Elca\Model\Assistant\Window;

use Elca\Model\Common\Geometry\Rectangle;

/**
 * Frame
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Frame
{
    /**
     * @var number
     */
    private $frameWidth;

    /**
     * @var Rectangle
     */
    private $outerBoundary;

    /**
     * @var int
     */
    private $materialId;

    /**
     * @param Rectangle $outerBoundary
     * @param number    $frameWidth
     * @param number    $size
     * @param null      $materialId
     */
    public function __construct(Rectangle $outerBoundary, $frameWidth, $materialId = null)
    {
        $this->frameWidth = $frameWidth;
        $this->outerBoundary = $outerBoundary;
        $this->materialId = $materialId;
    }

    /**
     * @return number
     */
    public function getWidth()
    {
        return $this->outerBoundary->getWidth();
    }

    /**
     * @return number
     */
    public function getHeight()
    {
        return $this->outerBoundary->getHeight();
    }

    /**
     * @return Rectangle
     */
    public function getOuterBoundary()
    {
        return $this->outerBoundary;
    }

    /**
     * @return Rectangle
     */
    public function getInnerBoundary()
    {
        return new Rectangle(
            $this->outerBoundary->getWidth() - 2 * $this->frameWidth,
            $this->outerBoundary->getHeight() - 2 * $this->frameWidth
        );
    }

    /**
     * @return number
     */
    public function getArea()
    {
        return $this->getOuterBoundary()->getArea() - $this->getInnerBoundary()->getArea();
    }

    /**
     * Calculates the average perimeter
     */
    public function getPerimeter()
    {
        return ($this->outerBoundary->getWidth() - $this->frameWidth) * 2
            + ($this->outerBoundary->getHeight() - $this->frameWidth) * 2;
    }

    /**
     * Calculates the average perimeter
     */
    public function getLength()
    {
        return $this->getPerimeter();
    }


    /**
     * @return float
     */
    public function getRatio()
    {
        return 1 - ($this->getInnerBoundary()->getArea() / $this->getOuterBoundary()->getArea());
    }


    /**
     * @return number
     */
    public function getFrameWidth()
    {
        return $this->frameWidth;
    }

    /**
     * @return int
     */
    public function getMaterialId()
    {
        return $this->materialId;
    }

}
