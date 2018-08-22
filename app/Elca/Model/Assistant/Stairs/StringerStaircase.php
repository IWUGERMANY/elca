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

use Elca\Model\Assistant\Material\Material;
use Elca\Model\Assistant\Stairs\Construction\Stringer;
use Elca\Model\Assistant\Stairs\Steps\Step;

class StringerStaircase extends Staircase
{
    /**
     * Staircase constructor.
     *
     * @param Step     $step
     * @param int      $numberOfSteps
     * @param number   $stringerWidth
     * @param number   $stringerHeight
     * @param Material $stringerMaterial
     * @param int      $numberOfStringers
     * @param null     $alternativeLength
     */
    public function __construct($name, Step $step, $numberOfSteps, $stringerWidth, $stringerHeight, Material $stringerMaterial, $numberOfStringers = 1, $alternativeLength = null)
    {
        parent::__construct($name, $step, $numberOfSteps);

        $this->setConstruction(
            new Stringer(
                $stringerMaterial,
                $stringerWidth,
                $stringerHeight,
                $this->getSteps(),
                $alternativeLength,
                $numberOfStringers
            )
        );
    }

    /**
     * @return int
     */
    public function getType()
    {
        return Staircase::TYPE_STRINGER;
    }

}
