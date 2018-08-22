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

namespace Elca\Model\ProcessConfig\LifeCycle;

use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Transform\ArrayOfObjects;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\ProcessName;
use Elca\Model\Process\Stage;

class Process
{
    /**
     * @var ProcessId
     */
    private $id;

    /**
     * @var Module
     */
    private $module;

    /**
     * @var float
     */
    private $moduleRatio;

    /**
     * @var Quantity
     */
    private $quantitativeReference;

    /**
     * @var ProcessName
     */
    private $name;

    /**
     * @var array|IndicatorValue[]
     */
    private $indicatorValues;

    /**
     *
     * @param ProcessId              $processId
     * @param Module                 $module
     * @param Quantity               $quantitativeReference
     * @param ProcessName            $name
     * @param float                  $moduleRatio
     * @param array|IndicatorValue[] $indicatorValues
     */
    public function __construct(
        ProcessId $processId, Module $module, Quantity $quantitativeReference, ProcessName $name,
        float $moduleRatio = 1, array $indicatorValues = []
    ) {
        $this->id                    = $processId;
        $this->module                = $module;
        $this->quantitativeReference = $quantitativeReference;
        $this->name                  = $name;
        $this->moduleRatio           = $moduleRatio;
        $this->indicatorValues       = ArrayOfObjects::from($indicatorValues)->mapPropertyToObject('ident');
    }

    public function id(): ProcessId
    {
        return $this->id;
    }

    public function module(): Module
    {
        return $this->module;
    }

    public function stage(): Stage
    {
        return $this->module->stage();
    }

    public function quantitativeReference(): Quantity
    {
        return $this->quantitativeReference;
    }

    public function name(): ProcessName
    {
        return $this->name;
    }

    public function moduleRatio(): float
    {
        return $this->moduleRatio;
    }

    /**
     * @return IndicatorValue[]
     */
    public function indicatorValues(): array
    {
        return $this->indicatorValues;
    }

    public function hasIndicatorValueFor(IndicatorIdent $ident): bool
    {
        return isset($this->indicatorValues[(string)$ident]);
    }

    public function indicatorValueFor(IndicatorIdent $ident): IndicatorValue
    {
        return $this->indicatorValues[(string)$ident] ?? new IndicatorValue($ident);
    }
}
