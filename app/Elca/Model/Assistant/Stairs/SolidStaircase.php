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
use Elca\Model\Assistant\Stairs\Construction\Solid;
use Elca\Model\Assistant\Stairs\Steps\Cover;
use Elca\Model\Assistant\Stairs\Steps\Step;

class SolidStaircase extends Staircase
{
    /**
     * @param $initName
     * @return SolidStaircase
     */
    public static function getDefault($initName = 'Neue Treppe') {
        return new SolidStaircase(
            $initName,
            new Step(
                Staircase::DEFAULT_WIDTH,
                Staircase::DEFAULT_STEP_DEPTH,
                Staircase::DEFAULT_STEP_HEIGHT,
                new Cover(
                    new Material(null),
                    Staircase::DEFAULT_COVER_SIZE,
                    Staircase::DEFAULT_WIDTH,
                    Staircase::DEFAULT_STEP_DEPTH
                )
            ),
            1,  // number of steps
            Staircase::DEFAULT_SLAB_HEIGHT,
            new Material(null),
            new Material(null, 0)
        );
    }

    /**
     * Staircase constructor.
     *
     * @param Step     $step
     * @param int      $numberOfSteps
     * @param number      $slabHeight
     * @param Material $material1
     * @param Material $material2
     * @param number|null     $alternativeLength
     */
    public function __construct($name, Step $step, $numberOfSteps, $slabHeight, Material $material1, Material $material2 = null, $alternativeLength = null)
    {
        parent::__construct($name, $step, $numberOfSteps);

        $this->setConstruction(
            new Solid(
                $step->getWidth(),
                $slabHeight,
                $this->getSteps(),
                $material1,
                $material2,
                $alternativeLength
            )
        );
    }

    /**
     * @return int
     */
    public function getType()
    {
        return Staircase::TYPE_SOLID;
    }

}
