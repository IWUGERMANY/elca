<?php


namespace Soda4Lca\Model\Import;


use Beibob\Blibs\DbHandle;
use Beibob\Blibs\FloatCalc;
use Beibob\Blibs\Log;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Db\ElcaProcessSet;
use Elca\Model\Common\Unit;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\Conversion\FlowReference;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessLifeCycleId;
use Elca\Service\ProcessConfig\Conversions;
use Ramsey\Uuid\Uuid;
use Soda4Lca\Db\Soda4LcaImport;
use Soda4Lca\Db\Soda4LcaProcess;
use Soda4Lca\Db\Soda4LcaProcessSet;

class Soda4LcaConversionsImporter
{
    const KWH_TO_MJ_FACTOR = 3.6;

    /**
     * @var Log
     */
    private $log;

    /**
     * @var DbHandle
     */
    private $dbh;

    /**
     * @var Soda4LcaParser
     */
    private $parser;

    /**
     * @var Conversions
     */
    private $conversions;

    private $energyEquivalent;

    public function __construct(Log $log, DbHandle $dbh, Conversions $conversions)
    {
        $this->log = $log;
        $this->dbh = $dbh;
        $this->parser = Soda4LcaParser::getInstance();
        $this->conversions = $conversions;

        $this->energyEquivalent = new LinearConversion(Unit::kWh(), Unit::MJ(),
            self::KWH_TO_MJ_FACTOR);
    }

    public function import(Soda4LcaImport $import)
    {
        $this->log->notice(
            'Update conversions for '.($import->getProcessDb()->getName()),
            __METHOD__
        );

        $updatedProcessConfigs = 0;
        $sodaProcesses        = Soda4LcaProcessSet::findImported($import->getId());
        $processDbId = new ProcessDbId($import->getProcessDbId());

        /** @var $process Soda4LcaProcess */
        foreach ($sodaProcesses as $process) {
            try {
                $processDO = $this->parser->getProcessDataSet($process->getUuid(), $process->getVersion());
                $this->log->debug(
                    sprintf("Processing %s `%s' %f %s [uuid=%s/%s] [lcIdents=%s]",
                    $processDO->classId,
                    $processDO->nameOrig,
                    $processDO->refValue,
                    $processDO->refUnit,
                    $processDO->uuid,
                    $processDO->version,
                    implode(',', array_keys($processDO->epdModules))
                    )
                );

                $this->dbh->begin();

                $updateProdProcesses = ElcaProcessSet::findExtended([
                    'uuid'          => $process->getUuid(),
                    'version'       => $process->getVersion(),
                    'process_db_id' => $import->getProcessDbId(),
                    'life_cycle_phase' => ElcaLifeCycle::PHASE_PROD
                ]);

                $this->log->debug(sprintf('Checking %d production processes', $updateProdProcesses->count()));

                if ($updateProdProcesses->count()) {
                    /**
                     * @var ElcaProcess $updateProdProcess
                     */
                    foreach ($updateProdProcesses as $updateProdProcess) {

                        $processConfigs = ElcaProcessConfigSet::findByProcessId($updateProdProcess->getId());

                        foreach ($processConfigs as $processConfig) {
                            $updatedProcessConfigs += (int)$this->updateConversions($processConfig, $processDbId,
                                $processDO,
                                $updateProdProcess);
                        }
                    }

                    $this->log->debug('Checking production processes DONE');
                }

                $updateOpProcesses = ElcaProcessSet::findExtended([
                    'uuid'          => $process->getUuid(),
                    'version'       => $process->getVersion(),
                    'process_db_id' => $import->getProcessDbId(),
                    'life_cycle_phase' => ElcaLifeCycle::PHASE_OP
                ]);

                $this->log->debug(sprintf('Checking %d operation processes', $updateOpProcesses->count()));

                if ($updateOpProcesses->count()) {
                    /**
                     * @var ElcaProcess $updateOpProcess
                     */
                    foreach ($updateOpProcesses as $updateOpProcess) {

                        $processConfigs = ElcaProcessConfigSet::findByProcessId($updateOpProcess->getId());

                        foreach ($processConfigs as $processConfig) {
                            $this->checkIfProcessRequiresKWhToMJConversion($import, $processDO, $processConfig);
                        }
                    }

                    $this->log->debug('Checking operation processes DONE');
                }

                $this->dbh->commit();
            }
            catch (Soda4LcaException $Exception) {
                $this->log->error('Failed: '.$Exception->getMessage(), __METHOD__);
            }
        }

        $this->log->notice(
            'Done. '.$updatedProcessConfigs.' of '.$sodaProcesses->count().' process configs updated',
            __METHOD__
        );
    }

