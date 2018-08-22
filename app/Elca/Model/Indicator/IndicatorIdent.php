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

namespace Elca\Model\Indicator;

final class IndicatorIdent
{
    public const PE_N_EM = 'peNEm';
    public const PE_EM = 'peEm';
    public const PET = 'pet';
    public const PERE = 'pere';
    public const PERM = 'perm';
    public const PENRE = 'penre';
    public const PENRM = 'penrm';
    public const PERT = 'pert';
    public const PENRT = 'penrt';
    public const GWP = 'gwp';
    public const ODP = 'odp';
    public const ADP = 'adp';

    /**
     * Pseudo ident
     */
    public const PE = 'pe';

    /**
     * Indicator idents for primary energy renewable
     */
    private static $primaryEnergyRenewableIndicators = [
        self::PE_EM,
        self::PERE,
        self::PERM,
        self::PERT,
    ];

    /**
     * Indicator idents for primary energy not renewable
     */
    private static $primaryEnergyNotRenewableIndicators = [
        self::PE_N_EM,
        self::PENRE,
        self::PENRM,
        self::PENRT,
    ];

    /**
     * @var string
     */
    private $value;

    public static function primaryEnergyRenewableIndicators(): array
    {
        return self::$primaryEnergyRenewableIndicators;
    }

    public static function primaryEnergyNotRenewableIndicators(): array
    {
        return self::$primaryEnergyNotRenewableIndicators;
    }

    public static function pet(): IndicatorIdent
    {
        return new self(self::PET);
    }

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isRenewablePrimaryEnergy(): bool
    {
        return \in_array($this->value, self::$primaryEnergyRenewableIndicators, true);
    }

    public function isNotRenewablePrimaryEnergy(): bool
    {
        return \in_array($this->value, self::$primaryEnergyNotRenewableIndicators, true);
    }

    public function isPrimaryEnergyIndicator(): bool
    {
        return \in_array(
            $this->value(),
            \array_merge(
                [self::PET, self::PE],
                self::$primaryEnergyNotRenewableIndicators,
                self::$primaryEnergyRenewableIndicators

            ),
            true
        );
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(IndicatorIdent $other): bool
    {
        return $this->value === $other->value;
    }

    public function isOneOf(array $primaryEnergyIndicatorModules): bool
    {
        return \in_array($this->value, $primaryEnergyIndicatorModules, true);
    }
}
