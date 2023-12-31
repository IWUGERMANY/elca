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

namespace Soda4Lca\Model\Import;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\File;
use Beibob\Blibs\FloatCalc;
use Beibob\Blibs\Log;
use Beibob\Blibs\NestedNode;
use DateTime;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigName;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Db\ElcaProcessConfigVariant;
use Elca\Db\ElcaProcessConfigVariantSet;
use Elca\Db\ElcaProcessIndicator;
use Elca\Db\ElcaProcessLifeCycleAssignment;
use Elca\Db\ElcaProcessLifeCycleAssignmentSet;
use Elca\Db\ElcaProcessName;
use Elca\Db\ElcaProcessScenario;
use Elca\Db\ElcaProcessScenarioSet;
use Elca\Db\ElcaProcessSet;
use Elca\Model\Common\CategoryClassId;
use Elca\Model\Common\Unit;
use Elca\Model\Process\Module;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\Conversion\ConversionType;
use Elca\Model\ProcessConfig\Conversion\FlowReference;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Service\ProcessConfig\Conversions;
use Exception;
use Ramsey\Uuid\Uuid;
use Soda4Lca\Db\Soda4LcaImport;
use Soda4Lca\Db\Soda4LcaProcess;
use Soda4Lca\Db\Soda4LcaProcessSet;

