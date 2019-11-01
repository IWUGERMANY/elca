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

use Elca\Db\ElcaElementComponent;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Exception\InvalidArgumentException;
use Elca\Model\Indicator\IndicatorRepository;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Process\Stage;
use Elca\Model\ProcessConfig\ConversionId;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\Processing\Element\ElementComponentQuantity;
use Elca\Service\ProcessConfig\Conversions;


class ExtantSavingsCalculator
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
     * @var ProcessLifeCycleRepository
     */
    private $processLifeCycleRepository;

    /**
     * @var IndicatorRepository
     */
    private $indicatorRepository;

    /**
     * @var Conversions
     */
    private $conversions;

    public function __construct(
        ProcessLifeCycleRepository $processLifeCycleRepository,
        IndicatorRepository $indicatorRepository,
        Conversions $conversions
    )
    {
        $this->processLifeCycleRepository = $processLifeCycleRepository;
        $this->indicatorRepository        = $indicatorRepository;
        $this->conversions                = $conversions;
    }

    public function computeElementComponentSavings(ElcaElementComponent $component, ProcessDbId $processDbId)
    {
        /**
         * Skip this component if lca is not enabled
         */
        if (!$component->getCalcLca() || !$component->isExtant()) {
            return null;
        }

        $processLifeCycle = $this->processLifeCycleRepository->findByIdAndStage(
            new ProcessConfigId($component->getProcessConfigId()),
            $processDbId,
            Stage::production()
        );

        /**
         * Compute the quantity of the component
         */
        $quantity = $this->computeElementComponentQuantity($component, $processDbId);

        /**
         * Init result set
         */
        $componentResults = new ProcessLifeCycleLcaResults($quantity);

        $processLcaCalculator = new ProcessLcaCalculator(
            $processLifeCycle,
            $this->indicatorRepository->findForProcessingByProcessDbId($processDbId)
        );

        foreach ($processLifeCycle->productionProcesses() as $process) {
            if (!$process->stage()->isProduction() || $this->isExcluded($process)) {
                continue;
            }

            $componentResults->addProcessIndicatorResults(
                $processLcaCalculator->compute($process, $quantity)
            );
        }

        return $componentResults->indicatorResults();
    }

    /**
     * Conputes the quantity for an ElementComponent
     *
     * @param ElcaElementComponent $elcaElementComponent
     * @return Quantity
     */
    public function computeElementComponentQuantity(ElcaElementComponent $elcaElementComponent,
        ProcessDbId $processDbId): Quantity
    {
        $processConversion = $this->conversions->findConversion(new ConversionId($elcaElementComponent->getProcessConversionId()),
            $processDbId);

        if (null === $processConversion) {
            throw new InvalidArgumentException('Could not find a conversion for conversionId=:conversionId: and processDbId=:processDbId:', [
                ':conversionId:' => $processConversion->conversionId(),
                ':processDbId:' => $processDbId
            ]);
        }

        $elementComponent = ElementComponentQuantity::fromElcaElementComponent($elcaElementComponent,
            $processConversion);

        /**
         * Convert quantity into outUnits
         */
        return $elementComponent->convertedQuantity();
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
}
