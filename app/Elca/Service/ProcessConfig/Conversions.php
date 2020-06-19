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

use Beibob\Blibs\FloatCalc;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaProcessConfig;
use Elca\Model\Common\Optional;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Exception\InvalidArgumentException;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\ConversionSet;
use Elca\Model\ProcessConfig\Conversion\FlowReference;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\ProcessConversionsRepository;
use Elca\Model\ProcessConfig\ConversionId;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessConversion;
use Elca\Model\ProcessConfig\ProcessLifeCycleId;

/**
 * Class Conversions
 *
 * @package Elca\Service\ProcessConfig
 */
class Conversions
{
    /**
     * @var ProcessLifeCycleRepository
     */
    private $processLifeCycleRepository;

    /**
     * @var ProcessConversionsRepository
     */
    private $processConversionsRepository;

    /**
     * @var ConversionsAudit
     */
    private $conversionsAudit;

    public function __construct(
        ProcessLifeCycleRepository $processLifeCycleRepository,
        ProcessConversionsRepository $processConversionsRepository,
        ConversionsAudit $conversionsAudit
    )
    {
        $this->processLifeCycleRepository   = $processLifeCycleRepository;
        $this->processConversionsRepository = $processConversionsRepository;
        $this->conversionsAudit = $conversionsAudit;
    }

    /**
     * Takes care of inserting or updating a conversion and its associated conversion version
     *
     * If the conversion values already exists for the given processDbId it gets updated,
     * and inserted otherwise
     *
     * @param ProcessDbId        $processDbId
     * @param ProcessConfigId    $processConfigId
     * @param LinearConversion   $conversion
     * @param FlowReference|null $flowReference
     * @param null               $callee
     */
    public function registerConversion(ProcessDbId $processDbId, ProcessConfigId $processConfigId,
        LinearConversion $conversion, FlowReference $flowReference = null, $callee = null): void
    {
        $foundProcessConversion = $this->processConversionsRepository->findByConversion(
            $processConfigId, $processDbId, $conversion->fromUnit(), $conversion->toUnit()
        );

        if (null === $foundProcessConversion) {
            $conversion = $this->provideKnownConversionIfPossible($conversion, $flowReference);

            $processConversion = new ProcessConversion($processDbId, $processConfigId,
                $conversion, $flowReference);

            $this->processConversionsRepository->add($processConversion);

            $this->conversionsAudit->recordNewConversion($processConversion, $callee ?? __METHOD__);
            return;
        }

        $oldConversion = $foundProcessConversion->conversion();
        $oldFlowReference = $foundProcessConversion->flowReference();
        $foundProcessConversion->changeConversion($conversion);
        $foundProcessConversion->changeFlowReference($flowReference);

        $this->processConversionsRepository->save($foundProcessConversion);

        $this->conversionsAudit->recordUpdatedConversion($foundProcessConversion, $oldConversion, $oldFlowReference, $callee ?? __METHOD__);
    }

    public function unregisterConversion(ProcessDbId $processDbId, ConversionId $conversionId, $callee = null)
    {
        $foundProcessConversion = $this->processConversionsRepository->findById($conversionId, $processDbId);

        if (null === $foundProcessConversion) {
            return;
        }

        $this->processConversionsRepository->remove($foundProcessConversion);

        $this->conversionsAudit->recordRemovedConversion($foundProcessConversion, $callee ?? __METHOD__);
    }

