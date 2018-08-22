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

namespace Elca\Model\Processing;

use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\Stage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;

class ProcessLifeCycleLcaResults
{
    private const A13_AGGREGATION = 'a13';

    /**
     * @var IndicatorResults[]
     */
    private $indicatorResults;

    /**
     * @var int|null
     */
    private $numReplacements;

    /**
     * @var Quantity
     */
    private $quantity;

    /**
     * @var float|null
     */
    private $mass;

    /**
     * @var bool
     */
    private $a13HasBeenAggregated;

    public function __construct(Quantity $quantity, float $mass = null)
    {
        $this->quantity = $quantity;
        $this->mass     = $mass;

        $this->indicatorResults     = [];
        $this->numReplacements      = 0;
        $this->a13HasBeenAggregated = false;
    }

    /**
     * @return IndicatorResults[]
     */
    public function indicatorResults(): array
    {
        return $this->indicatorResults;
    }

    public function processModuleRatios(): ?\Generator
    {
        foreach ($this->indicatorResults as $indicatorResult) {
            if (!$indicatorResult->hasProcessId()) {
                continue;
            }

            yield (string)$indicatorResult->processId() => $indicatorResult->moduleRatio();
        }
    }

    public function a13HasBeenAggregated(): bool
    {
        return $this->a13HasBeenAggregated;
    }

    public function hasProcess(ProcessId $processId): bool
    {
        return isset($this->indicatorResults[(string)$processId]);
    }

    public function moduleRatioFor(ProcessId $processId): ?float
    {
        return $this->hasProcess($processId) ? $this->indicatorResults[(string)$processId]->moduleRatio() : null;
    }

    public function numReplacements(): int
    {
        return $this->numReplacements ?? 0;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function mass(): ?float
    {
        return $this->mass;
    }

    public function addProcessIndicatorResults(IndicatorResults $indicatorResults): void
    {
        if ($indicatorResults->module()->isA1A2OrA3()) {
            $this->indicatorResults[self::A13_AGGREGATION] = isset($this->indicatorResults[self::A13_AGGREGATION])
                ? $this->indicatorResults[self::A13_AGGREGATION]->add($indicatorResults)
                : new IndicatorResults(
                    Module::a13(),
                    \iterator_to_array($indicatorResults->getIterator()),
                    $indicatorResults->processId(),
                    $indicatorResults->moduleRatio()
                );

            $this->a13HasBeenAggregated = true;
        }

        $this->indicatorResults[(string)$indicatorResults->processId()] = $indicatorResults;
    }

    /**
     *
     */
    public function aggregateMaintenance(int $numReplacements, LifeCycleUsages $lifeCycleUsages): void
    {
        $this->numReplacements = $numReplacements;

        $maintenance = [];

        foreach ($this->indicatorResults as $indicatorResults) {
            if (!$indicatorResults->stage()->isOneOf([Stage::PROD, Stage::EOL, Stage::REC])) {
                continue;
            }

            if (!$lifeCycleUsages->moduleIsAppliedInMaintenance($indicatorResults->module())) {
                continue;
            }

            if ($this->a13HasBeenAggregated && $indicatorResults->module()->isA1A2OrA3()) {
                continue;
            }

            foreach ($indicatorResults as $indicatorResult) {
                $indicatorId = (string)$indicatorResult->indicatorId();
                if (!isset($maintenance[$indicatorId])) {
                    $maintenance[$indicatorId] = 0;
                }

                if ($numReplacements > 0) {
                    $maintenance[$indicatorId] += $indicatorResult->value() * $numReplacements;
                }
            }
        }

        $this->indicatorResults['maint'] = IndicatorResults::forMaintenance(
            IndicatorResult::valuesFromMap($maintenance)
        );
    }
}
