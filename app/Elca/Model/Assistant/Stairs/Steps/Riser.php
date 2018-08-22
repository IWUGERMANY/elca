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
 * Riser
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Riser
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
     * @var number
     */
    private $size;

    /**
     * @var Material
     */
    private $material;

    /**
     * Riser constructor.
     *
     * @param number   $width
     * @param number   $height
     * @param number   $size
     * @param Material $material
     */
    public function __construct($height, $width = null, $size = null, Material $material = null)
    {
        $this->height = $height;
        $this->width = $width;
        $this->size = $size;
        $this->material = $material ?: new Material(null);
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->material === null;
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
     * @param Material $material
     */
    public function replaceMaterial(Material $material)
    {
        $this->material = $material;
    }
}
