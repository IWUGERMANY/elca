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

namespace Elca\Model\Assistant\Stairs;

/**
 * Platform
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Platform
{
    /**
     * @var int
     */
    private $amount;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * Platform constructor.
     *
     * @param int $width
     * @param int $height
     * @param int $amount
     */
    public function __construct($width = 1, $height = 1, $amount = 1)
    {
        $this->amount = $amount;
        $this->width = $width;
        $this->height = $height;
    }


    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
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
     * @return object
     */
    public function getDataObject()
    {
        return (object)[
            'platformWidth' => $this->width,
            'platformHeight' => $this->height,
            'numberOfPlatforms' => $this->amount
        ];
    }
}
