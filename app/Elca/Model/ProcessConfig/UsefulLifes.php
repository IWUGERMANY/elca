<?php declare(strict_types=1);
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

namespace Elca\Model\ProcessConfig;

use Elca\Model\Exception\InvalidArgumentException;

class UsefulLifes
{
    /**
     * @var UsefulLife|null
     */
    private $min;

    /**
     * @var UsefulLife|null
     */
    private $average;

    /**
     * @var UsefulLife|null
     */
    private $max;

    /**
     * @var string|null
     */
    private $information;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(UsefulLife $min = null, UsefulLife $average = null, UsefulLife $max = null, string $information = null)
    {
        $this->guardAtLeastOne($min, $average, $max);
        $this->guardValues($min, $average, $max);

        $this->min         = $min;
        $this->average     = $average;
        $this->max         = $max;
        $this->information = $information;
    }

    public function min(): ?UsefulLife
    {
        return $this->min;
    }

    public function average(): ?UsefulLife
    {
        return $this->average;
    }

    public function max(): ?UsefulLife
    {
        return $this->max;
    }

    public function information(): string
    {
        return $this->information;
    }

    /**
     * Returns the default useful life which is the minimum
     */
    public function defaultValue() : ?UsefulLife
    {
        if (null !== $this->min()) {
            return $this->min();
        }

        if (null !== $this->average()) {
            return $this->average();
        }

        return $this->max();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function guardAtLeastOne(?UsefulLife $min, ?UsefulLife $average, ?UsefulLife $max): void
    {
        if (null === $min && null === $average && null === $max) {
            throw new InvalidArgumentException('At least one UsefulLife has to be specified in the constructor');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function guardValues(?UsefulLife $min, ?UsefulLife $average, ?UsefulLife $max): void
    {
        if (null !== $min && null !== $average && $min->greaterThan($average)) {
            throw new InvalidArgumentException('Minimum value is greater than average value');
        }
        if (null !== $average && null !== $max && $average->greaterThan($max)) {
            throw new InvalidArgumentException('Avergage value is greater than max value');
        }

        if (null !== $min && null !== $max && $min->greaterThan($max)) {
            throw new InvalidArgumentException('Min value is greater than max value');
        }
    }
}
