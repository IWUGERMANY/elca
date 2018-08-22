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

namespace Elca\Model\Assistant\Stairs\Steps;

use Elca\Model\Assistant\Stairs\Steps\Cover;

/**
 * Step
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Step
{
    private $width;
    private $depth;
    private $height;

    /**
     * @var Cover
     */
    private $cover;

    /**
     * @var Riser
     */
    private $riser;

    /**
     * Step constructor.
     *
     * @param number $width
     * @param number $depth
     * @param number $height
     * @param Cover  $cover
     * @param Riser  $riser
     */
    public function __construct($width, $depth, $height, Cover $cover, Riser $riser = null)
    {
        $this->width = $width;
        $this->depth = $depth;
        $this->height = $height;
        $this->cover = $cover;
        $this->riser = $riser;
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
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return number
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return Cover
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     * @return bool
     */
    public function hasRiser()
    {
        return $this->riser !== null;
    }

    /**
     * @return Riser
     */
    public function getRiser()
    {
        return $this->riser;
    }

    /**
     * @return number
     */
    public function getVolume()
    {
        return ($this->width * $this->height * $this->depth) / 2;
    }

    /**
     * @return int
     */
    public function getDegree()
    {
        return 2 * $this->height + $this->depth;
    }

}
