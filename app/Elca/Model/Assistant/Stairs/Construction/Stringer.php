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
 * Stringer
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Stringer extends Construction
{
    /**
     * @var int
     */
    private $amount;

    /**
     * Stringer constructor.
     *
     * @param Material    $material
     * @param number      $width
     * @param number      $height
     * @param Steps       $steps
     * @param number|null $alternativeLength
     * @param int         $amount
     */
    public function __construct(Material $material, $width, $height, Steps $steps, $alternativeLength = null, $amount = 2)
    {
        $length = $alternativeLength !== null? $alternativeLength : $steps->getLength();
        parent::__construct($width, $height, $length);

        $this->addMaterial($material);
        $this->amount = $amount;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param bool $forSingleStringer
     * @return number
     */
    public function getVolume($forSingleStringer = false)
    {
        if ($forSingleStringer)
            return parent::getVolume();

        return $this->amount * parent::getVolume();
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
            'stringerWidth' => $this->getWidth() * 100,
            'stringerHeight' => $this->getHeight() * 100,
            'stringerLength' => $this->getLength(),
            'numberOfStringers' => $this->amount > 0? $this->amount : 2,
            'materialId' => [
                'stringer' => $material->getMaterialId(),
            ]
        ];
    }

}
