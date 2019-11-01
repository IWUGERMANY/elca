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
use Elca\Model\Common\Unit;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\Stage;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\ConversionSet;
use Elca\Model\ProcessConfig\Conversion\RequiredConversion;
use Elca\Model\ProcessConfig\Converter;
use Elca\Model\ProcessConfig\ProcessConfigId;

class ProcessLifeCycle
{
    /**
     * @var ProcessConfigId
     */
    private $processConfigId;

    /**
     * @var ProcessDbId
     */
    private $processDbId;

    /**
     * @var Process[]
     */
    private $processes;

    /**
     * @var Conversion[]
     */
    private $conversions;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * ProcessLifeCycleConversions constructor.
     *
     * @param ProcessConfigId $processConfigId
     * @param ProcessDbId     $processDbId
     * @param Process[]       $processes
     * @param Conversion[]    $conversions
     */
    public function __construct(
        ProcessConfigId $processConfigId, ProcessDbId $processDbId, array $processes, array $conversions = []
    ) {
        $this->processConfigId = $processConfigId;
        $this->processDbId     = $processDbId;
        $this->processes       = ArrayOfObjects::from($processes)->mapPropertyToObject('id');
        $this->conversions     = $conversions;
    }

    public function processConfigId(): ProcessConfigId
    {
        return $this->processConfigId;
    }

    public function processDbId(): ProcessDbId
    {
        return $this->processDbId;
    }

    /**
     * @return Process[]
     */
    public function processes(): array
    {
        return $this->processes;
    }

    /**
     * @return Process[]
     */
    public function productionProcesses(): array
    {
        return $this->processesByStage(Stage::production());
    }

    /**
     * @return Process[]
     */
    public function usageProcesses(): array
    {
        return $this->processesByStage(Stage::usage());
    }

    /**
     * @return Conversion[]
     */
    public function requiredConversions(): array
    {
        $units = $this->requiredUnits();

        if (\count($units) < 2) {
            return [];
        }

        $firstUnit = \array_shift($units);
        $converter = $this->converter();

        $conversions = [];
        foreach ($units as $unit) {
            $conversion = $converter->find($firstUnit, $unit) ?? new RequiredConversion($firstUnit, $unit);

            if (!$conversion->isKnown()) {
                $invertedConversion = $conversion->invert();

                if ($invertedConversion->isKnown()) {
                    $conversion = $invertedConversion;
                }
            }

            $conversions[] = $conversion;
        }

        return $conversions;
    }

    public function additionalConversions(): array
    {
        $requiredConversions = new ConversionSet($this->requiredConversions());

        $additionalConversions = [];
        foreach ($this->conversions as $conversion) {
            if (null === $requiredConversions->find($conversion->fromUnit(), $conversion->toUnit())) {
                $additionalConversions[] = $conversion;
            }
        }

        return $additionalConversions;
    }

    /**
     * Retrieves all required process units
     * This method ignores usage processes!
     *
     * @return Unit[]
     */
    public function requiredUnits(): array
    {
        return $this->extractUnits(
            function (Process $process) {
                return false === $process->module()->stage()->isUsage();
            }
        );
    }

    /**
     * @return Unit[]
     */
    public function units(): array
    {
        return $this->extractUnits();
    }

    /**
     * @return Conversion[]
     */
    public function conversions(): array
    {
        return $this->conversions;
    }

    public function converter(): Converter
    {
        if (null === $this->converter) {
            $this->converter = new Converter($this->processConfigId(), $this->conversions());
        }

        return $this->converter;
    }

    public function findProcessById(ProcessId $processId): ?Process
    {
        return $this->processes[(string)$processId] ?? null;
    }

    public function quantitativeReference(Stage $stage = null): ?Quantity
    {
        $processByStage = current($this->processesByStage($stage ?? Stage::production()));

        /**
         * Fallback to usage processes if no explicit stage was requested
         */
        if (null === $stage && !$processByStage) {
            return $this->quantitativeReference(Stage::usage());
        }

        return $processByStage ? $processByStage->quantitativeReference() : null;
    }

    public function hasProcesses()
    {
        return count($this->processes) > 0;
    }

    public function hasConversions()
    {
        return count($this->conversions) > 0;
    }

    private function extractUnits(\Closure $filter = null): array
    {
        $units = [];
        foreach ($this->processes as $process) {
            if (null !== $filter && false === $filter($process)) {
                continue;
            }

            $unit                 = $process->quantitativeReference()->unit();
            $units[(string)$unit] = $unit;
        }

        return $units;
    }

    private function processesByStage(Stage $stage): array
    {
        return \array_filter(
            $this->processes(),
            function (Process $process) use ($stage) {
                return $process->stage()->equals($stage);
            }
        );
    }
}