    /**
     * This method keeps the density field and the density conversion in sync.
     *
     * It returns an indicator flag, if the state or value was changed
     *
     * @param ProcessDbId     $processDbId
     * @param ProcessConfigId $processConfigId
     * @param float|null      $density
     * @return bool
     */
    public function changeProcessConfigDensity(ProcessDbId $processDbId, ProcessConfigId $processConfigId,
        float $density = null, FlowReference $flowReference = null, $callee = null): bool
    {
        $densityProcessConversion = $this->findDensityConversionFor($processDbId, $processConfigId);
        $hasChanged               = null !== $densityProcessConversion
            ? !FloatCalc::cmp($densityProcessConversion->conversion()->factor(), $density)
            : true;

        if ($density === null) {
            if (null !== $densityProcessConversion) {
                $this->processConversionsRepository->remove($densityProcessConversion);
                $this->conversionsAudit->recordRemovedConversion($densityProcessConversion, $callee ?? __METHOD__);
            }

            return $hasChanged;
        }

        if (null !== $densityProcessConversion) {
            if ($hasChanged) {
                $linearConversion = $densityProcessConversion->conversion();
                $densityProcessConversion->changeConversion(new LinearConversion($linearConversion->fromUnit(),
                $linearConversion->toUnit(), $density));
                $oldFlowReference = $densityProcessConversion->flowReference();
                $densityProcessConversion->changeFlowReference($flowReference);

                $this->processConversionsRepository->save($densityProcessConversion);
                $this->conversionsAudit->recordUpdatedConversion($densityProcessConversion, $linearConversion,
                    $oldFlowReference, $callee ?? __METHOD__);
            }

            return $hasChanged;
        }

        $densityProcessConversion = new ProcessConversion($processDbId, $processConfigId,
            new LinearConversion(Unit::m3(), Unit::kg(), $density), $flowReference);
        $this->processConversionsRepository->add($densityProcessConversion);
        $this->conversionsAudit->recordNewConversion($densityProcessConversion, $callee ?? __METHOD__);

        return $hasChanged;
    }

    public function changeProcessConfigDefaultSize(ElcaProcessConfig $processConfig, float $defaultSize = null): bool
    {
        $hasChanged = $defaultSize !== $processConfig->getDefaultSize();

        $processConfig->setDefaultSize($defaultSize);

        return $hasChanged;
    }

    public function computeDensityFromMpua(ProcessDbId $processDbId, ProcessConfigId $processConfigId,
        ?float $defaultSize): ?float
    {
        if (null === $defaultSize) {
            return null;
        }

        $avgMpuaConversion = $this->findAvgMpuaConversionFor($processDbId, $processConfigId);
        if (null === $avgMpuaConversion) {
            return null;
        }

        return $avgMpuaConversion->conversion()->factor() / $defaultSize;
    }

    public function computeDefaultSizeFromDensity(ProcessDbId $processDbId, ProcessConfigId $processConfigId,
        ?float $density): ?float
    {
        if (null === $density) {
            return null;
        }

        $avgMpuaConversion = $this->findAvgMpuaConversionFor($processDbId, $processConfigId);
        if (null === $avgMpuaConversion) {
            return null;
        }

        return $avgMpuaConversion->conversion()->factor() / $density;
    }

    public function findAllConversions(ProcessLifeCycleId $processLifeCycleId): ConversionSet
    {
        $processLifeCycle = $this->processLifeCycleRepository->findById($processLifeCycleId);

        return new ConversionSet($processLifeCycle->conversions());
    }

    public function findRequiredConversions(ProcessLifeCycleId $processLifeCycleId): ConversionSet
    {
        $processLifeCycle = $this->processLifeCycleRepository
            ->findById($processLifeCycleId);

        $conversions = $processLifeCycle->requiredConversions();

        return new ConversionSet(\array_unique($conversions, SORT_REGULAR));
    }

    public function findAdditionalConversions(ProcessLifeCycleId $processLifeCycleId): ConversionSet
    {
        $requiredConversions = $this->findRequiredConversions($processLifeCycleId)->toArray();

        $processLifeCycle = $this->processLifeCycleRepository->findById($processLifeCycleId);

        $conversions = \array_diff($processLifeCycle->additionalConversions(), $requiredConversions);

        return new ConversionSet(\array_unique($conversions, SORT_REGULAR));
    }

    public function findProductionConversions(ProcessLifeCycleId $processLifeCycleId): ConversionSet
    {
        $processLifeCycle = $this->processLifeCycleRepository->findById($processLifeCycleId);
        $requiredUnits    = $processLifeCycle->requiredUnits();

        $conversions = [];
        foreach ($processLifeCycle->conversions() as $conversion) {
            foreach ($requiredUnits as $unit) {
                if ($unit->equals($conversion->toUnit()) || $unit->equals($conversion->fromUnit())) {
                    $conversions[] = $conversion;
                }
            }
        }

        return ConversionSet::fromArray($conversions);
    }