    private function updateConversions(ElcaProcessConfig $processConfig, ProcessDbId $processDbId, \stdClass $processDO,
        ElcaProcess $updateProcess): bool
    {
        $modified = false;
        $processConfigId  = new ProcessConfigId($processConfig->getId());
        $flowReference = $this->provideFlowReference($processDO);

        $foundConversions = $this->conversions->findAllConversions(new ProcessLifeCycleId($processDbId,
            $processConfigId));

        $conversionIndex = $foundConversions->mappedIndex();

        foreach ($processDO->MatProperties->conversions as $ident => $convDO) {
            $inUnit  = Unit::fromString($convDO->inUnit);
            $outUnit = Unit::fromString($convDO->outUnit);

            if ('' === $convDO->factor || !is_numeric($convDO->factor)) {
                $this->log->error(sprintf('%s [%s/%s]: %s : Invalid factor for conversion [%s -> %s] : `%s\'. Skipping.',
                    $updateProcess->getName(),
                    $updateProcess->getUuid(),
                    $updateProcess->getVersion(),
                    $flowReference,
                    $inUnit,
                    $outUnit,
                    $convDO->factor
                ), __METHOD__);
                continue;
            }

            $importConversion = new ImportedLinearConversion($inUnit, $outUnit, (float)$convDO->factor, null);

            $foundConversion = $foundConversions->find($inUnit, $outUnit);
            if ($foundConversion->isPresent()) {

                /**
                 * @var LinearConversion $conversion
                 */
                $conversion = $foundConversion->get();

                if ($conversion->fromUnit()->equals($importConversion->fromUnit())) {
                    unset($conversionIndex[$inUnit->value()][$outUnit->value()]);

                    if (!FloatCalc::cmp($conversion->factor(), $importConversion->factor())) {
                        $this->log->notice(
                            sprintf('%s [%s/%s]: %s : Will update existing conversion [%s -> %s] : %f changed to %f',

                                $updateProcess->getName(),
                                $updateProcess->getUuid(),
                                $updateProcess->getVersion(),
                                $flowReference,
                                $conversion->fromUnit(),
                                $conversion->toUnit(),
                                $conversion->factor(),
                                $importConversion->factor()
                            ), __METHOD__
                        );
                    } else {
                        $this->log->debug(
                            sprintf('%s [%s/%s]: %s : Nothing changed for conversion [%s -> %s] : %f',

                                $updateProcess->getName(),
                                $updateProcess->getUuid(),
                                $updateProcess->getVersion(),
                                $flowReference,
                                $conversion->fromUnit(),
                                $conversion->toUnit(),
                                $conversion->factor()
                            ), __METHOD__
                        );

                        continue;
                    }
                } else {
                    unset($conversionIndex[$outUnit->value()][$inUnit->value()]);

                    $this->log->warning(
                        sprintf('%s [%s/%s]: %s: Found inversion of existing conversion [%s -> %s] : %f // [%s -> %s] : %f',

                            $updateProcess->getName(),
                            $updateProcess->getUuid(),
                            $updateProcess->getVersion(),
                            $flowReference,
                            $conversion->fromUnit(),
                            $conversion->toUnit(),
                            $conversion->factor(),
                            $importConversion->fromUnit(),
                            $importConversion->toUnit(),
                            $importConversion->factor()
                        ), __METHOD__
                    );
                }
            } else {
                $this->log->notice(
                    sprintf('%s [%s/%s]: %s : Will import new conversion [%s -> %s] : %f',

                        $updateProcess->getName(),
                        $updateProcess->getUuid(),
                        $updateProcess->getVersion(),
                        $flowReference,
                        $importConversion->fromUnit(),
                        $importConversion->toUnit(),
                        $importConversion->factor()
                    ), __METHOD__
                );
            }

            $this->conversions->registerConversion($processDbId, $processConfigId, $importConversion, $flowReference,__METHOD__);
            $modified = true;
        }

//        /**
//         * @var Conversion $unseenConversion
//         */
//        foreach ($conversionIndex as $inUnit => $conversions) {
//            foreach ($conversions as $outUnit => $unseenConversion) {
//                if ($unseenConversion->isIdentity() || !$unseenConversion->type()->isKnown()) {
//                    continue;
//                }
//
//                $this->log->notice(
//                    sprintf('%s [%s/%s]: Will remove stale conversion [%s -> %s] : %f',
//
//                        $updateProcess->getName(),
//                        $updateProcess->getUuid(),
//                        $updateProcess->getVersion(),
//                        $unseenConversion->fromUnit(),
//                        $unseenConversion->toUnit(),
//                        $unseenConversion->factor()
//                    ), __METHOD__
//                );
//                $modified = true;
//            }
//        }

        return $modified;
    }

