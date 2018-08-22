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
 * MiddleHolm
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class MiddleHolm extends Construction
{
    const ORIENTATION_VERTICAL = 1;
    const ORIENTATION_ASCENDING = 2;

    const SHAPE_ELLIPSOID = 1;
    const SHAPE_RECTANGLE = 2;

    /**
     * @var int
     */
    private $orientation;

    /**
     * @var int
     */
    private $shape;

    /**
     * @var number
     */
    private $size;

    /**
     * Construction constructor.
     *
     * @param number   $width
     * @param number   $height
     * @param Material $material
     * @param int      $shape
     * @param int      $orientation
     * @param number   $size
     * @param Steps    $steps
     * @param null     $alternativeLength
     */
    public function __construct($width, $height, Material $material, $shape = self::SHAPE_RECTANGLE, $orientation = self::ORIENTATION_ASCENDING, $size = null, Steps $steps, $alternativeLength = null)
    {
        if ($alternativeLength !== null) {
            $length = $alternativeLength;

        } else {
            $length = $this->getCalculatedLength($steps);
        }

        parent::__construct($width, $height, $length);

        $this->addMaterial($material);
        $this->shape = $shape;
        $this->orientation = $orientation;
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * @return int
     */
    public function getShape()
    {
        return $this->shape;
    }

    /**
     * @return number
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param Steps $steps
     * @return number
     */
    public function getCalculatedLength(Steps $steps)
    {
        if ($this->getOrientation() === self::ORIENTATION_ASCENDING) {
            return $steps->getLength();
        }

        return $steps->getHeight();
    }


    /**
     * @return number
     */
    public function getVolume()
    {
        if ($this->shape === self::SHAPE_RECTANGLE) {

            $volume = $this->getWidth() * $this->getHeight() * $this->getLength();

            if ($this->size !== null) {
                $volume -= ($this->getWidth() - $this->size) * ($this->getHeight() - $this->size) * $this->getLength();
            }
        } else {
            $volume = $this->getWidth() / 2 * $this->getHeight() / 2 * pi() * $this->getLength();

            if ($this->size !== null) {
                $volume -= ($this->getWidth() - $this->size) / 2 * ($this->getHeight() - $this->size) / 2 * pi() * $this->getLength();
            }
        }

        return $volume;
    }

    /**
     * @return object
     */
    public function getDataObject()
    {
        $materials = $this->getMaterials();

        $material = null;
        if (isset($materials[0])) {
            $material = isset($materials[0]) ? $materials[0] : new Material(null);
        }

        return (object)[
            'holmWidth' => $this->getWidth() * 100,
            'holmHeight' => $this->getHeight() * 100,
            'holmLength' => $this->getLength(),
            'holmSize' => $this->size !== null? $this->size * 1000 : null,
            'holmShape' => $this->shape,
            'holmOrientation' => $this->orientation,
            'materialId' => [
                'holm' => $material->getMaterialId(),
            ]
        ];
    }
}
