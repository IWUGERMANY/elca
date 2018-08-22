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

namespace Elca\Service\ProcessConfig;

use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConversion;
use Elca\Model\Common\Unit;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\ConversionSet;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\RecommendedConversion;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfig;
use Elca\Model\ProcessConfig\ProcessConfigId;

class Conversions
{
    /**
     * @var ProcessLifeCycleRepository
     */
    private $processLifeCycleRepository;

    public function __construct(ProcessLifeCycleRepository $processLifeCycleRepository)
    {
        $this->processLifeCycleRepository = $processLifeCycleRepository;
    }

    /**
     * This method keeps the density field and the density conversion in sync.
     *
     * It returns an indicator flag, if the state or value was changed
     */
    public function changeProcessConfigDensity(ElcaProcessConfig $processConfig, float $density = null): bool
    {
        $densityConversion = $this->findDensityConversionFor($processConfig->getId());
        $hasChanged        = $densityConversion->getFactor() !== $density;

        $processConfig->setDensity($density);

        if ($density === null) {
            if ($densityConversion->isInitialized()) {
                $densityConversion->delete();
            }

            return $hasChanged;
        }

        if ($densityConversion->isInitialized()) {
            $densityConversion->setFactor($density);
            $densityConversion->update();
        } else {
            ElcaProcessConversion::create(
                $processConfig->getId(),
                Unit::CUBIC_METER,
                Unit::KILOGRAMM,
                $density
            );
        }

        return $hasChanged;
    }

    public function changeProcessConfigDefaultSize(ElcaProcessConfig $processConfig, float $defaultSize = null): bool
    {
        $hasChanged = $defaultSize !== $processConfig->getDefaultSize();

        $processConfig->setDefaultSize($defaultSize);

        return $hasChanged;
    }

    public function computeDensityFromMpua(ElcaProcessConfig $processConfig, ?float $defaultSize): ?float
    {
        if (null === $defaultSize) {
            return null;
        }

        $avgMpuaConversion = $this->findAvgMpuaConversionFor($processConfig->getId());
        if (!$avgMpuaConversion->isInitialized()) {
            return null;
        }

        return $avgMpuaConversion->getFactor() / $defaultSize;
    }

    public function computeDefaultSizeFromDensity(ElcaProcessConfig $processConfig, ?float $density): ?float
    {
        if (null === $density) {
            return null;
        }

        $avgMpuaConversion = $this->findAvgMpuaConversionFor($processConfig->getId());
        if (!$avgMpuaConversion->isInitialized()) {
            return null;
        }

        return $avgMpuaConversion->getFactor() / $density;
    }

    public function findAllConversions(ProcessConfigId $processConfigId): ConversionSet
    {
        $processLifeCycles = $this->processLifeCycleRepository->findAllByProcessConfigId($processConfigId);

        $conversions = [[]];
        foreach ($processLifeCycles as $processLifeCycle) {
            $conversions[] = $processLifeCycle->conversions();
        }

        return new ConversionSet(\array_unique(\array_merge(...$conversions), SORT_REGULAR));
    }

    public function findAllRequiredConversions(ProcessConfigId $processConfigId): ConversionSet
    {
        $processLifeCycles = $this->processLifeCycleRepository->findAllByProcessConfigId($processConfigId);

        $conversions = [[]];
        foreach ($processLifeCycles as $processLifeCycle) {
            $conversions[] = $processLifeCycle->requiredConversions();
        }

        return new ConversionSet(\array_unique(\array_merge(...$conversions), SORT_REGULAR));
    }

    public function findAllAdditionalConversions(ProcessConfigId $processConfigId): ConversionSet
    {
        $requiredConversions = $this->findAllRequiredConversions($processConfigId)->toArray();

        $processLifeCycles = $this->processLifeCycleRepository->findAllByProcessConfigId($processConfigId);

        $conversions = [[]];
        foreach ($processLifeCycles as $processLifeCycle) {
            $conversions[] = \array_diff($processLifeCycle->additionalConversions(), $requiredConversions);
        }

        return new ConversionSet(\array_unique(\array_merge(...$conversions), SORT_REGULAR));
    }

    public function findAllRequiredUnits(ProcessConfigId $processConfigId): array
    {
        $processLifeCycles = $this->processLifeCycleRepository->findAllByProcessConfigId($processConfigId);

        $units = [];
        foreach ($processLifeCycles as $processLifeCycle) {
            $units += $processLifeCycle->requiredUnits();
        }

        return $units;
    }

    public function findRecommendedConversions(ProcessConfigId $processConfigId): ConversionSet
    {
        $processLifeCycles = $this->processLifeCycleRepository->findAllByProcessConfigId($processConfigId);

        $referenceUnits = [];
        foreach ($processLifeCycles as $processLifeCycle) {
            if (null === ($quantitativeReference = $processLifeCycle->quantitativeReference())) {
                continue;
            }

            $unit                          = $quantitativeReference->unit();
            $referenceUnits[(string)$unit] = $unit;
        }

        if (\count($referenceUnits) < 2) {
            return new ConversionSet([]);
        }

        $existingConversions = $this->findAllConversions($processConfigId);

        $recommendedConversions = new ConversionSet();
        foreach ($referenceUnits as $fromUnitStr => $fromUnit) {
            foreach ($referenceUnits as $toUnitStr => $toUnit) {
                if (
                    $fromUnitStr === $toUnitStr ||
                    $existingConversions->has($fromUnit, $toUnit)
                ) {
                    continue;
                }

                $recommendedConversions->add(new RecommendedConversion($fromUnit, $toUnit));
            }
        }

        return $recommendedConversions;
    }

    public function isBeingUsed(Conversion $conversion): bool
    {
        if (!$conversion instanceof LinearConversion) {
            return false;
        }

        return (bool)ElcaElementComponentSet::findByProcessConversionId(
            $conversion->surrogateId(),
            array(),
            null,
            1
        )->count();
    }

    protected function findDensityConversionFor(int $processConfigId): ElcaProcessConversion
    {
        return ElcaProcessConversion::findByProcessConfigIdAndInOut(
            $processConfigId,
            Unit::CUBIC_METER,
            Unit::KILOGRAMM
        );
    }

    protected function findAvgMpuaConversionFor(int $processConfigId): ElcaProcessConversion
    {
        return ElcaProcessConversion::findByProcessConfigIdAndInOut(
            $processConfigId,
            Unit::SQUARE_METER,
            Unit::KILOGRAMM
        );
    }

}
