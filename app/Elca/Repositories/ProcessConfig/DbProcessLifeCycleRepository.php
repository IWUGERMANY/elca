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

namespace Elca\Repositories\ProcessConfig;

use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessConversionVersion;
use Elca\Db\ElcaProcessConversionVersionSet;
use Elca\Db\ElcaProcessIndicator;
use Elca\Db\ElcaProcessIndicatorSet;
use Elca\Db\ElcaProcessSet;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Process\ProcessId;
use Elca\Model\Process\ProcessName;
use Elca\Model\Process\Scenario;
use Elca\Model\Process\Stage;
use Elca\Model\ProcessConfig\Conversion\ConversionType;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessLifeCycleId;
use Utils\Model\FactoryHelper;

class DbProcessLifeCycleRepository implements ProcessLifeCycleRepository
{
    public function findById(ProcessLifeCycleId $processLifeCycleId): ProcessLifeCycle
    {
        $processSet         = ElcaProcessSet::findByProcessConfigId(
            $processLifeCycleId->processConfigId()->value(),
            ['process_db_id' => $processLifeCycleId->processDbId()->value()]
        );

        return $this->build($processLifeCycleId->processConfigId(), $processLifeCycleId->processDbId(), $processSet);
    }

    public function findByIdAndStage(
        ProcessConfigId $processConfigId,
        ProcessDbId $processDbId,
        Stage $stage
    ): ProcessLifeCycle {

        $processSet         = ElcaProcessSet::findByProcessConfigId(
            $processConfigId->value(),
            ['process_db_id' => $processDbId->value(), 'life_cycle_phase' => $stage->value()]
        );

        return $this->build($processConfigId, $processDbId, $processSet);
    }

    public function findAllByProcessConfigId(ProcessConfigId $processConfigId): array
    {
        $groupedProcesses = [];
        foreach (ElcaProcessSet::findByProcessConfigId($processConfigId->value()) as $process) {
            $processDbId = $process->getProcessDbId();
            if (!isset($groupedProcesses[$processDbId])) {
                $groupedProcesses[$processDbId] = new ElcaProcessSet();
            }

            $groupedProcesses[$processDbId]->add($process);
        }


        $result = [];
        foreach ($groupedProcesses as $processDbId => $processSet) {
            $result[] = $this->build(
                $processConfigId,
                new ProcessDbId($processDbId),
                $processSet
            );
        }

        return $result;
    }

    private function build(ProcessConfigId $processConfigId, ProcessDbId $processDbId, ElcaProcessSet $processSet
    ) {
        $indicatorValuesSet = ElcaProcessIndicatorSet::findByProcessIds(
            $processSet->getArrayBy()
        );

        $processes = $conversions = [];

        /**
         * @var ElcaProcess $dbProcess
         */
        foreach ($processSet as $dbProcess) {
            $processes[$dbProcess->getId()] = $this->buildProcess($indicatorValuesSet, $dbProcess);
        }

        /**
         * @var ElcaProcessConversionVersion[] $processConversionVersions
         */
        $processConversionVersions = ElcaProcessConversionVersionSet::findExtendedByProcessConfigIdAndProcessDbId(
            $processConfigId->value(), $processDbId->value(), ['id' => 'ASC']
        );

        foreach ($processConversionVersions as $dbProcessConversionVersion) {
            $fromUnit = new Unit($dbProcessConversionVersion->getInUnit());
            $toUnit   = new Unit($dbProcessConversionVersion->getOutUnit());

            $factor = $dbProcessConversionVersion->getFactor();
            $ident = $dbProcessConversionVersion->getIdent();

            $conversion = $ident
                ? new ImportedLinearConversion($fromUnit, $toUnit, $factor, new ConversionType($ident))
                : new LinearConversion($fromUnit, $toUnit, $factor);

            $conversion->setSurrogateId($dbProcessConversionVersion->getConversionId());

            $conversions[] = $conversion;
        }

        return FactoryHelper::createInstanceWithoutConstructor(
            ProcessLifeCycle::class,
            [
                'processConfigId' => $processConfigId,
                'processDbId'     => $processDbId,
                'processes'       => $processes,
                'conversions'     => $conversions,
            ]
        );
    }

    private function buildProcess(
        ElcaProcessIndicatorSet $indicatorValuesSet,
        ElcaProcess $dbProcess
    ): Process {
        $dbProcessId = $dbProcess->getId();

        $indicatorValues = $indicatorValuesSet
            ->filter(
                function (ElcaProcessIndicator $processIndicator) use ($dbProcessId) {
                    return $processIndicator->getProcessId() === $dbProcessId;
                }
            )->map(
                function (ElcaProcessIndicator $processIndicator) {
                    return new IndicatorValue(
                        new IndicatorIdent($processIndicator->getIndicatorIdent()),
                        (float)$processIndicator->getValue()
                    );
                }
            );

        $dbScenario = null !== $dbProcess->getScenarioId()
            ? $dbProcess->getScenario()
            : null;

        $scenario = null;
        if (null !== $dbScenario) {
            $scenario = new Scenario(
                $dbScenario->getIdent(),
                $dbScenario->isDefault(),
                $dbScenario->getDescription(),
                $dbScenario->getGroupIdent()
            );

            $scenario->setSurrogateId($dbScenario->getId());
        }

        $process = new Process(
            new ProcessId(
                $dbProcessId,
                $dbProcess->getUuid()
            ),
            new Module($dbProcess->getLifeCycleIdent()),
            new Quantity($dbProcess->getRefValue(), new Unit($dbProcess->getRefUnit())),
            new ProcessName($dbProcess->getName(), $dbProcess->getNameOrig()),
            (float)$dbProcess->getRatio(),
            $indicatorValues
        );

        return $process;
    }
}
