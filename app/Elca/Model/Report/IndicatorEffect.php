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

namespace Elca\Model\Report;

use Elca\Db\ElcaLifeCycle;
use Elca\Model\Indicator\Indicator;

class IndicatorEffect
{
    /**
     * @var Indicator
     */
    private $indicator;

    /**
     * @var number[]
     */
    private $phaseValues;

    /**
     * IndicatorEffect constructor.
     *
     * @param Indicator $indicator
     * @param number[] $phaseValues
     */
    public function __construct(Indicator $indicator, array $phaseValues)
    {
        $this->indicator   = $indicator;
        $this->phaseValues = $phaseValues;
    }

    /**
     * @return Indicator
     */
    public function indicator()
    {
        return $this->indicator;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->indicator->name();
    }


    /**
     * @return string
     */
    public function unit()
    {
        return $this->indicator->unit();
    }

    /**
     * @return number|null
     */
    public function total()
    {
        if (!isset($this->phaseValues[ElcaLifeCycle::PHASE_TOTAL])) {
            return null;
        }

        return $this->phaseValues[ElcaLifeCycle::PHASE_TOTAL];
    }

    /**
     * @return number|null
     */
    public function maint()
    {
        if (!isset($this->phaseValues[ElcaLifeCycle::PHASE_MAINT])) {
            return null;
        }

        return $this->phaseValues[ElcaLifeCycle::PHASE_MAINT];
    }


    /**
     * @return number|null
     */
    public function prod()
    {
        if (!isset($this->phaseValues[ElcaLifeCycle::PHASE_PROD])) {
            return null;
        }

        return $this->phaseValues[ElcaLifeCycle::PHASE_PROD];
    }

    /**
     * @return number|null
     */
    public function op()
    {
        if (!isset($this->phaseValues[ElcaLifeCycle::PHASE_OP])) {
            return null;
        }

        return $this->phaseValues[ElcaLifeCycle::PHASE_OP];
    }

    /**
     * @return number|null
     */
    public function eol()
    {
        if (!isset($this->phaseValues[ElcaLifeCycle::PHASE_EOL])) {
            return null;
        }

        return $this->phaseValues[ElcaLifeCycle::PHASE_EOL];
    }

    /**
     * @return number|null
     */
    public function rec()
    {
        if (!isset($this->phaseValues[ElcaLifeCycle::PHASE_REC])) {
            return null;
        }

        return $this->phaseValues[ElcaLifeCycle::PHASE_REC];
    }

    /**
     * is triggered by calling isset() or empty() on inaccessible members.
     *
     * @param $name string
     * @return bool
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __isset($name)
    {
        return method_exists($this, $name);
    }


    /**
     * Interface for table builder which access properties
     *
     * @param $name string
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get($name)
    {
        if (!method_exists($this, $name)) {
            trigger_error('No such method '. $name);
        }

        return $this->$name();
    }
}