/**
 * Imports from soda4lca service
 *
 * @package   soda4lca
 * @author    Tobias Lode <tobias@beibob.de>
 * @author    Fabian Möller <fab@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class Soda4LcaImporter
{
    const KWH_TO_MJ_FACTOR = 3.6;

    /**
     * Fragments of names which need to be removed
     */
    private static $nameFragmentsToRemove = ['End of life -', 'Nutzung -'];

    /**
     * Log
     */
    private $Log;

    /**
     * Parser instance
     */
    private $Parser;

    /**
     * Db handle
     */
    private $Dbh;

    /**
     * Import
     */
    private $Import;

    /**
     * ProcessDb to import into
     */
    private $ProcessDb;

    /**
     * Maps uuid to indicators
     */
    private $indicatorUuidMap;

    /**
     * All EN 15804 lifeCycle idents
     */
    private $lifeCycleIdents;

    /**
     * Maps lifeCycle phases to idents
     */
    private $lifeCycleIdentPhaseMap;

    /**
     * Set of unassigned processes
     */
    private $unassignedProcesses = [];

    /**
     * Set of process configs created during import
     */
    private $createdProcessConfigs = [];

    /**
     * @var Conversions
     */
    private $conversions;

    private $energyEquivalent;


    /**
     * Constructor
     *
     * @param  Soda4LcaImport $Import
     *
     * @throws Soda4LcaException
     * @return Soda4LcaImporter -
     */
    public function __construct(Soda4LcaImport $Import, Conversions $conversions)
    {
        if (!$Import->isInitialized()) {
            throw new Soda4LcaException('Import not initialized');
        }

        $this->Import    = $Import;
        $this->ProcessDb = $Import->getProcessDb();
        $this->conversions = $conversions;
        $this->Log       = Log::getInstance();
        $this->Parser    = Soda4LcaParser::getInstance();
        $this->Dbh       = DbHandle::getInstance();

        /**
         * Helper maps
         */
        $this->indicatorUuidMap = ElcaIndicatorSet::find(['is_en15804_compliant' => true])->getArrayBy('id', 'uuid');

        $LifeCycleSet                 = ElcaLifeCycleSet::findEn15804Compliant(['p_order' => 'ASC']);
        $this->lifeCycleIdents        = $LifeCycleSet->getArrayBy('ident');
        $this->lifeCycleIdentPhaseMap = $LifeCycleSet->getArrayBy('phase', 'ident');

        $this->energyEquivalent = new LinearConversion(Unit::kWh(), Unit::MJ(),
            self::KWH_TO_MJ_FACTOR);
    }
    // End __construct


    /**
     * @param null $datastockUuid
     * @return array
     */
    public function checkProcessesVersions($datastockUuid = null)
    {
        $importTS         = time();
        $startIndex       = 0;
        $pageSize         = 500;
        $totalSize        = null;
        $needsUpdateCount = 0;
        $newCount         = 0;
        $entryCount       = 0;
        $update           = false;

        while (is_null($totalSize) || $startIndex < $totalSize) {
            $Processes  = $this->Parser->getProcesses($datastockUuid, $startIndex, $pageSize, $totalSize);
            $startIndex += $pageSize;

            $this->Log->notice(
                'Checking processes for new versions: '.$pageSize.' processes for '.($datastockUuid
                    ? $datastockUuid : 'default'),
                __METHOD__
            );

            foreach ($Processes as $ProcessInfoDO) {
                $entryCount++;
                $Existing = Soda4LcaProcess::findByPk($this->Import->getId(), $ProcessInfoDO->uuid);

                if (!$Existing->isInitialized()) {
                    $NewProcess = Soda4LcaProcess::create(
                        $this->Import->getId(),
                        $ProcessInfoDO->uuid,
                        $ProcessInfoDO->name,
                        $ProcessInfoDO->classId,
                        Soda4LcaProcess::STATUS_SKIPPED,
                        null,
                        'Neuer Datensatz! Importiert am: '.date(
                            t('DATETIME_FORMAT_DMY').' '.t('DATETIME_FORMAT_HI'),
                            $importTS
                        )
                    );

                    $NewProcess->setLatestVersion($ProcessInfoDO->version);
                    $NewProcess->update();

                    $newCount++;
                } else {
                    if ($Existing->getVersion() != $ProcessInfoDO->version) {

                        if ($ProcessInfoDO->version != $Existing->getLatestVersion() && $Existing->getLatestVersion(
                            ) != $ProcessInfoDO->version
                        ) {
                            $Existing->setLatestVersion($ProcessInfoDO->version);
                            $update = true;
                        }

                        if ($Existing->getStatus() != Soda4LcaProcess::STATUS_SKIPPED) {
                            $Existing->setStatus(Soda4LcaProcess::STATUS_SKIPPED);
                            $update = true;
                        }

                        if ($update) {
                            $Existing->update();
                            $update = false;
                        }
                        $needsUpdateCount++;
                    }
                }
            }
        }
        $msg = 'Processes of '.($datastockUuid ? $datastockUuid : 'default').' checked for new versions.';
        if ($newCount > 0) {
            $msg .= ' '.$newCount.' new processes found!';
        }

        if ($needsUpdateCount > 0) {
            $msg .= ' '.$needsUpdateCount.' processes ready for update!';
        }

        if ($newCount < 1 && $needsUpdateCount < 1 || $newCount > 0 && $needsUpdateCount < 1) {
            $msg .= ' '.$entryCount.' processes up to date.';
        }

        $this->Log->notice($msg, __METHOD__);

        return array('new_imported' => $newCount, 'needs_update' => $needsUpdateCount);
    }
    // End checkVersions


    /**
     * Import processes from a certain or default datastock
     *
     * @param  string $datastockUuid
     *
     * @throws Exception
     * @return int
     */
    public function importProcesses($datastockUuid = null)
    {
        /**
         * Clear list
         */
        $this->unassignedProcesses = [];

        try {
            $this->Dbh->begin();

            /**
             * Update status to import
             */
            $this->Import->setStatus(Soda4LcaImport::STATUS_IMPORT);
            $this->Import->update();

            $importId = $this->Import->getId();

            $startIndex    = 0;
            $pageSize      = 100;
            $totalSize     = null;
            $importedCount = 0;

            while (null === $totalSize || $startIndex < $totalSize) {
                $processes  = $this->Parser->getProcesses($datastockUuid, $startIndex, $pageSize, $totalSize);
                $startIndex += $pageSize;

                $this->Log->notice(
                    '[Phase 1]['.$startIndex.'] Import '.$pageSize.' processes for '.($datastockUuid
                        ? $datastockUuid : 'default'),
                    __METHOD__
                );
                foreach ($processes as $ProcessInfoDO) {
                    $ProcessDO = null;
                    try {
                        /**
                         * Retrieve and parse process information from service interface
                         */
                        $ProcessDO = $this->Parser->getProcessDataSet($ProcessInfoDO->uuid, $ProcessInfoDO->version);
                        $this->prepareProcessDO($ProcessDO);
                        list($processStatus, $processStatusDetails) = $this->importProcessDataSet($ProcessDO);

                        /**
                         * Register Process
                         */
                        Soda4LcaProcess::create(
                            $importId,
                            $ProcessDO->uuid,
                            $ProcessDO->name,
                            (string)$this->getCategoryClassId($ProcessInfoDO->classId),
                            $processStatus,
                            $ProcessDO->version,
                            $processStatusDetails,
                            join(', ', $ProcessDO->lcIdents)
                        );

                        $importedCount++;
                    } catch (Soda4LcaException $Exception) {
                        $ProcessDO       = is_object($ProcessDO) ? $ProcessDO : $ProcessInfoDO;
                        $Soda4LcaProcess = Soda4LcaProcess::create(
                            $importId,
                            $ProcessInfoDO->uuid,
                            $ProcessDO->name ?? '['.$ProcessInfoDO->uuid.']',
                            (string)$this->getCategoryClassId($ProcessDO->classId),
                            Soda4LcaProcess::STATUS_SKIPPED,
                            null,
                            $Exception->getTranslatedMessage(),
                            isset($ProcessDO->lcIdents) && is_array($ProcessDO->lcIdents) ? implode(
                                ', ',
                                $ProcessDO->lcIdents
                            ) : '',
                            $Exception->getCode()
                        );
                        $Soda4LcaProcess->setLatestVersion($ProcessDO->version);
                        $Soda4LcaProcess->update();

                        $this->Log->error(
                            'Skipped process '.$ProcessInfoDO->name.' ['.$ProcessInfoDO->uuid.']',
                            __METHOD__
                        );
                        $this->Log->error($Exception->getMessage(), __METHOD__);

                        if ($addInfo = $Exception->getAdditionalData()) {
                            $this->Log->error('Additional info: '.print_r($addInfo, true), __METHOD__);
                        }
                    }
                }

                $this->Log->debug('CURRENT MEMORY USAGE: '.File::formatFileSize(memory_get_usage()), __METHOD__);
            }

            /**
             * assign unassigned eol processes
             */
            $this->Log->notice(
                '[Phase 2] Retry for '.count($this->unassignedProcesses).' unassigned processes',
                __METHOD__
            );
            foreach ($this->unassignedProcesses as $ProcessDO) {
                $Soda4LcaProcess = Soda4LcaProcess::findByPk($importId, $ProcessDO->uuid);

                /**
                 * If assigned, clear from list
                 */
                list($processStatus, $processStatusDetails) = $this->importProcessDataSet($ProcessDO, true);

                if ($processStatus != $Soda4LcaProcess->getStatus()) {
                    $Soda4LcaProcess->setStatus($processStatus);
                    $Soda4LcaProcess->setDetails($processStatusDetails);
                    $Soda4LcaProcess->update();

                    unset($this->unassignedProcesses[$ProcessDO->uuid]);
                }
            }

            /**
             * Cleanup ProcessConfigs without assignment
             */
            $this->Log->notice('[Phase 3] Cleanup', __METHOD__);
            foreach ($this->createdProcessConfigs as $ProcessConfig) {
                if (ElcaProcessLifeCycleAssignmentSet::dbCount(['process_config_id' => $ProcessConfig->getId()])) {
                    continue;
                }

                $this->Log->notice(
                    'Deleted unused ProcessConfig '.$ProcessConfig->getName().' ['.$ProcessConfig->getId().']',
                    __METHOD__
                );
                $ProcessConfig->delete();
            }

            /**
             * Update import status
             */
            $this->Import->setStatus(Soda4LcaImport::STATUS_DONE);
            $this->Import->setDateOfImport(date('Y.m.d H:i:s'));
            $this->Import->update();

            ElcaIndicatorSet::refreshIndicatorsView();

            $this->Dbh->commit();
            $this->Log->notice('Done. '.$importedCount.' of '.$totalSize.' processes imported', __METHOD__);
        } catch (Exception $Exception) {
            $this->Dbh->rollback();

            /**
             * Reset status
             */
            $this->Import->setStatus(Soda4LcaImport::STATUS_INIT);
            $this->Import->setDateOfImport(null);
            $this->Import->update();

            throw $Exception;
        }

        return $totalSize;
    }
    // End import

    /**
     * Retry importing skipped processes
     *
     * @return void -
     */
    public function retrySkippedProcesses()
    {
        $this->Log->notice('Import skipped processes for '.($this->Import->getProcessDb()->getName()), __METHOD__);

        $reimportedProcesses = 0;

        $Processes = Soda4LcaProcessSet::findByStatus($this->Import->getId(), Soda4LcaProcess::STATUS_SKIPPED);

        /** @var $Process Soda4LcaProcess */
        foreach ($Processes as $Process) {
            try {
                $this->Dbh->begin();
                $this->Log->notice(
                    'Retry process '.$Process->getName().' ['.$Process->getUuid().']',
                    __METHOD__
                );

                $ProcessDO = $this->Parser->getProcessDataSet($Process->getUuid());
                $this->prepareProcessDO($ProcessDO);

                $update = $Process->getVersion() && $Process->getLatestVersion();
                list($processStatus, $processStatusDetails) = $this->importProcessDataSet($ProcessDO, false, $update);

                $Process->setStatus($processStatus);
                $Process->setDetails($processStatusDetails);
                $Process->setEpdModules(implode(', ', $ProcessDO->lcIdents));
                $Process->setErrorCode();

                $Process->setVersion($ProcessDO->version);
                $Process->setLatestVersion(null);

                $Process->update();

                $reimportedProcesses++;

                $this->Dbh->commit();
            } catch (Soda4LcaException $Exception) {
                $this->Dbh->rollback();

                $this->Log->error('Failed: '.$Exception->getMessage(), __METHOD__);
                $Process->setDetails($Exception->getTranslatedMessage());
                if (isset($ProcessDO)) {
                    $Process->setEpdModules(implode(', ', $ProcessDO->lcIdents));
                }
                $Process->setErrorCode($Exception->getCode());
                $Process->update();
            }
        }

        ElcaIndicatorSet::refreshIndicatorsView();

        $this->Log->notice(
            'Done. '.$reimportedProcesses.' of '.$Processes->count().' processes re-imported',
            __METHOD__
        );
    }
    // End retryUnassignedAndSkippedProcesses

    /**
     * @return void -
     */
    public function updateProcessEpdSubType()
    {
        $this->Log->notice(
            'Update EPD Subtype for processes in '.($this->Import->getProcessDb()->getName()),
            __METHOD__
        );
        $processDbId      = $this->Import->getProcessDbId();
        $updatedProcesses = 0;
        $Processes        = Soda4LcaProcessSet::findImported($this->Import->getId());


        /** @var $Process Soda4LcaProcess */
        foreach ($Processes as $Process) {
            try {
                $this->Log->notice(
                    'Update process '.$Process->getName().' ['.$Process->getUuid().']',
                    __METHOD__
                );

                $ProcessDO = $this->Parser->getProcessDataSet($Process->getUuid());
                $this->prepareProcessDO($ProcessDO);

                $this->Dbh->begin();

                foreach ($ProcessDO->epdModules as $epdModule => $scenarioIdents) {
                    $lcIdent = $ProcessDO->lcIdents[$epdModule];

                    foreach ($scenarioIdents as $scenarioIdent => $foo) {

                        $scenarioIdent = $scenarioIdent ?: null;
                        $process       = ElcaProcess::findByUuidAndProcessDbIdAndLifeCycleIdentAndScenarioIdent(
                            $ProcessDO->uuid,
                            $processDbId,
                            $lcIdent,
                            $scenarioIdent
                        );

                        if ($process->getEpdType() !== $ProcessDO->epdSubType) {
                            $process->setEpdType($ProcessDO->epdSubType);
                            $process->update();
                        }
                    }
                }

                $this->Dbh->commit();

                $updatedProcesses++;
            } catch (Soda4LcaException $Exception) {
                $this->Dbh->rollback();

                $this->Log->error('Failed: '.$Exception->getMessage(), __METHOD__);
            }
        }

        ElcaIndicatorSet::refreshIndicatorsView();

        $this->Log->notice(
            'Done. '.$updatedProcesses.' of '.$Processes->count().' epd types updated',
            __METHOD__
        );
    }

    /**
     * @return void -
     */
    public function updateProcessGeographicalRepresentativeness()
    {
        $this->Log->notice(
            'Update geographical representativeness for processes in '.($this->Import->getProcessDb()->getName()),
            __METHOD__
        );
        $updatedProcesses = 0;
        $sodaProcesses        = Soda4LcaProcessSet::findImported($this->Import->getId());

        /** @var $process Soda4LcaProcess */
        foreach ($sodaProcesses as $process) {
            try {
                $processDO = $this->Parser->getProcessDataSet($process->getUuid());

                $this->Dbh->begin();

                $updateProcesses = ElcaProcessSet::find(['uuid' => $process->getUuid()]);
                foreach ($updateProcesses as $updateProcess) {
                    if ($updateProcess->getGeographicalRepresentativeness() === $processDO->geographicalRepresentativeness) {
                        continue;
                    }

                    $updateProcess->setGeographicalRepresentativeness($processDO->geographicalRepresentativeness);
                    $updateProcess->update();

                    $this->Log->notice(
                        'Process '.$updateProcess->getName().' ['.$updateProcess->getUuid().'] updated: '. $processDO->geographicalRepresentativeness,
                        __METHOD__
                    );

                    $updatedProcesses++;
                }

                $this->Dbh->commit();
            }
            catch (Soda4LcaException $Exception) {
                $this->Log->error('Failed: '.$Exception->getMessage(), __METHOD__);
            }
        }

        $this->Log->notice(
            'Done. '.$updatedProcesses.' of '.$sodaProcesses->count().' processes updated',
            __METHOD__
        );
    }

    /**
     * Adds additional or cleaned up information
     *
     * @param  object $processDO
     *
     * @return object
     */
    private function prepareProcessDO($processDO)
    {
        /**
         * Translate epdModules to lifeCycle idents and phases
         */
        $processDO->lcIdents = $processDO->lcPhases = [];
        foreach ($processDO->epdModules as $epdModule => $scenarioIdents) {
            $lcIdent                         = $this->getLifeCycleIdentByEpdModule($epdModule);
            $lcPhase                         = $this->lifeCycleIdentPhaseMap[$lcIdent];
            $processDO->lcIdents[$epdModule] = $lcIdent;
            $processDO->lcPhases[$lcPhase]   = $this->lifeCycleIdentPhaseMap[$lcIdent];
        }

        /**
         * Prepare properties
         */
        if ($processCategoryNode = $this->getProcessCategoryNodeIdByClassId($processDO->classId)) {
            /**
             * Check if category node name has changed
             */
            if ($processCategoryNode->getName() != $processDO->className) {
                $this->Log->notice(
                    'Name of process category `'.$processDO->classId.'\' has changed from `'.$processCategoryNode->getName(
                    ).'\' to `'.$processDO->className.'\''
                );
                $processCategoryNode->setName($processDO->className);
                $processCategoryNode->update();
            }
        } else {
            try {
                $processCategoryNode = $this->createProcessCategory($processDO->classId, $processDO->className);
            }
            catch (\Exception $exception) {
                throw new Soda4LcaException("Could not create new process category", 0, $exception, $processDO);
            }

            $this->Log->notice(
                'Created new process category `' . $processDO->classId . '\' `' . $processCategoryNode->getName() . '\''
            );
        }

        $processDO->processCategoryNodeId = $processCategoryNode->getNodeId();

        $processDO->name = $this->getCleanedName($processDO->nameOrig);

        $processDO->cleanMultiLangNames = [];
        foreach ($processDO->multiLangNames as $langIdent => $multiLangName) {
            if ($langIdent) {
                $processDO->cleanMultiLangNames[$langIdent] = $this->getCleanedName($multiLangName);
            }
        }

        if (isset($processDO->refUnit)) {
            $processDO->refUnit = $this->getRefUnitByUnit($processDO->refUnit);
        }

        if ($processDO->dateOfLastRevision) {
            $DateOfLastRev                 = new DateTime($processDO->dateOfLastRevision);
            $processDO->dateOfLastRevision = $DateOfLastRev->format('Y-m-d H:i:s');
        } else {
            $processDO->dateOfLastRevision = null;
        }

        /**
         * Group scenarios
         */
        $processDO->defaultScenarios = [];
        $processDO->scenarioGroups   = [];
        foreach ($processDO->scenarios as $scenarioIdent => $ScenarioDO) {
            $processDO->scenarioGroups[$ScenarioDO->group][] = $ScenarioDO;

            if ($ScenarioDO->isDefault) {
                $processDO->defaultScenarios[$ScenarioDO->group] = $scenarioIdent;
            }
        }

        /**
         * Set a default scenario if not defined
         */
        foreach ($processDO->scenarioGroups as $group => $scenarios) {
            if (isset($processDO->defaultScenarios[$group])) {
                continue;
            }

            $ScenarioDO            = $scenarios[0];
            $ScenarioDO->isDefault = true;
        }

        /**
         * Special markers
         */
        $processDO->singlePhaseProcessPhase = count($processDO->lcPhases) == 1 ? reset($processDO->lcPhases) : false;
        $processDO->onlyEolOrRecPhase       = !isset($processDO->lcPhases[ElcaLifeCycle::PHASE_PROD]) && !isset($processDO->lcPhases[ElcaLifeCycle::PHASE_OP]);

        /**
         * Full epd contains at least phase A and (phase C or D)
         */
        $processDO->hasProdAndEolOrRec = isset($processDO->lcPhases[ElcaLifeCycle::PHASE_PROD]) && (isset($processDO->lcPhases[ElcaLifeCycle::PHASE_EOL]) || isset($processDO->lcPhases[ElcaLifeCycle::PHASE_REC]));

        return $processDO;
    }
    // End importProcessDataSet

    /**
     * Returns the lifeCycleIdent for the given epdModule name
     *
     * @param  string $epdModule
     *
     * @return string
     */
    private function getLifeCycleIdentByEpdModule($epdModule)
    {
        $parts          = explode('_', $epdModule);
        $lifeCycleIdent = $parts[0];

        if ($lifeCycleIdent === 'A1-A3') {
            $lifeCycleIdent = 'A1-3';
        }

        return $lifeCycleIdent;
    }
    // End findProcessConfigs

    /**
     * Returns the processCategoryNodeId by categoryClassId
     *
     * @param  string $categoryClassId
     *
     * @return ElcaProcessCategory
     */
    private function getProcessCategoryNodeIdByClassId(string $categoryClassIdString)
    {
        $categoryClassId = $this->getCategoryClassId($categoryClassIdString);

        $category = ElcaProcessCategory::findByRefNum((string)$categoryClassId);
        if ($category->isInitialized()) {
            return $category;
        }

        return null;
    }
    // End createProcessConfig

    /**
     * Returns a formated classId
     *
     * @param $categoryClassId
     *
     * @return string
     */
    private function getCategoryClassId($categoryClassId): CategoryClassId
    {
        return CategoryClassId::fromString($categoryClassId);
    }
    // End applyScenariosForProcessConfig

    /**
     * Returns a cleaned up process name
     *
     * @param  string $origName
     *
     * @return string
     */
    private function getCleanedName($origName)
    {
        return \trim(str_replace(self::$nameFragmentsToRemove, '', $origName));
    }
    // End importProcessDataSet

    /**
     * Returns the eLCA refUnit from the given unit string
     *
     * @param  string $unit
     *
     * @return string
     */
    private function getRefUnitByUnit($unit)
    {
        /**
         * Normalize unit names
         */
        return UnitNameMapper::unitByName($unit)->value();
    }

    /**
     * Imports a single process data set
     *
     * @param  object $processDO
     * @param bool    $isPhaseTwo
     *
     * @return int
     *@throws Soda4LcaException
     */
    private function importProcessDataSet($processDO, $isPhaseTwo = false, $update = false)
    {
        $context = $update ? 'Updating' : 'Importing';
        $this->Log->debug(
            $context.' process '.$processDO->nameOrig . ' [' . $processDO->uuid . '] [' . join(
                ', ',
                $processDO->lcIdents
            ).']',
            __METHOD__
        );

        $numAssignments = 0;

        $processStatusDetails = [];
        $processStatus        = Soda4LcaProcess::STATUS_OK;

        /**
         * Ready to start importing
         */
        try {
            $this->Dbh->begin();

            /**
             * Some checks
             */
            if (!isset($processDO->refUnit)) {
                throw new Soda4LcaException(
                    'Missing reference to referenceFlow',
                    Soda4LcaException::MISSING_REFERENCE_FLOW,
                    null,
                    $processDO
                );
            }

            if (!count($processDO->epdModules)) {
                throw new Soda4LcaException(
                    'Missing epd modules and values',
                    Soda4LcaException::MISSING_EPD_MODULES,
                    null,
                    $processDO
                );
            }

            if (!isset($processDO->processCategoryNodeId) || !$processDO->processCategoryNodeId) {
                throw new Soda4LcaException(
                    'Could not find a process category for classId '.$processDO->classId,
                    Soda4LcaException::PROCESS_CATEGORY_NOT_FOUND,
                    null,
                    $processDO
                );
            }

            /**
             * Get matching ProcessConfigs
             */
            $processConfigSet = $this->findProcessConfigs($processDO);

            /**
             * Scenarios
             *
             * Don't add scenarios to process configs when ProcessDO has no phase PRODUCTION
             */
            $scenarioIdentMap = [];
            if (count($processDO->scenarios)) {
                if (isset($processDO->lcPhases[ElcaLifeCycle::PHASE_PROD])) {
                    if ($processConfigSet->count() == 1) {
                        $processConfig    = $processConfigSet[0];
                        $scenarioIdentMap = $this->applyScenariosForProcessConfig($processDO, $processConfig);

                        if (count($scenarioIdentMap)) {
                            $scenarios = $defaultScenarios = [];
                            foreach ($scenarioIdentMap as $ident => $Scenario) {
                                $scenarios[$ident] = $Scenario->getDescription().($Scenario->isDefault() ? '*' : '');
                            }

                            $scenarioText = join(', ', $scenarios);

                            $this->Log->notice(
                                'Scenarios for `'.$processDO->name . '\' [' . $processDO->uuid . ']: ' . $scenarioText,
                                __METHOD__
                            );
                            $processStatusDetails[] = t('Gefundene Szenarien:').' '.$scenarioText;

                            if (!count($processDO->defaultScenarios)) {
                                $this->Log->warning(
                                    'No default scenarios were defined. Using the first one.',
                                    __METHOD__
                                );
                                $processStatusDetails[] = t(
                                    'Es ist kein Defaultszenario definiert. Das Erste wird verwendet.'
                                );
                            }
                        }
                    } else {
                        $this->Log->warning(
                            'Cannot apply scenarios for `'.$processDO->name . '\' [' . $processDO->uuid . '] to multiple process configs!',
                            __METHOD__
                        );
                        $processStatusDetails[] = t(
                                                      'Mehrere Baustoffkonfigurationen gefunden: %names%.',
                                                      null,
                                                      ['%names%' => join(', ', $processConfigSet->getArrayBy('name'))]
                                                  )
                                                  .t(
                                                      'Szenarios können nicht mehreren Baustoffkonfigurationen zugeordnet werden!'
                                                  );
                    }
                } else {
                    $this->Log->warning(
                        'Scenarios found for `'.$processDO->name . '\' [' . $processDO->uuid . '] on process data set with the following phases: ' . join(
                            ', ',
                            array_keys($processDO->lcPhases)
                        ),
                        __METHOD__
                    );
                    $processStatusDetails[] = t('Szenarien lassen sich nur für EPDs mit Herstellung abbilden.');
                }
            }

            /**
             * Create or update processes for each found epdModule
             */
            foreach ($processDO->epdModules as $epdModule => $scenarioIdents) {
                $lcIdent = $processDO->lcIdents[$epdModule];

                foreach ($scenarioIdents as $scenarioIdent => $indicatorUuids) {
                    $scenarioId = isset($scenarioIdentMap[$scenarioIdent]) && is_object(
                        $scenarioIdentMap[$scenarioIdent]
                    ) ? $scenarioIdentMap[$scenarioIdent]->getId() : null;
                    $Process    = ElcaProcess::findByUuidAndProcessDbIdAndLifeCycleIdentAndScenarioId(
                        $processDO->uuid,
                        $this->ProcessDb->getId(),
                        $lcIdent,
                        $scenarioId
                    );
                    if ($Process->isInitialized()) {
                        $Process->setProcessCategoryNodeId($processDO->processCategoryNodeId);
                        $Process->setName($processDO->name);
                        $Process->setNameOrig($processDO->nameOrig);
                        $Process->setRefUnit($processDO->refUnit);
                        $Process->setVersion($processDO->version);
                        $Process->setDateOfLastRevision($processDO->dateOfLastRevision);
                        $Process->setRefValue($processDO->refValue);
                        $Process->setDescription($processDO->description);
                        $Process->setEpdType(isset($processDO->epdSubType) ? $processDO->epdSubType : null);
                        $Process->setGeographicalRepresentativeness($processDO->geographicalRepresentativeness ?? null);
                        $Process->update();

                        if (!$Process->isValid()) {
                            throw new Soda4LcaException(
                                'Process '.$processDO->uuid . ' [' . $processDO->uuid . '] [' . $lcIdent . '] not valid after update',
                                Soda4LcaException::INVALID_PROCESS_AFTER_CREATE_OR_UPDATE,
                                null,
                                $Process->getValidator()->getErrors()
                            );
                        }

                        $this->Log->debug(
                            'Updated process '.$processDO->name . ' [' . $Process->getId(
                            ).'] ['.$processDO->uuid . '] [' . $lcIdent . '] in database ' . $this->ProcessDb->getName(),
                            __METHOD__
                        );

                    } else {
                        $Process = ElcaProcess::create(
                            $this->ProcessDb->getId(),
                            $processDO->processCategoryNodeId,
                            $processDO->name,
                            $processDO->nameOrig,
                            $processDO->uuid,
                            $lcIdent,
                            $processDO->refUnit,
                            $processDO->version,
                            $processDO->refValue ? $processDO->refValue : 1,
                            $scenarioId,
                            $processDO->description,
                            $processDO->dateOfLastRevision,
                            $processDO->epdSubType ?? null,
                            $processDO->geographicalRepresentativeness ?? null
                        );
                        if (!$Process->isValid()) {
                            throw new Soda4LcaException(
                                'Process '.$processDO->uuid . ' [' . $processDO->uuid . '] [' . $lcIdent . '] not valid after create',
                                Soda4LcaException::INVALID_PROCESS_AFTER_CREATE_OR_UPDATE,
                                null,
                                $Process->getValidator()->getErrors()
                            );
                        }

                        $this->Log->debug(
                            'Created new process '.$processDO->name . ' [' . $processDO->uuid . '] [' . $lcIdent . '] [' . $scenarioIdent . '] in database ' . $this->ProcessDb->getName(
                            ),
                            __METHOD__
                        );
                    }

                    $this->updateProcessNames($processDO, $Process);

                    /**
                     * create update process indicators
                     */
                    foreach ($indicatorUuids as $indicatorUuid => $amount) {
                        if (!isset($this->indicatorUuidMap[$indicatorUuid])) {
                            continue;
                        }

                        $ProcessIndicator = ElcaProcessIndicator::findByProcessIdAndIndicatorId(
                            $Process->getId(),
                            $this->indicatorUuidMap[$indicatorUuid]
                        );

                        if ($ProcessIndicator->isInitialized()) {
                            if (!is_null($amount)) {
                                $ProcessIndicator->setValue($amount);
                                $ProcessIndicator->update();
                            } else {
                                $ProcessIndicator->delete();
                            }
                        } elseif (!is_null($amount)) {
                            ElcaProcessIndicator::create(
                                $Process->getId(),
                                $this->indicatorUuidMap[$indicatorUuid],
                                $amount
                            );
                        }
                    }


                    /**
                     * Assign to processConfigs if this is a prod phase process or this is
                     * the second time coming through
                     */
                    if ((!$processDO->onlyEolOrRecPhase || $isPhaseTwo) &&
                        $processConfigSet instanceOf ElcaProcessConfigSet && $processConfigSet->count()
                    ) {

                        /**
                         * Skip modules A4, A5
                         */
                        if (in_array($lcIdent, ['A4', 'A5'])) {
                            $this->Log->debug('Skipping '.$lcIdent.' assignment', __METHOD__);
                            continue;
                        }

                        /**
                         * Skip modules A1, A2, A3 if there is also A1-3 aggregation
                         */
                        if (isset($processDO->lcIdents['A1-A3']) && in_array($lcIdent, ['A1', 'A2', 'A3'])) {
                            $this->Log->debug(
                                'Skipping '.$lcIdent.' assignment, because A1-3 exists and has precedence',
                                __METHOD__
                            );
                            continue;
                        }

                        /**
                         * Skip modules outside default scenario
                         */
                        if (isset($scenarioIdentMap[$scenarioIdent]) &&
                            $scenarioIdentMap[$scenarioIdent] instanceOf ElcaProcessScenario &&
                            !$scenarioIdentMap[$scenarioIdent]->isDefault()
                        ) {
                            $this->Log->debug(
                                'Skipping '.$lcIdent.' assignment, because it is not in the default scenario',
                                __METHOD__
                            );
                            continue;
                        }


                        /**
                         * Assign to processConfig
                         */
                        foreach ($processConfigSet as $processConfig) {

                            /**
                             * Check if this process config has already an assignment for the $lcIdent
                             * and the current processDb
                             */
                            if (ElcaProcessSet::dbCountByProcessConfigId(
                                $processConfig->getId(),
                                [
                                    'process_db_id'    => $this->ProcessDb->getId(),
                                    'life_cycle_ident' => $lcIdent,
                                ],
                                true
                            )
                            ) {
                                /**
                                 * For the update=true case this is expected,
                                 * but do not overwrite a previous made assignment!
                                 */
                                if ($update) {
                                    if (isset($scenarioIdentMap[$scenarioIdent])) {
                                        continue;
                                    }

                                    $AssignedProcess = ElcaProcessSet::findByProcessConfigId(
                                        $processConfig->getId(),
                                        [
                                            'process_db_id'    => $this->ProcessDb->getId(),
                                            'life_cycle_ident' => $lcIdent,
                                        ],
                                        [],
                                        true
                                    )->current();

                                    if ($AssignedProcess->getUuid() == $processDO->uuid) {
                                        continue;
                                    }

                                    $LifeCycleAssignment = ElcaProcessLifeCycleAssignment::findByProcessConfigIdAndProcessId(
                                        $processConfig->getId(),
                                        $AssignedProcess->getId()
                                    );

                                    if ($LifeCycleAssignment->isInitialized()) {
                                        $LifeCycleAssignment->delete();
                                        $this->Log->notice(
                                            'Removed assignment: '.$processConfig->getId().' '.$processConfig->getName(
                                            ).' => '.$AssignedProcess->getName()
                                        );
                                    }

                                } else {
                                    $this->Log->debug(
                                        'Skipping assignment to: '.$processConfig->getId().' '.$processConfig->getName(
                                        ).' Another process with lcIdent='.$lcIdent.' is already assigned',
                                        __METHOD__
                                    );
                                    continue;
                                }
                            }

                            /**
                             * In phase 2 check single phase eol processes
                             */
                            if ($isPhaseTwo && $processDO->onlyEolOrRecPhase) {

                                /**
                                 * Assign them only if also a prod process has been assigned
                                 */
                                if (!ElcaProcessSet::dbCountByProcessDbIdAndProcessConfigIdAndPhases(
                                    $this->ProcessDb->getId(),
                                    $processConfig->getId(),
                                    [ElcaLifeCycle::PHASE_PROD],
                                    true
                                )
                                ) {
                                    $this->Log->debug(
                                        'Skipping assignment to: '.$processConfig->getId().' '.$processConfig->getName(
                                        ).' due to missing PROD phase',
                                        __METHOD__
                                    );
                                    continue;
                                }
                            }

                            /**
                             *  Assign ProcessDO <=> ProcessConfig
                             */
                            $LifeCycleAssignment = ElcaProcessLifeCycleAssignment::findByProcessConfigIdAndProcessId(
                                $processConfig->getId(),
                                $Process->getId()
                            );
                            if (!$LifeCycleAssignment->isInitialized()) {
                                $LifeCycleAssignment = ElcaProcessLifeCycleAssignment::create(
                                    $processConfig->getId(),
                                    $Process->getId()
                                );
                                $this->Log->debug(
                                    'Assigned to: '.$processConfig->getId().' '.$processConfig->getName(),
                                    __METHOD__
                                );
                            }

                            $numAssignments++;

                            if ($processConfig->getName() === $Process->getName()) {
                                $stage = Module::fromValue($Process->getLifeCycleIdent())->stage();
                                if ($stage->isProduction() || $stage->isUsage()) {
                                    $this->updateProcessConfigNames($processDO, $processConfig);
                                }
                            }
                        }

                    } else {
                        /**
                         * Save unassigned processes for later
                         */
                        $this->unassignedProcesses[$processDO->uuid] = $processDO;
                        $this->Log->debug(
                            'Delaying assignments for '.$processDO->nameOrig . ' [' . $processDO->uuid . '] [' . join(
                                ', ',
                                $processDO->lcIdents
                            ).']',
                            __METHOD__
                        );
                    }
                }
            }

            /**
             * Insert or update material properties (But don't delete in case of update)
             */
            $prodProcessConfigSet = $this->findProcessConfigs($processDO, false, ElcaLifeCycle::PHASE_PROD);

            if (isset($processDO->lcPhases[ElcaLifeCycle::PHASE_PROD])) {

                if ($prodProcessConfigSet instanceOf ElcaProcessConfigSet) {

                    if ($prodProcessConfigSet->count() === 1) {
                        if ($matPropProblems = $this->applyMatPropertiesForProcessConfig($processDO,
                            $prodProcessConfigSet[0])) {
                            foreach ($matPropProblems as $matPropProblem) {
                                $processStatusDetails[] = $matPropProblem;
                            }
                        }
                    }

                    /**
                     * Insert PROD identity conversion if necessary
                     */
                    $processConfigSet->map(
                        function (ElcaProcessConfig $processConfig) use ($processDO) {
                            $this->addIdentityConversionIfNecessary($processDO, $processConfig);
                        });
                }
            }

            /**
             * Check if the process has phase operation and is a 1 kWh process
             * Then automatically add a MJ -> kWh conversion
             */
            if ($this->isMJOperationProcessWhichRequiresConversionToKWh($processDO)) {

                $this->Log->debug(
                    sprintf(
                        'Process `%s\' requires kWh to MJ conversion!',
                        $processDO->name
                    )
                );

                foreach ($processConfigSet as $processConfig) {
                    $processConfigId = new ProcessConfigId($processConfig->getId());
                    $processDbId     = new ProcessDbId($this->Import->getProcessDbId());

                    $foundConversion = $this->conversions->findEnergyEquivalentConversionFor($processConfigId,
                        $processDbId);

                    if ($foundConversion->isEmpty()) {
                        $this->conversions->registerConversion($processDbId, $processConfigId,
                            $this->energyEquivalent, null, __METHOD__);

                        $this->Log->notice(
                            sprintf(
                                'Adding kWh -> MJ conversion to `%s\': %s [in=%s,out=%s,f=%f]',
                                $processConfig->getName(),
                                $this->energyEquivalent->type(),
                                $this->energyEquivalent->fromUnit(),
                                $this->energyEquivalent->toUnit(),
                                $this->energyEquivalent->factor()
                            )
                        );
                    } else {
                        $this->Log->debug(
                            sprintf(
                                'Process `%s\' already has kWh to MJ conversion for processConfig %s!',
                                $processDO->name,
                                $processConfig->getName()
                            )
                        );
                    }
                }
            }


            /**
             * Create process config variants for all flow descendants
             */
            if (isset($processDO->lcPhases[ElcaLifeCycle::PHASE_PROD]) &&
                isset($processDO->flowDescendants) &&
                $processConfigSet instanceOf ElcaProcessConfigSet
            ) {
                $this->applyFlowDescendantsForProcessConfigs($processDO, $processConfigSet);
            }

            $this->Dbh->commit();
        } catch (Exception $Exception) {
            $this->Dbh->rollback();
            throw new Soda4LcaException($Exception->getMessage(), $Exception->getCode(), $Exception, $processDO);
        }

        if (!$numAssignments && !$update) {
            $processStatus          = Soda4LcaProcess::STATUS_UNASSIGNED;
            $processStatusDetails[] = t(
                'Es wurde keine Baustoffkonfiguration gefunden, die dem Baustoff zugeordnet werden konnte.'
            );
        }

        return [$processStatus, implode('; ', $processStatusDetails)];
    }
    // End getLifeCycleIdentByEpdModule

    /**
     * Find or create process configs for the given ProcessDO
     *
     * @param  object $ProcessDO
     *
     * @return ElcaProcessConfigSet
     */
    private function findProcessConfigs($ProcessDO, $dontCreate = false, $phase = null)
    {
        /**
         * Try to find by process uuid, then by name
         */
        $processConfigSet = ElcaProcessConfigSet::findByProcessUuid($ProcessDO->uuid, $phase);
        if (!count($processConfigSet)) {
            /**
             * Beware of some older single phase processes which have (after name cleanup) the same name
             * like newer multiphase processes!
             *
             * Also include epd type in name search. Some production processes have the same name but different
             * epd types
             */
            $processConfigSet = ElcaProcessConfigSet::findByProcessName(
                $ProcessDO->name,
                $lcPhase = (count($ProcessDO->lcPhases) > 1 ? $phase : $ProcessDO->singlePhaseProcessPhase),
                $ProcessDO->epdSubType ?? null,
                $ProcessDO->geographicalRepresentativeness ?? null
            );

            $foundBy = [];
            if (count($ProcessDO->lcPhases) <= 1) {
                $foundBy[] = 'lcPhase='.$ProcessDO->singlePhaseProcessPhase;
            }
            if (!empty($ProcessDO->epdSubType)) {
                $foundBy[] = 'epdSubType='.$ProcessDO->epdSubType;
            }

            $this->Log->notice(
                'Found '.\count(
                    $processConfigSet
                ).' process config(s) by process name, '.\implode(' and ', $foundBy).' ['
                .\implode('],[', $ProcessDO->lcIdents).']',
                __METHOD__
            );
        } else {
            $this->Log->notice('Found '.count($processConfigSet).' process config(s) by process uuid', __METHOD__);
        }

        /**
         * If empty, try to create one
         */
        if (!$dontCreate && !count($processConfigSet)) {
            /**
             * Create the config only for production and single operation processes
             */
            if (isset($ProcessDO->lcPhases[ElcaLifeCycle::PHASE_PROD]) ||
                $ProcessDO->singlePhaseProcessPhase == ElcaLifeCycle::PHASE_OP
            ) {
                $ProcessConfig = $this->createProcessConfig($ProcessDO);

                $processConfigSet = new ElcaProcessConfigSet();
                $processConfigSet->add($ProcessConfig);

                /**
                 * Save for reporting
                 */
                $this->createdProcessConfigs[] = $ProcessConfig;
            } else {
                $this->Log->error(
                    'No ProcessConfig found nor created one for '.$ProcessDO->uuid.': '.$ProcessDO->nameOrig,
                    __METHOD__
                );
            }
        }

        return $processConfigSet;
    }
    // End getRefUnitByUnit

    /**
     * Create a processConfig for the given ProcessDataSet
     *
     * @param  object ProcessDO
     *
     * @return ElcaProcessConfig
     */
    private function createProcessConfig($processDO)
    {
        $density     = isset($processDO->MatProperties->density) && $processDO->MatProperties->density
            ? $processDO->MatProperties->density : null;
        $minLifeTime = isset($processDO->MatProperties->lifeTime) && $processDO->MatProperties->lifeTime
            ? $processDO->MatProperties->lifeTime : null;

        $processConfig = ElcaProcessConfig::create(
            $processDO->name,
            $processDO->processCategoryNodeId,
            null, // description
            null, // $thermalConductivity
            null, // $thermalResistance
            true, // $isReference
            null, // $fHsHi
            $minLifeTime
        );

        $this->Log->notice('Created '.$processConfig->getName().' ['.$processConfig->getId().']', __METHOD__);

        $processDbId = new ProcessDbId($this->Import->getProcessDbId());
        $processConfigId = new ProcessConfigId($processConfig->getId());

        $flowReference = $this->provideFlowReference($processDO);

        /**
         * Set density
         */
        $this->conversions->changeProcessConfigDensity($processDbId, $processConfigId, $density, $flowReference, __METHOD__);

        /**
         * Add identity conversion
         */
        $this->conversions->registerConversion(
            $processDbId,
            $processConfigId,
            ImportedLinearConversion::forReferenceUnit(Unit::fromString($processDO->refUnit)), $flowReference,__METHOD__
        );

        return $processConfig;
    }

    /**
     * Applies the process scenarios for the given process config and returns a ident-scenarioId map
     *
     * @param  object            $ProcessDO
     * @param  ElcaProcessConfig $ProcessConfig
     *
     * @return array
     */
    private function applyScenariosForProcessConfig($ProcessDO, ElcaProcessConfig $ProcessConfig)
    {
        $scenarioIdentMap = [];
        $processConfigId  = $ProcessConfig->getId();

        $OldScenarios   = ElcaProcessScenarioSet::find(['process_config_id' => $processConfigId]);
        $oldScenarioMap = $OldScenarios->getArrayBy('id', 'ident');

        /**
         * Create scenarios
         */
        foreach ($ProcessDO->scenarios as $scenarioIdent => $ScenarioDO) {
            $Scenario = ElcaProcessScenario::findByProcessConfigIdAndIdent($ProcessConfig->getId(), $scenarioIdent);
            if ($Scenario->isInitialized()) {
                $Scenario->setGroupIdent($ScenarioDO->group);
                $Scenario->setDescription($ScenarioDO->description);
                $Scenario->setIsDefault($ScenarioDO->isDefault);
                $Scenario->update();

                unset($oldScenarioMap[$scenarioIdent]);

                $this->Log->debug(
                    'Updated scenario `'.$ScenarioDO->description.'\' ['.$Scenario->getId(
                    ).'] for process config `'.$ProcessConfig->getName().'\'',
                    __METHOD__
                );
            } else {
                $Scenario = ElcaProcessScenario::create(
                    $processConfigId,
                    $scenarioIdent,
                    $ScenarioDO->group,
                    (bool)$ScenarioDO->isDefault,
                    $ScenarioDO->description
                );
                $this->Log->debug(
                    'Created scenario `'.$ScenarioDO->description.'\' ['.$Scenario->getId(
                    ).'] for process config `'.$ProcessConfig->getName().'\'',
                    __METHOD__
                );
            }

            $scenarioIdentMap[$scenarioIdent] = $Scenario;
        }

        if (count($oldScenarioMap)) {
            $this->Log->debug(
                'Remaining scenarios found for process config `'.$ProcessConfig->getName().'\'',
                __METHOD__
            );

            foreach ($oldScenarioMap as $ident => $scenarioId) {
                if ($Scenario = $OldScenarios->search('id', $scenarioId)) {
                    $this->Log->debug(
                        'Removing stale scenario `'.$Scenario->getDescription().'\' ['.$Scenario->getId().']',
                        __METHOD__
                    );
                    $Scenario->delete();
                }
            }
        }

        return $scenarioIdentMap;
    }
    // End getCleanedName

    /**
     * Applies the given matproperties for the given ProcessConfig
     *
     * @param  ElcaProcessConfig $processConfig
     * @param  object            $processDO
     *
     * @return array
     */
    private function applyMatPropertiesForProcessConfig($processDO, ElcaProcessConfig $processConfig)
    {
        $processConfigId   = new ProcessConfigId($processConfig->getId());
        $processDbId       = new ProcessDbId($this->Import->getProcessDbId());
        $flowReference = $this->provideFlowReference($processDO);

        $problems = [];

        $hasGrossDensity = false;
        foreach ($processDO->MatProperties->conversions as $ident => $convDO) {

            $fromUnit = Unit::fromString($convDO->inUnit);
            $toUnit = Unit::fromString($convDO->outUnit);

            $processConversion = $this->conversions->findByConversion(
                $processConfigId,
                $processDbId,
                $fromUnit,
                $toUnit
            );

            if (null !== $processConversion) {
                if (!$processConversion->isImported()) {
                    $this->Log->notice(
                        sprintf(
                            'Found manually added ProcessConversion for %s: [in=%s,out=%s,f=%s] which will be overwritten by %s [in=%s,out=%s,f=%s]',
                            $processConfig->getName(),
                            $processConversion->fromUnit(),
                            $processConversion->toUnit(),
                            $processConversion->factor(),
                            $convDO->ident,
                            $convDO->inUnit,
                            $convDO->outUnit,
                            $convDO->factor
                        ),
                        __METHOD__
                    );

                    $problems[] = t(
                        'Materialeigenschaft %newIdent% [in=%newIn%,out=%newOut%,f=%newFactor%] überschreibt die bereits konfigurierte: %ident% [in=%in%,out=%out%,f=%factor%]',
                        null,
                        [
                            '%newIdent%'  => $convDO->ident,
                            '%newIn%'     => $convDO->inUnit,
                            '%newOut%'    => $convDO->outUnit,
                            '%newFactor%' => $convDO->factor,
                            '%ident%'     => $processConversion->type(),
                            '%in%'        => $processConversion->fromUnit(),
                            '%out%'       => $processConversion->toUnit(),
                            '%factor%'    => $processConversion->factor(),
                        ]
                    );
                } elseif (ConversionType::initial()->equals($processConversion->type())) {
                    $this->Log->notice(
                        sprintf(
                            'Found previously added ProcessConversion for %s: %s [in=%s,out=%s,f=%s] which will be overwritten by %s [in=%s,out=%s,f=%s]',
                            $processConfig->getName(),
                            $processConversion->type(),
                            $processConversion->fromUnit(),
                            $processConversion->toUnit(),
                            $processConversion->factor(),
                            $convDO->ident,
                            $convDO->inUnit,
                            $convDO->outUnit,
                            $convDO->factor
                        ),
                        __METHOD__
                    );

                    $problems[] = t(
                        'Materialeigenschaft %newIdent% [in=%newIn%,out=%newOut%,f=%newFactor%] überschreibt die bereits konfigurierte: %ident% [in=%in%,out=%out%,f=%factor%]',
                        null,
                        [
                            '%newIdent%'  => $convDO->ident,
                            '%newIn%'     => $convDO->inUnit,
                            '%newOut%'    => $convDO->outUnit,
                            '%newFactor%' => $convDO->factor,
                            '%ident%'     => $processConversion->type(),
                            '%in%'        => $processConversion->fromUnit(),
                            '%out%'       => $processConversion->toUnit(),
                            '%factor%'    => $processConversion->factor(),
                        ]
                    );
                } elseif (false === FloatCalc::cmp($convDO->factor, $processConversion->factor())) {
                    $this->Log->error(
                        sprintf(
                            'Skipped updating a ProcessConversion for %s: %s [in=%s,out=%s,f=%f] which conflicts with existing %s [in=%s,out=%s,f=%f]',
                            $processConfig->getName(),
                            $convDO->ident,
                            $convDO->inUnit,
                            $convDO->outUnit,
                            $convDO->factor,
                            $processConversion->type(),
                            $processConversion->fromUnit(),
                            $processConversion->toUnit(),
                            $processConversion->factor()
                        ),
                        __METHOD__
                    );

                    $problems[] = t(
                        'Materialeigenschaft %newIdent% [in=%newIn%,out=%newOut%,f=%newFactor%] wurde nicht importiert, da ein Konflikt mit einer bereits konfigurierten besteht: %ident% [in=%in%,out=%out%,f=%factor%]',
                        null,
                        [
                            '%newIdent%'  => $convDO->ident,
                            '%newIn%'     => $convDO->inUnit,
                            '%newOut%'    => $convDO->outUnit,
                            '%newFactor%' => $convDO->factor,
                            '%ident%'     => $processConversion->type(),
                            '%in%'        => $processConversion->fromUnit(),
                            '%out%'       => $processConversion->toUnit(),
                            '%factor%'    => $processConversion->factor(),
                        ]
                    );
                    continue;
                }
            }

            try {
                $importedLinearConversion = new ImportedLinearConversion($fromUnit, $toUnit, (float)($convDO->factor),
                    new ConversionType($convDO->ident));

                $this->conversions->registerConversion($processDbId, $processConfigId, $importedLinearConversion, $flowReference,__METHOD__);

                $this->Log->debug(
                    'Registered ProcessConversion `' . $convDO->ident . '\' for `' . $processConfig->getName() . '\': [in=' . $convDO->inUnit . ',out=' . $convDO->outUnit . ',f=' . $convDO->factor . ']',
                    __METHOD__
                );
            }
            catch (\Throwable $exception) {
                $this->Log->error(
                    'Failed to register ProcessConversion `' . $convDO->ident . '\' for `' . $processConfig->getName() . '\': [in=' . $convDO->inUnit . ',out=' . $convDO->outUnit . ',f=' . $convDO->factor . ']:',
                    __METHOD__
                );
                $this->Log->error($exception->getMessage(), __METHOD__);
            }

            if ($importedLinearConversion->type()->isGrossDensity()) {
                $hasGrossDensity = true;
            }
        }

        /**
         * Check if a gross density conversion was included in the given material properties. If not,
         * remove the ident of the manually added density conversion, if it exists.
         *
         * The ident field is being used to determine whether a conversion has been created manually or was imported via
         * the soda interface
         *
         * @Todo: This should be not necessary anymore since we store a FlowReference on each conversion
         *        Adapt the logic
         */
        if (false === $hasGrossDensity) {
            $densityConversion = $this->conversions->findDensityConversionFor($processDbId, $processConfigId);

            if (null !== $densityConversion && $densityConversion->isImported()) {
                $this->conversions->registerConversion($processDbId, $processConfigId,
                    new LinearConversion($densityConversion->fromUnit(), $densityConversion->toUnit(),
                        $densityConversion->factor()), $flowReference,__METHOD__);

                $this->Log->debug('Unset the ident of density process conversion `'.$densityConversion->conversionId().'\'. This conversion has been manually added.', __METHOD__);
            }
        }

        return $problems;
    }

    /**
     * Applies flow descendants for the given ProcessConfigSet
     *
     * @param  object               $ProcessDO
     * @param  ElcaProcessConfigSet $ProcessConfigSet
     *
     * @return void -
     */
    private function applyFlowDescendantsForProcessConfigs($ProcessDO, ElcaProcessConfigSet $ProcessConfigSet)
    {
        $used = [];
        foreach ($ProcessDO->flowDescendants as $DescDO) {
            foreach ($ProcessConfigSet as $ProcessConfig) {
                $PCVariant = ElcaProcessConfigVariant::findByPk($ProcessConfig->id, $DescDO->uuid);

                if ($PCVariant->isInitialized()) {
                    if ($PCVariant->getName() != $DescDO->name ||
                        $PCVariant->getRefUnit() != $DescDO->refUnit ||
                        $PCVariant->getRefValue() != $DescDO->refValue ||
                        $PCVariant->isVendorSpecific() != $DescDO->isVendorSpecific
                    ) {
                        $PCVariant->setName($DescDO->name);
                        $PCVariant->setRefValue($DescDO->refValue);
                        $PCVariant->setRefUnit($DescDO->refUnit);
                        $PCVariant->setIsVendorSpecific($DescDO->isVendorSpecific);
                        $PCVariant->update();

                        $this->Log->debug(
                            'Updated '.($DescDO->isVendorSpecific ? 'vendor specific'
                                : '').' ProcessConfigVariant for `'.$ProcessConfig->getName(
                            ).'\': `'.$DescDO->name.'\' ['.$DescDO->uuid.']',
                            __METHOD__
                        );
                    }
                } else {
                    ElcaProcessConfigVariant::create(
                        $ProcessConfig->getId(),
                        $DescDO->uuid,
                        $DescDO->name,
                        $DescDO->refUnit,
                        $DescDO->refValue,
                        $DescDO->isVendorSpecific
                    );

                    $this->Log->debug(
                        'Created new '.($DescDO->isVendorSpecific ? 'vendor specific'
                            : '').' ProcessConfigVariant for `'.$ProcessConfig->getName(
                        ).'\': `'.$DescDO->name.'\' ['.$DescDO->uuid.']',
                        __METHOD__
                    );
                }
            }

            $used[$DescDO->uuid] = true;
        }

        foreach (ElcaProcessConfigVariantSet::findByProcessConfigSet($ProcessConfigSet) as $PCVariant) {
            if (!isset($used[$PCVariant->getUuid()])) {
                $PCVariant->delete();
                $this->Log->notice(
                    'Deleted '.($PCVariant->isVendorSpecific() ? 'vendor specific'
                        : '').' ProcessConfigVariant for `'.$PCVariant->getProcessConfig()->getName(
                    ).'\': `'.$PCVariant->getName().'\' ['.$PCVariant->getUuid().']',
                    __METHOD__
                );
            }
        }

    }

    private function createProcessCategory(string $categoryClassIdString, string $categoryClassName
    ): ElcaProcessCategory {
        $categoryClassId = CategoryClassId::fromString($categoryClassIdString);

        /**
         * Check if parent exist
         */
        $parentCategory = ElcaProcessCategory::findByRefNum($categoryClassId->parent()->toString());
        if (!$parentCategory->isInitialized()) {
            throw new \Exception(
                'Parent category '.$categoryClassId->parent()->toString(
                ).' does not exist. Cannot create category '.$categoryClassId
            );
        }

        $newNode = NestedNode::createAsChildOf($parentCategory->getNode(), (string)$categoryClassId);

        return ElcaProcessCategory::create($newNode->getId(), $categoryClassName, (string)$categoryClassId);
    }

    private function updateProcessNames($processDO, $process): void
    {
        foreach ($processDO->cleanMultiLangNames as $langIdent => $multiLangName) {
            if (!$langIdent) {
                continue;
            }

            $processName = ElcaProcessName::findByProcessIdAndLang($process->getId(), $langIdent);
            if ($processName->isInitialized()) {
                if ($processName->getName() === $multiLangName) {
                    continue;
                }
                $processName->setName($multiLangName);
                $processName->update();
                $this->Log->debug(
                    'ProcessName `' . $processName->getName() . '\' [' . $processName->getLang() . '] updated',
                    __METHOD__
                );
            } else {
                $processName = ElcaProcessName::create($process->getId(), $langIdent, $multiLangName);
                $this->Log->debug(
                    'ProcessName `' . $processName->getName() . '\' [' . $processName->getLang() . '] added',
                    __METHOD__
                );
            }
        }
    }

    private function updateProcessConfigNames($processDO, ElcaProcessConfig $processConfig): void
    {
        foreach ($processDO->cleanMultiLangNames as $langIdent => $multiLangName) {
            if (!$langIdent) {
                continue;
            }

            $processConfigName = ElcaProcessConfigName::findByProcessConfigIdAndLang($processConfig->getId(), $langIdent);
            if ($processConfigName->isInitialized()) {
                if ($processConfigName->getName() === $multiLangName) {
                    continue;
                }
                $processConfigName->setName($multiLangName);
                $processConfigName->update();
                $this->Log->debug(
                    'ProcessConfigName `' . $processConfigName->getName() . '\' [' . $processConfigName->getLang() . '] updated',
                    __METHOD__
                );
            } else {
                $processConfigName = ElcaProcessConfigName::create($processConfig->getId(), $langIdent, $multiLangName);
                $this->Log->debug(
                    'ProcessConfigName `' . $processConfigName->getName() . '\' [' . $processConfigName->getLang() . '] added',
                    __METHOD__
                );
            }
        }
    }

    private function addIdentityConversionIfNecessary($processDO, ElcaProcessConfig $processConfig)
    {
        $processConfigId   = new ProcessConfigId($processConfig->getId());
        $processDbId       = new ProcessDbId($this->Import->getProcessDbId());
        $flowReference     = $this->provideFlowReference($processDO);

        $identityConversion = $this->conversions->findIdentityConversionForUnit($processConfigId, $processDbId,
            Unit::fromString($processDO->refUnit));

        if (null !== $identityConversion) {
            return;
        }

        $this->Log->debug(
            sprintf('Add identity conversion (refUnit=%s,processDbId=%s) for `%s\' (id=%s)',
                $processDO->refUnit,
                $processDbId,
                $processConfig->getName(),
                $processConfig->getId()
            ), __METHOD__);

        $this->conversions->registerConversion(
            $processDbId,
            $processConfigId,
            ImportedLinearConversion::forReferenceUnit(Unit::fromString($processDO->refUnit)), $flowReference,__METHOD__
        );
    }

    private function provideFlowReference($processDO){
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

        $this->Log->debug(
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
}
// End Soda4LcaImporter