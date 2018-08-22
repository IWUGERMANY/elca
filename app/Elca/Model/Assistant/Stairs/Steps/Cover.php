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

use Elca\Model\Assistant\Material\Material;

/**
 * Cover
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Cover
{
    const TYPE_RECTANGLE = 1;
    const TYPE_TRAPEZOID = 2;

    /**
     * @var number
     */
    private $width;
    private $length1;
    private $length2;
    private $size;

    /**
     * @var Material
     */
    private $material;

    /**
     * Cover constructor.
     *
     * @param number $size
     * @param number $width
     * @param number $length1
     * @param number $length2
     * @param Material $material
     */
    public function __construct(Material $material, $size, $width, $length1, $length2 = null)
    {
        $this->size = $size;
        $this->width = $width;
        $this->length1 = $length1;
        $this->length2 = $length2 !== null? $length2 : $length1;
        $this->material = $material;
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
    public function getLength1()
    {
        return $this->length1;
    }

    /**
     * @return null|number
     */
    public function getLength2()
    {
        return $this->length2;
    }

    /**
     * @return number
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return Material
     */
    public function getMaterial()
    {
        return $this->material;
    }

    /**
     * @return bool
     */
    public function isTrapezoid()
    {
        return $this->length1 != $this->length2;
    }

    /**
     * @return number
     */
    public function getArea()
    {
        return ($this->length1 + $this->length2) / 2 * $this->width;
    }

    /**
     * @return number
     */
    public function getVolume()
    {
        return $this->getArea() * $this->size;
    }

    /**
     * @param Material $material
     */
    public function replaceMaterial(Material $material)
    {
        $this->material = $material;
    }
}
