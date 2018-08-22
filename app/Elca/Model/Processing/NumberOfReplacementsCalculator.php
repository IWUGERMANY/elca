<?php
/**
 *  This file is part of the eLCA project
 *
 *  eLCA
 *  A web based life cycle assessment application
 *
 *  Copyright (c) 2017 Tobias Lode <tobias@beibob.de>
 *                BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 *  eLCA is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  eLCA is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Elca\Model\Processing;

use Elca\Model\ProcessConfig\UsefulLife;

class NumberOfReplacementsCalculator
{
    /**
     * @var int
     */
    private $projectLifeTime;

    public function __construct(int $projectLifeTime)
    {
        $this->projectLifeTime = $projectLifeTime;
    }

    public function compute(UsefulLife $usefulLife, bool $isExtant): int
    {
        if ($isExtant) {
            /**
             * Always round up. Reduce project life time by delay value
             */
            return $usefulLife->inYears() < $this->projectLifeTime ? max(
                0,
                ceil(($this->projectLifeTime - $usefulLife->delayInYears()) / $usefulLife->inYears())
            ) : 0;

        }

        /**
         * Exclude last maintenance interval of the element from the calculation.
         */
        return $this->projectLifeTime % $usefulLife->inYears() === 0 ? max(
            0,
            (int)($this->projectLifeTime / $usefulLife->inYears()) - 1
        ) : floor($this->projectLifeTime / $usefulLife->inYears());
    }
}