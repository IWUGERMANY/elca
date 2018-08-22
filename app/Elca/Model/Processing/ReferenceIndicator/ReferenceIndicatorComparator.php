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

namespace Elca\Model\Processing\ReferenceIndicator;

use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Indicator\ReferenceIndicatorValue;

class ReferenceIndicatorComparator
{
    /**
     * @var ReferenceIndicatorValue
     */
    private $referenceValue;

    /**
     * @var IndicatorValue
     */
    private $indicatorValue;

    public function __construct(ReferenceIndicatorValue $referenceValue, IndicatorValue $indicatorValue)
    {
        if (false === $referenceValue->ident()->equals($indicatorValue->ident())) {
            throw new \InvalidArgumentException(sprintf('Indicators does not match: %s <> %s', $referenceValue->ident(), $indicatorValue->ident()));
        }

        $this->referenceValue = $referenceValue;
        $this->indicatorValue = $indicatorValue;
    }

    /**
     * Returns -1 if the given indicator value is being less good as the given reference indicator value
     * Returns 0 if the given indicator value is being equal good as the given reference indicator value
     * Returns 1 if the given indicator value is being better as the given reference indicator value
     */
    public function compare(): int
    {
//        $deviation = $this->deviation();
//        if (FloatCalc::lt($deviation,-1/3)) {
//            return 1;
//        }
//        if (FloatCalc::gt($deviation,1/3)) {
//            return -1;
//        }

        if ($this->indicatorValue->value() > $this->referenceValue->maxValue()) {
            return $this->indicatorValue->ident()->isRenewablePrimaryEnergy() ? 1 : -1;
        }

        if ($this->indicatorValue->value() < $this->referenceValue->minValue()) {
            return $this->indicatorValue->ident()->isRenewablePrimaryEnergy() ? -1 : 1;
        }

        return 0;
    }

    /**
     * Returns the deviation of the given indicator value from the reference value as float.
     */
    public function deviation(): float
    {
        return ($this->indicatorValue->value() - $this->referenceValue->avgValue()) / abs($this->referenceValue->avgValue());
    }
}
