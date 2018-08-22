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

class Indicator
{
    /**
     * @var IndicatorId
     */
    private $id;

    /**
     * @var IndicatorIdent
     */
    private $ident;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $unit;

    /**
     * @var bool
     */
    private $isEn15804Compliant;

    /**
     * Indicator constructor.
     *
     * @param int    $id
     * @param        $name
     * @param string $ident
     * @param string $unit
     * @param bool   $isEn15804Compliant
     */
    public function __construct(
        IndicatorId $id, string $name, IndicatorIdent $ident, string $unit, bool $isEn15804Compliant
    ) {
        $this->id                 = $id;
        $this->ident              = $ident;
        $this->unit               = $unit;
        $this->isEn15804Compliant = $isEn15804Compliant;
        $this->name               = $name;
    }

    /**
     * @return IndicatorId
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return IndicatorIdent
     */
    public function ident()
    {
        return $this->ident;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function unit()
    {
        return $this->unit;
    }

    /**
     * @return boolean
     */
    public function isEn15804Compliant()
    {
        return $this->isEn15804Compliant;
    }

    /**
     * @return bool
     */
    public function isRenewablePrimaryEnergy()
    {
        return \in_array($this->ident, IndicatorIdent::primaryEnergyRenewableIndicators(), true);
    }

    /**
     * @return bool
     */
    public function isNotRenewablePrimaryEnergy()
    {
        return \in_array($this->ident, IndicatorIdent::primaryEnergyNotRenewableIndicators(), true);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
