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
 * Solid
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Solid extends Construction
{
    /**
     * @var Steps
     */
    private $steps;

    /**
     * Solid constructor.
     *
     * @param number   $width
     * @param number   $height
     * @param \Elca\Model\Assistant\Stairs\Steps\Steps    $steps
     * @param Material $material1
     * @param Material $material2
     * @param number|null     $alternativeLength
     */
    public function __construct($width, $height, Steps $steps, Material $material1, Material $material2 = null, $alternativeLength = null)
    {
        $length = $alternativeLength !== null? $alternativeLength : $steps->getLength();

        parent::__construct($width, $height, $length);

        $this->addMaterial($material1);

        if ($material2 !== null)
            $this->addMaterial($material2);

        $this->steps = $steps;
    }

    /**
     * @param bool $forSingleStep
     * @return number
     */
    public function getVolume($forSingleStep = false)
    {
        if ($forSingleStep)
            return parent::getVolume() + $this->steps->getStep()->getVolume();

        return parent::getVolume() + $this->steps->getVolume();
    }

    /**
     * @return object
     */
    public function getDataObject()
    {
        $materials = $this->getMaterials();

        $material1 = isset($materials[0]) ? $materials[0] : new Material(null);
        $material2 = isset($materials[1]) ? $materials[1] : new Material(null, 0);

        return (object)[
            'solidSlabHeight' => $this->getHeight() * 100,
            'solidLength' => $this->getLength(),
            'alternativeLength' => $this->steps->getLength(),
            'solidMaterial1Share' => $material1->getShare(),
            'solidMaterial2Share' => $material2->getShare(),
            'materialId' => [
                'solid1' => $material1->getMaterialId(),
                'solid2' => $material2->getMaterialId()
            ]
        ];
    }

}
