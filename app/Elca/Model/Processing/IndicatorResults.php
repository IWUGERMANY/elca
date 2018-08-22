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

use Elca\Model\Common\Transform\ArrayOfObjects;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\Stage;

final class IndicatorResults implements \IteratorAggregate
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var float
     */
    private $moduleRatio;

    /**
     * @var IndicatorResult[]
     */
    private $indicatorResults;

    /**
     * @var ProcessId|null
     */
    private $processId;

    /**
     * @param IndicatorResult[] $maintenance
     * @return IndicatorResults
     */
    public static function forMaintenance(array $maintenance) : self
    {
        return new self(
            Module::maintenance(), $maintenance, null, null
        );
    }

    /**
     * @param Module            $module
     * @param IndicatorResult[] $indicatorResults
     * @param ProcessId|null    $processId
     * @param float             $moduleRatio
     */
    public function __construct(Module $module, array $indicatorResults, ?ProcessId $processId, ?float $moduleRatio)
    {
        $this->module           = $module;
        $this->moduleRatio      = $moduleRatio;
        $this->indicatorResults = $indicatorResults;
        $this->processId = $processId;
    }

    public function module(): Module
    {
        return $this->module;
    }

    public function moduleRatio(): float
    {
        return $this->moduleRatio ?? 1;
    }

    public function stage(): Stage
    {
        return $this->module->stage();
    }

    public function hasProcessId(): bool
    {
        return null !== $this->processId;
    }

    public function processId(): ?ProcessId
    {
        return $this->processId;
    }

    /**
     * @return IndicatorResult[]|\ArrayIterator
     */
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator($this->indicatorResults);
    }

    public function changeModule(Module $module): IndicatorResults
    {
        return new self(
            $module,
            $this->indicatorResults,
            $this->processId,
            $this->moduleRatio
        );
    }

    public function add(IndicatorResults $otherIndicatorResults): IndicatorResults
    {
        /**
         * @var IndicatorResult[] $otherIndicatorResultsMap
         */
        $otherIndicatorResultsMap = ArrayOfObjects::from($otherIndicatorResults->indicatorResults)->mapPropertyToObject('indicatorId');

        $newResults = [];
        foreach ($this->indicatorResults as $indicatorResult) {
            if (isset($otherIndicatorResultsMap[(string)$indicatorResult->indicatorId()])) {
                $newResults[] = $indicatorResult->add(
                    $otherIndicatorResultsMap[(string)$indicatorResult->indicatorId()]->value()
                );
            }
            else {
                $newResults[] = $indicatorResult;
            }
        }

        return new self(
            $this->module,
            $newResults,
            $this->processId,
            $this->moduleRatio
        );
    }

}