    public function findProductionConversionsForMultipleDbs(ProcessConfigId $processConfigId,
        ProcessDbId ...$processDbIds): ConversionSet
    {
        $processConversions = $this->processConversionsRepository->findIntersectConversionsForMultipleProcessDbs(
            $processConfigId, ...$processDbIds);

        $conversionSet = new ConversionSet();
        foreach ($processConversions as $processConversion) {
            $conversionSet->add($processConversion->conversion());
        }

        return $conversionSet;
    }

    public function findQuantitativeReference(ProcessLifeCycleId $processLifeCycleId): Quantity
    {
        $processLifeCycle = $this->processLifeCycleRepository->findById($processLifeCycleId);

        if (!$quantity = $processLifeCycle->quantitativeReference()) {
            throw new InvalidArgumentException("Quantitative reference not found for :processLifeCycleId:", [
                ':processLifeCycleId:' => $processLifeCycleId,
            ]);
        }

        return $quantity;
    }

    public function findRequiredUnits(ProcessLifeCycleId $processLifeCycleId): array
    {
        $processLifeCycle = $this->processLifeCycleRepository->findById($processLifeCycleId);

        return $processLifeCycle->requiredUnits();
    }

    public function findAvailableUnits(ProcessLifeCycleId $processLifeCycleId): array
    {
        $processLifeCycle = $this->processLifeCycleRepository->findById($processLifeCycleId);

        return $processLifeCycle->units();
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

    public function findDensityConversionFor(ProcessDbId $processDbId,
        ProcessConfigId $processConfigId): ?ProcessConversion
    {
        return $this->processConversionsRepository->findByConversion($processConfigId, $processDbId, Unit::m3(),
            Unit::kg());
    }

    public function findByConversion(ProcessConfigId $processConfigId, ProcessDbId $processDbId, Unit $fromUnit,
        Unit $toUnit): ?ProcessConversion
    {
        return $this->processConversionsRepository->findByConversion($processConfigId, $processDbId, $fromUnit,
            $toUnit);
    }

    public function findConversionForRefUnit(ProcessConfigId $processConfigId, ProcessDbId $processDbId,
        Unit $refUnit): ?ProcessConversion
    {
        return $this->processConversionsRepository->findByConversion($processConfigId, $processDbId, $refUnit,
            $refUnit);
    }

    public function findConversion(ConversionId $conversionId, ProcessDbId $processDbId): ?ProcessConversion
    {
        return $this->processConversionsRepository->findById($conversionId, $processDbId);
    }

    public function findIdentityConversionForUnit(ProcessConfigId $processConfigId, ProcessDbId $processDbId,
        Unit $unit)
    {
        return $this->processConversionsRepository->findIdentityConversionForReferenceUnit($processConfigId,
            $processDbId, $unit);
    }

    public function removeIdentityConversionForUnit(ProcessConfigId $processConfigId, ProcessDbId $processDbId,
        Unit $unit)
    {
        $processConversion = $this->processConversionsRepository->findIdentityConversionForReferenceUnit($processConfigId,
            $processDbId, $unit);

        if (null === $processConversion) {
            return;
        }

        $this->processConversionsRepository->remove($processConversion);
    }

    public function findEnergyEquivalentConversionFor(ProcessConfigId $processConfigId, ProcessDbId $processDbId) : Optional
    {
        return Optional::ofNullable($this->processConversionsRepository->findByConversion($processConfigId,
            $processDbId, Unit::kWh(), Unit::MJ()));
    }

    protected function findAvgMpuaConversionFor(ProcessDbId $processDbId,
        ProcessConfigId $processConfigId): ?ProcessConversion
    {
        return $this->processConversionsRepository->findByConversion($processConfigId, $processDbId, Unit::m2(),
            Unit::kg());
    }

    /**
     * Invert a conversion if it is known this way, keep imported conversions as-is
     */
    protected function provideKnownConversionIfPossible(LinearConversion $conversion, ?FlowReference $flowReference)
    {
        if ($this->isImported($conversion, $flowReference) || $conversion->isKnown()) {
            return $conversion;
        }

        $invertedConversion = $conversion->invert();

        if ($invertedConversion->isKnown()) {
            return $invertedConversion;
        }

        return $conversion;
    }

    protected function isImported(LinearConversion $conversion, ?FlowReference $flowReference): bool
    {
        return $conversion instanceof ImportedLinearConversion || null !== $flowReference;
    }

}
