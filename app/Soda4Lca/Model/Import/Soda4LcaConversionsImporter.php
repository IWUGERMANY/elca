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

    public function __construct(Log $log, DbHandle $dbh, Conversions $conversions)
    {
        $this->log = $log;
        $this->dbh = $dbh;
        $this->parser = Soda4LcaParser::getInstance();
        $this->conversions = $conversions;
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

                $this->dbh->begin();

                $updateProcesses = ElcaProcessSet::findExtended([
                    'uuid'          => $process->getUuid(),
                    'version'       => $process->getVersion(),
                    'process_db_id' => $import->getProcessDbId(),
                    'life_cycle_phase' => ElcaLifeCycle::PHASE_PROD
                ]);
                /**
                 * @var ElcaProcess $updateProcess
                 */
                foreach ($updateProcesses as $updateProcess) {

                    $processConfigs = ElcaProcessConfigSet::findByProcessId($updateProcess->getId());

                    foreach ($processConfigs as $processConfig) {
                        $updatedProcessConfigs += (int)$this->updateConversions($processConfig, $processDbId, $processDO,
                            $updateProcess);
                    }
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

            $importConversion = new LinearConversion($inUnit, $outUnit, (float)$convDO->factor);

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
}