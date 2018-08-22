<?php declare(strict_types=1);
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2017 Tobias Lode <tobias@beibob.de>
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

use Beibob\Blibs\Interfaces\Logger;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Process\Module;
use Elca\Model\ProcessConfig\Conversion\ConversionException;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\UsefulLife;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;

class ElementComponentLcaCalculator
{
    /**
     * Indicators will not be included in the lca for this modules
     */
    private static $excludeModules = [
        Module::A4,
        Module::A5,
        Module::C1,
        Module::C2,
    ];

    /**
     * @var LifeCycleUsages
     */
    private $lifeCycleUsages;

    /**
     * @var int
     */
    private $projectLifeTime;

    /**
     * @var array
     */
    private $indicators;

    /**
     * @var Logger|null
     */
    private $logger;

    /**
     * @param LifeCycleUsages $lifeCycleUsages
     * @param int             $projectLifeTime
     * @param Indicator[]     $indicators
     * @param Logger|null     $logger
     */
    public function __construct(
        LifeCycleUsages $lifeCycleUsages, int $projectLifeTime, array $indicators, Logger $logger = null
    ) {
        $this->lifeCycleUsages = $lifeCycleUsages;
        $this->projectLifeTime = $projectLifeTime;
        $this->indicators      = $indicators;
        $this->logger          = $logger;
    }

    /**
     * @throws ProcessNotFoundInLifeCycleException
     * @throws ConversionException
     * @throws \Exception
     */
    public function compute(
        ProcessLifeCycle $processLifeCycle, Quantity $componentQuantity, UsefulLife $usefulLife,
        bool $isExtantComponent = false
    ): ProcessLifeCycleLcaResults {
        /**
         * Compute mass (if not already)
         */
        $mass = $this->computeMass($processLifeCycle, $componentQuantity);

        /**
         * Init result set
         */
        $componentResults = new ProcessLifeCycleLcaResults(
            $componentQuantity, $mass
        );

        $processLcaCalculator = new ProcessLcaCalculator(
            $processLifeCycle,
            $this->indicators,
            $this->logger
        );

        foreach ($processLifeCycle->processes() as $process) {

            if ($this->isExcluded($process)) {
                continue;
            }

            $componentResults->addProcessIndicatorResults(
                $processLcaCalculator->compute($process, $componentQuantity)
            );
        }

        $numReplacements = (new NumberOfReplacementsCalculator($this->projectLifeTime))
            ->compute($usefulLife, $isExtantComponent);

        $componentResults->aggregateMaintenance($numReplacements, $this->lifeCycleUsages);

        return $componentResults;
    }

    /**
     * Compute indicators for each configured process,
     * except all usage and predefined modules
     */
    private function isExcluded(Process $process): bool
    {
        return $process->stage()->isUsage() ||
               \in_array($process->module()->value(), self::$excludeModules, true);
    }

    private function computeMass(ProcessLifeCycle $processLifeCycle, Quantity $quantity): float
    {
        $componentMassCalculator = new ComponentMassCalculator();

        try {
            $mass = $componentMassCalculator->compute($processLifeCycle->converter(), $quantity);
        }
        catch (ConversionException $exception) {
            $this->logMissingConversion($processLifeCycle, $quantity, Unit::kg());

            $mass = 0;
        }

        return $mass;
    }

    private function logMissingConversion(ProcessLifeCycle $processLifeCycle, Quantity $inQuantity, Unit $toUnit): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->fatal(
            sprintf(
                '%s: conversion (%s >> %s) not found',
                $processLifeCycle->processConfigId(),
                $inQuantity->unit(),
                $toUnit
            ),
            __METHOD__
        );
    }
}
