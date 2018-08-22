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

class TotalCosts implements Costs
{
    /**
     * @var float
     */
    private $bgf;

    /**
     * @var Costs[]|array
     */
    private $costs;

    /**
     * @var string
     */
    private $currency;

    /**
     * @param Costs[]|array $costs
     * @param float|null    $bgf
     */
    public function __construct(array $costs, string $currency, float $bgf = null)
    {
        $this->guardCurrency($costs, $currency);

        $this->bgf   = $bgf;
        $this->costs = $costs;
        $this->currency = $currency;
    }

    public function bgf(): float
    {
        return $this->bgf;
    }

    public function costs(): array
    {
        return $this->costs;
    }

    public function amount(): float
    {
        $totalCosts = 0;

        foreach ($this->costs as $costs) {
            $totalCosts += $costs->amount();
        }

        return $totalCosts;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function costsPerBgf(): ?float
    {
        if (null === $this->bgf) {
            return null;
        }

        return $this->amount() / $this->bgf;
    }

    public function __toString()
    {
        return sprintf('%s %s', $this->amount(), $this->currency);
    }

    private function guardCurrency(array $costs, string $currency): void
    {
        foreach ($costs as $cost) {
            if ($currency !== $cost->currency()) {
                throw new \InvalidArgumentException(
                    'Mixed currencies are not allowed: found '.$cost->currency().' within costs of currency '.$currency
                );
            }
        }
    }
}