    private function provideFlowReference($processDO)
    {
        $flowReference = null;
        if ($processDO->MatProperties->flowUuid && Uuid::isValid($processDO->MatProperties->flowUuid)) {
            $flowReference = FlowReference::from($processDO->MatProperties->flowUuid,
                $processDO->MatProperties->flowVersion);
        }

        return $flowReference;
    }

    private function isMJOperationProcessWhichRequiresConversionToKWh($processDO): bool
    {
        if (!isset($processDO->refUnit, $processDO->refValue)) {
            return false;
        }

        $this->log->debug(
            sprintf(
                'Check if process `%s\' (%f %s) requires kWh to MJ conversion',
                $processDO->nameOrig,
                $processDO->refValue,
                $processDO->refUnit
            )
        );

        return isset($processDO->epdModules[ElcaLifeCycle::IDENT_B6]) &&
               $processDO->refUnit === Unit::MEGAJOULE &&
               FloatCalc::cmp($processDO->refValue, self::KWH_TO_MJ_FACTOR, 0.1);
    }

    private function checkIfProcessRequiresKWhToMJConversion(Soda4LcaImport $import, $processDO, ElcaProcessConfig $processConfig)
    {
        if (!$this->isMJOperationProcessWhichRequiresConversionToKWh($processDO)) {
            return;
        }

        $this->log->debug(
            sprintf(
                'Process `%s\' requires kWh to MJ conversion!',
                $processDO->nameOrig
            )
        );

        $processConfigId   = new ProcessConfigId($processConfig->getId());
        $processDbId       = new ProcessDbId($import->getProcessDbId());

        $foundConversion = $this->conversions->findEnergyEquivalentConversionFor($processConfigId, $processDbId);

        if ($foundConversion->isPresent()) {
            $this->log->debug(
                sprintf(
                    'Process `%s\' already has kWh to MJ conversion!',
                    $processDO->nameOrig
                )
            );
            return;
        }

        $this->conversions->registerConversion($processDbId, $processConfigId,
            $this->energyEquivalent, null,__METHOD__);

        $this->log->notice(
            sprintf(
                'Adding kWh to MJ conversion to `%s\': %s [in=%s,out=%s,f=%f]',
                $processConfig->getName(),
                $this->energyEquivalent->type(),
                $this->energyEquivalent->fromUnit(),
                $this->energyEquivalent->toUnit(),
                $this->energyEquivalent->factor()
            )
        );

    }
}