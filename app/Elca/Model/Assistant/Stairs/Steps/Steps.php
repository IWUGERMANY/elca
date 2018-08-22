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

use Assert\Assertion;

class Steps
{
    /**
     * @var Step
     */
    private $step;

    /**
     * @var int
     */
    private $amount;

    /**
     * Steps constructor.
     *
     * @param int   $amount
     * @param Step  $step
     */
    public function __construct(Step $step, $amount)
    {
        Assertion::true($amount > 0, 'At least one step has to be defined', 'amount');

        $this->step = $step;
        $this->amount = $amount;
    }

    /**
     * @return Step
     */
    public function getStep()
    {
        return $this->step;
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
    public function getLength()
    {
        $depth = $this->step->getDepth();
        $height = $this->step->getHeight();

        return $this->amount * sqrt($depth * $depth + $height * $height);
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->amount * $this->step->getHeight();

    }

    /**
     * @return int
     */
    public function getVolume()
    {
        return $this->amount * $this->step->getVolume();
    }
}
