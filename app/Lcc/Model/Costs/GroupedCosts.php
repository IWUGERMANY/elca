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

namespace Lcc\Model\Costs;

class GroupedCosts implements Costs
{
    /**
     * @var string
     */
    private $group;

    /**
     * @var float
     */
    private $costs;

    /**
     * @var string
     */
    private $currency;

    public function __construct(string $group, float $costs, string $currency = '€')
    {
        $this->group = $group;
        $this->costs = $costs;
        $this->currency = $currency;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function costs(): float
    {
        return $this->costs;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function __toString()
    {
        return $this->costs .' '. $this->currency;
    }

    public function amount(): float
    {
        return $this->costs;
    }
}
