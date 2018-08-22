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

namespace Elca\Model\Assistant\Stairs\Construction;

use Elca\Model\Assistant\Material\Material;
use Elca\Model\Assistant\Stairs\Steps\Steps;

/**
 * Construction
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
abstract class Construction
{
    /**
     * @var Material[]
     */
    private $materials;

    /**
     * @var number
     */
    private $width;
    private $height;
    private $length;

    /**
     * Construction constructor.
     *
     * @param number $width
     * @param number $height
     * @param number $length
     */
    public function __construct($width, $height, $length)
    {
        $this->width = $width;
        $this->materials = [];
        $this->height = $height;
        $this->length = $length;
    }

    /**
     * @param Material $material
     */
    public function addMaterial(Material $material)
    {
        $this->materials[] = $material;
    }

    /**
     * @return Material[]
     */
    public function getMaterials()
    {
        return $this->materials;
    }

    /**
     * @param int $index
     * @return Material
     */
    public function getMaterial($index = 0)
    {
        return $this->materials[$index];
    }

    /**
     * @param int      $index
     * @param Material $material
     */
    public function replaceMaterialOn($index = 0, Material $material)
    {
        $this->materials[$index] = $material;
    }

    /**
     * @return number
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param Steps $steps
     * @return number
     */
    public function getCalculatedLength(Steps $steps)
    {
        return $steps->getLength();
    }

    /**
     * @return number
     */
    public function getVolume()
    {
        return $this->width * $this->height * $this->length;
    }

    /**
     * @return object
     */
    abstract public function getDataObject();
}
