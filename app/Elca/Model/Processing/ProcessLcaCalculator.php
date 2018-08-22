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

namespace Elca\Model\Processing;

use Beibob\Blibs\Interfaces\Logger;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Exception\InvalidArgumentException;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\ProcessConfig\Conversion\ConversionException;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;

class ProcessLcaCalculator
{
    private static $primaryEnergyIndicatorModules = [
        IndicatorIdent::PE_EM,
        IndicatorIdent::PE_N_EM,
        IndicatorIdent::PERT,
        IndicatorIdent::PENRT,
    ];

    /**
     * @var ProcessLifeCycle
     */
    private $processLifeCycle;

    /**
     * @var Indicator|null
     */
    private $petIndicator;

    /**
     * @var Logger|null
     */
    private $logger;

    /**
     * @var array|Indicator[]
     */
    private $indicators;

    /**
     * @var ProcessLifeCycle  $processLifeCycle
     * @var array|Indicator[] $indicators
     * @var Logger            $logger
     */
    public function __construct(ProcessLifeCycle $processLifeCycle, array $indicators, Logger $logger = null)
    {
        $this->processLifeCycle = $processLifeCycle;
        $this->indicators       = $indicators;
        $this->logger           = $logger;

        $this->initPetIndicator($indicators);
    }

    /**
     * @throws ConversionException
     * @throws ProcessNotFoundInLifeCycleException
     */
    public function compute(Process $process, Quantity $inQuantity): IndicatorResults
    {
        if (null === $this->processLifeCycle->findProcessById($process->id())) {
            throw new ProcessNotFoundInLifeCycleException($process->id(), $this->processLifeCycle->processConfigId());
        }

        /**
         * Convert quantity into process refUnit
         */
        $moduleRatio = $this->retrieveModuleRatio($process);

        $quantitativeReference = $process->quantitativeReference();

        $convertedQuantity = $moduleRatio * $this->convertQuantity(
                $this->processLifeCycle,
                $inQuantity,
                $quantitativeReference->unit()
            );

        $this->logProcessQuantityConversion($process, $inQuantity, $convertedQuantity);

        $values = [];

        // Prepare total primary energy
        $petIndicatorResult = new IndicatorResult($this->petIndicator->id(), 0.0);

        // Compute indicators
        foreach ($this->indicators as $indicator) {
            $indicatorIdent        = $indicator->ident();

            if ($this->petIndicator->ident()->equals($indicatorIdent)) {
                continue;
            }

            $indicatorResult       = null;
            $processIndicatorValue = $process->indicatorValueFor($indicatorIdent);

            if ($processIndicatorValue->isDefined()) {
                $indicatorResult = new IndicatorResult(
                    $indicator->id(),
                    $processIndicatorValue->value() * $convertedQuantity / $quantitativeReference->value()
                );

                if ($indicatorIdent->isOneOf(self::$primaryEnergyIndicatorModules)) {
                    $petIndicatorResult = $petIndicatorResult->add($indicatorResult->value());
                }
            }

            $values[] = $indicatorResult ?? new IndicatorResult($indicator->id(), null);
        }

        $values[] = $petIndicatorResult;

        return new IndicatorResults($process->module(), $values, $process->id(), $process->moduleRatio());
    }

    public function retrieveModuleRatio(Process $process): float
    {
        $moduleRatio = $process->moduleRatio();

        if (null === $moduleRatio || $moduleRatio < 0 || $moduleRatio > 1) {
            $moduleRatio = 1;
        }

        return $moduleRatio;
    }

    /**
     * @throws ConversionException
     */
    private function convertQuantity(ProcessLifeCycle $processLifeCycle, Quantity $inQuantity, Unit $toUnit): float
    {
        try {
            $result = $processLifeCycle
                ->converter()
                ->convert($inQuantity->value(), $inQuantity->unit(), $toUnit);
        } catch (ConversionException $exception) {
            $this->logMissingConversion($processLifeCycle, $inQuantity, $toUnit);

            throw $exception;
        }

        return $result;
    }

    private function initPetIndicator(array $indicators): void
    {
        $result = \array_filter(
            $indicators,
            function (Indicator $indicator) {
                return $indicator->ident()->value() === IndicatorIdent::PET;
            }
        );

        if (empty($result)) {
            throw new InvalidArgumentException('No PET indicator was found in set of indicators');
        }

        $this->petIndicator = \current($result);
    }

    private function logProcessQuantityConversion(
        Process $process, Quantity $inQuantity, float $result
    ): void {
        if (null === $this->logger) {
            return;
        }

        $this->logger->debug(
            sprintf(
                'Process %s [%s, %s, %s %%] %s >> %s',
                $process->name(),
                $process->quantitativeReference(),
                $process->module(),
                $process->moduleRatio() * 100,
                $inQuantity,
                $result
            ),
            __METHOD__
        );
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
