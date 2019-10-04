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
use Elca\Db\ElcaProcessConversion;
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
use Elca\Model\ProcessConfig\Conversion\ConversionType;
use Exception;
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
     * Constructor
     *
     * @param  Soda4LcaImport $Import
     *
     * @throws Soda4LcaException
     * @return Soda4LcaImporter -
     */
    public function __construct(Soda4LcaImport $Import)
    {
        if (!$Import->isInitialized()) {
            throw new Soda4LcaException('Import not initialized');
        }

        $this->Import    = $Import;
        $this->ProcessDb = $Import->getProcessDb();
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
                $Processes  = $this->Parser->getProcesses($datastockUuid, $startIndex, $pageSize, $totalSize);
                $startIndex += $pageSize;

                $this->Log->notice(
                    '[Phase 1]['.$startIndex.'] Import '.$pageSize.' processes for '.($datastockUuid
                        ? $datastockUuid : 'default'),
                    __METHOD__
                );
                foreach ($Processes as $ProcessInfoDO) {
                    $ProcessDO = null;
                    try {
                        /**
                         * Retrieve and parse process information from service interface
                         */
                        $ProcessDO = $this->Parser->getProcessDataSet($ProcessInfoDO->uuid);
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
                            $ProcessDO->name,
                            (string)$this->getCategoryClassId($ProcessDO->classId),
                            Soda4LcaProcess::STATUS_SKIPPED,
                            null,
                            $Exception->getTranslatedMessage(),
                            isset($ProcessDO->lcIdents) && is_array($ProcessDO->lcIdents) ? implode(
                                ', ',
                                is_array($ProcessDO->lcIdents)
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
            $processCategoryNode = $this->createProcessCategory($processDO->classId, $processDO->className);
            $this->Log->notice(
                'Created new process category `'.$processDO->classId.'\' `'.$processCategoryNode->getName().'\''
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
     * @param  object $ProcessDO
     * @param bool    $isPhaseTwo
     *
     * @throws Soda4LcaException
     * @return int
     */
    private function importProcessDataSet($ProcessDO, $isPhaseTwo = false, $update = false)
    {
        $context = $update ? 'Updating' : 'Importing';
        $this->Log->debug(
            $context.' process '.$ProcessDO->nameOrig.' ['.$ProcessDO->uuid.'] ['.join(
                ', ',
                $ProcessDO->lcIdents
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
            if (!isset($ProcessDO->refUnit)) {
                throw new Soda4LcaException(
                    'Missing reference to referenceFlow',
                    Soda4LcaException::MISSING_REFERENCE_FLOW,
                    null,
                    $ProcessDO
                );
            }

            if (!count($ProcessDO->epdModules)) {
                throw new Soda4LcaException(
                    'Missing epd modules and values',
                    Soda4LcaException::MISSING_EPD_MODULES,
                    null,
                    $ProcessDO
                );
            }

            if (!isset($ProcessDO->processCategoryNodeId) || !$ProcessDO->processCategoryNodeId) {
                throw new Soda4LcaException(
                    'Could not find a process category for classId '.$ProcessDO->classId,
                    Soda4LcaException::PROCESS_CATEGORY_NOT_FOUND,
                    null,
                    $ProcessDO
                );
            }

            /**
             * Get matching ProcessConfigs
             */
            $ProcessConfigSet = $this->findProcessConfigs($ProcessDO);

            /**
             * Scenarios
             *
             * Don't add scenarios to process configs when ProcessDO has no phase PRODUCTION
             */
            $scenarioIdentMap = [];
            if (count($ProcessDO->scenarios)) {
                if (isset($ProcessDO->lcPhases[ElcaLifeCycle::PHASE_PROD])) {
                    if ($ProcessConfigSet->count() == 1) {
                        $ProcessConfig    = $ProcessConfigSet[0];
                        $scenarioIdentMap = $this->applyScenariosForProcessConfig($ProcessDO, $ProcessConfig);

                        if (count($scenarioIdentMap)) {
                            $scenarios = $defaultScenarios = [];
                            foreach ($scenarioIdentMap as $ident => $Scenario) {
                                $scenarios[$ident] = $Scenario->getDescription().($Scenario->isDefault() ? '*' : '');
                            }

                            $scenarioText = join(', ', $scenarios);

                            $this->Log->notice(
                                'Scenarios for `'.$ProcessDO->name.'\' ['.$ProcessDO->uuid.']: '.$scenarioText,
                                __METHOD__
                            );
                            $processStatusDetails[] = t('Gefundene Szenarien:').' '.$scenarioText;

                            if (!count($ProcessDO->defaultScenarios)) {
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
                            'Cannot apply scenarios for `'.$ProcessDO->name.'\' ['.$ProcessDO->uuid.'] to multiple process configs!',
                            __METHOD__
                        );
                        $processStatusDetails[] = t(
                                                      'Mehrere Baustoffkonfigurationen gefunden: %names%.',
                                                      null,
                                                      ['%names%' => join(', ', $ProcessConfigSet->getArrayBy('name'))]
                                                  )
                                                  .t(
                                                      'Szenarios können nicht mehreren Baustoffkonfigurationen zugeordnet werden!'
                                                  );
                    }
                } else {
                    $this->Log->warning(
                        'Scenarios found for `'.$ProcessDO->name.'\' ['.$ProcessDO->uuid.'] on process data set with the following phases: '.join(
                            ', ',
                            array_keys($ProcessDO->lcPhases)
                        ),
                        __METHOD__
                    );
                    $processStatusDetails[] = t('Szenarien lassen sich nur für EPDs mit Herstellung abbilden.');
                }
            }

            /**
             * Create or update processes for each found epdModule
             */
            foreach ($ProcessDO->epdModules as $epdModule => $scenarioIdents) {
                $lcIdent = $ProcessDO->lcIdents[$epdModule];

                foreach ($scenarioIdents as $scenarioIdent => $indicatorUuids) {
                    $scenarioId = isset($scenarioIdentMap[$scenarioIdent]) && is_object(
                        $scenarioIdentMap[$scenarioIdent]
                    ) ? $scenarioIdentMap[$scenarioIdent]->getId() : null;
                    $Process    = ElcaProcess::findByUuidAndProcessDbIdAndLifeCycleIdentAndScenarioId(
                        $ProcessDO->uuid,
                        $this->ProcessDb->getId(),
                        $lcIdent,
                        $scenarioId
                    );
                    if ($Process->isInitialized()) {
                        $Process->setProcessCategoryNodeId($ProcessDO->processCategoryNodeId);
                        $Process->setName($ProcessDO->name);
                        $Process->setNameOrig($ProcessDO->nameOrig);
                        $Process->setRefUnit($ProcessDO->refUnit);
                        $Process->setVersion($ProcessDO->version);
                        $Process->setDateOfLastRevision($ProcessDO->dateOfLastRevision);
                        $Process->setRefValue($ProcessDO->refValue);
                        $Process->setDescription($ProcessDO->description);
                        $Process->setEpdType(isset($ProcessDO->epdSubType) ? $ProcessDO->epdSubType : null);
                        $Process->setGeographicalRepresentativeness($ProcessDO->geographicalRepresentativeness ?? null);
                        $Process->update();

                        if (!$Process->isValid()) {
                            throw new Soda4LcaException(
                                'Process '.$ProcessDO->uuid.' ['.$ProcessDO->uuid.'] ['.$lcIdent.'] not valid after update',
                                Soda4LcaException::INVALID_PROCESS_AFTER_CREATE_OR_UPDATE,
                                null,
                                $Process->getValidator()->getErrors()
                            );
                        }

                        $this->Log->debug(
                            'Updated process '.$ProcessDO->name.' ['.$Process->getId(
                            ).'] ['.$ProcessDO->uuid.'] ['.$lcIdent.'] in database '.$this->ProcessDb->getName(),
                            __METHOD__
                        );

                    } else {
                        $Process = ElcaProcess::create(
                            $this->ProcessDb->getId(),
                            $ProcessDO->processCategoryNodeId,
                            $ProcessDO->name,
                            $ProcessDO->nameOrig,
                            $ProcessDO->uuid,
                            $lcIdent,
                            $ProcessDO->refUnit,
                            $ProcessDO->version,
                            $ProcessDO->refValue ? $ProcessDO->refValue : 1,
                            $scenarioId,
                            $ProcessDO->description,
                            $ProcessDO->dateOfLastRevision,
                            $ProcessDO->epdSubType ?? null,
                            $ProcessDO->geographicalRepresentativeness ?? null
                        );
                        if (!$Process->isValid()) {
                            throw new Soda4LcaException(
                                'Process '.$ProcessDO->uuid.' ['.$ProcessDO->uuid.'] ['.$lcIdent.'] not valid after create',
                                Soda4LcaException::INVALID_PROCESS_AFTER_CREATE_OR_UPDATE,
                                null,
                                $Process->getValidator()->getErrors()
                            );
                        }

                        $this->Log->debug(
                            'Created new process '.$ProcessDO->name.' ['.$ProcessDO->uuid.'] ['.$lcIdent.'] ['.$scenarioIdent.'] in database '.$this->ProcessDb->getName(
                            ),
                            __METHOD__
                        );
                    }

                    $this->updateProcessNames($ProcessDO, $Process);

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
                    if ((!$ProcessDO->onlyEolOrRecPhase || $isPhaseTwo) &&
                        $ProcessConfigSet instanceOf ElcaProcessConfigSet && $ProcessConfigSet->count()
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
                        if (isset($ProcessDO->lcIdents['A1-A3']) && in_array($lcIdent, ['A1', 'A2', 'A3'])) {
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
                        foreach ($ProcessConfigSet as $ProcessConfig) {

                            /**
                             * Check if this process config has lcIdent assignment
                             */
                            if (ElcaProcessSet::dbCountByProcessConfigId(
                                $ProcessConfig->getId(),
                                [
                                    'process_db_id'    => $this->ProcessDb->getId(),
                                    'life_cycle_ident' => $lcIdent,
                                ],
                                true
                            )
                            ) {
                                if ($update) {
                                    if (isset($scenarioIdentMap[$scenarioIdent])) {
                                        continue;
                                    }

                                    $AssignedProcess = ElcaProcessSet::findByProcessConfigId(
                                        $ProcessConfig->getId(),
                                        [
                                            'process_db_id'    => $this->ProcessDb->getId(),
                                            'life_cycle_ident' => $lcIdent,
                                        ],
                                        [],
                                        true
                                    )->current();

                                    if ($AssignedProcess->getUuid() == $ProcessDO->uuid) {
                                        continue;
                                    }

                                    $LifeCycleAssignment = ElcaProcessLifeCycleAssignment::findByProcessConfigIdAndProcessId(
                                        $ProcessConfig->getId(),
                                        $AssignedProcess->getId()
                                    );

                                    if ($LifeCycleAssignment->isInitialized()) {
                                        $LifeCycleAssignment->delete();
                                        $this->Log->notice(
                                            'Removed assignment: '.$ProcessConfig->getId().' '.$ProcessConfig->getName(
                                            ).' => '.$AssignedProcess->getName()
                                        );
                                    }

                                } else {
                                    $this->Log->debug(
                                        'Skipping assignment to: '.$ProcessConfig->getId().' '.$ProcessConfig->getName(
                                        ).' Another process with lcIdent='.$lcIdent.' is already assigned',
                                        __METHOD__
                                    );
                                    continue;
                                }
                            }

                            /**
                             * In phase 2 check single phase eol processes
                             */
                            if ($isPhaseTwo && $ProcessDO->onlyEolOrRecPhase) {

                                /**
                                 * Assign them only if also a prod process has been assigned
                                 */
                                if (!ElcaProcessSet::dbCountByProcessDbIdAndProcessConfigIdAndPhases(
                                    $this->ProcessDb->getId(),
                                    $ProcessConfig->getId(),
                                    [ElcaLifeCycle::PHASE_PROD],
                                    true
                                )
                                ) {
                                    $this->Log->debug(
                                        'Skipping assignment to: '.$ProcessConfig->getId().' '.$ProcessConfig->getName(
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
                                $ProcessConfig->getId(),
                                $Process->getId()
                            );
                            if (!$LifeCycleAssignment->isInitialized()) {
                                $LifeCycleAssignment = ElcaProcessLifeCycleAssignment::create(
                                    $ProcessConfig->getId(),
                                    $Process->getId()
                                );
                                $this->Log->debug(
                                    'Assigned to: '.$ProcessConfig->getId().' '.$ProcessConfig->getName(),
                                    __METHOD__
                                );
                            }

                            $numAssignments++;

                            if ($ProcessConfig->getName() === $Process->getName()) {
                                $stage = Module::fromValue($Process->getLifeCycleIdent())->stage();
                                if ($stage->isProduction() || $stage->isUsage()) {
                                    $this->updateProcessConfigNames($ProcessDO, $ProcessConfig);
                                }
                            }
                        }

                    } else {
                        /**
                         * Save unassigned processes for later
                         */
                        $this->unassignedProcesses[$ProcessDO->uuid] = $ProcessDO;
                        $this->Log->debug(
                            'Delaying assignments for '.$ProcessDO->nameOrig.' ['.$ProcessDO->uuid.'] ['.join(
                                ', ',
                                $ProcessDO->lcIdents
                            ).']',
                            __METHOD__
                        );
                    }
                }
            }

            /**
             * Insert or update material properties (But don't delete in case of update)
             */
            if (isset($ProcessDO->lcPhases[ElcaLifeCycle::PHASE_PROD]) &&
                $ProcessConfigSet instanceOf ElcaProcessConfigSet &&
                $ProcessConfigSet->count() == 1
            ) {
                if ($matPropProblems = $this->applyMatPropertiesForProcessConfig($ProcessDO, $ProcessConfigSet[0])) {
                    foreach ($matPropProblems as $matPropProblem) {
                        $processStatusDetails[] = $matPropProblem;
                    }
                }
            }

            /**
             * Create process config variants for all flow descendants
             */
            if (isset($ProcessDO->lcPhases[ElcaLifeCycle::PHASE_PROD]) &&
                isset($ProcessDO->flowDescendants) &&
                $ProcessConfigSet instanceOf ElcaProcessConfigSet
            ) {
                $this->applyFlowDescendantsForProcessConfigs($ProcessDO, $ProcessConfigSet);
            }

            $this->Dbh->commit();
        } catch (Exception $Exception) {
            $this->Dbh->rollback();
            throw new Soda4LcaException($Exception->getMessage(), $Exception->getCode(), $Exception, $ProcessDO);
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
    private function findProcessConfigs($ProcessDO, $dontCreate = false)
    {
        /**
         * Try to find by process uuid, then by name
         */
        $ProcessConfigSet = ElcaProcessConfigSet::findByProcessUuid($ProcessDO->uuid);
        if (!count($ProcessConfigSet)) {
            /**
             * Beware of some older single phase processes which have (after name cleanup) the same name
             * like newer multiphase processes!
             *
             * Also include epd type in name search. Some production processes have the same name but different
             * epd types
             */
            $ProcessConfigSet = ElcaProcessConfigSet::findByProcessName(
                $ProcessDO->name,
                $lcPhase = (count($ProcessDO->lcPhases) > 1 ? null : $ProcessDO->singlePhaseProcessPhase),
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
                    $ProcessConfigSet
                ).' process config(s) by process name, '.\implode(' and ', $foundBy).' ['
                .\implode('],[', $ProcessDO->lcIdents).']',
                __METHOD__
            );
        } else {
            $this->Log->notice('Found '.count($ProcessConfigSet).' process config(s) by process uuid', __METHOD__);
        }

        /**
         * If empty, try to create one
         */
        if (!$dontCreate && !count($ProcessConfigSet)) {
            /**
             * Create the config only for production and single operation processes
             */
            if (isset($ProcessDO->lcPhases[ElcaLifeCycle::PHASE_PROD]) ||
                $ProcessDO->singlePhaseProcessPhase == ElcaLifeCycle::PHASE_OP
            ) {
                $ProcessConfig = $this->createProcessConfig($ProcessDO);

                $ProcessConfigSet = new ElcaProcessConfigSet();
                $ProcessConfigSet->add($ProcessConfig);

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

        return $ProcessConfigSet;
    }
    // End getRefUnitByUnit

    /**
     * Create a processConfig for the given ProcessDataSet
     *
     * @param  object ProcessDO
     *
     * @return ElcaProcessConfig
     */
    private function createProcessConfig($ProcessDO)
    {
        $density     = isset($ProcessDO->MatProperties->density) && $ProcessDO->MatProperties->density
            ? $ProcessDO->MatProperties->density : null;
        $minLifeTime = isset($ProcessDO->MatProperties->lifeTime) && $ProcessDO->MatProperties->lifeTime
            ? $ProcessDO->MatProperties->lifeTime : null;

        $ProcessConfig = ElcaProcessConfig::create(
            $ProcessDO->name,
            $ProcessDO->processCategoryNodeId,
            null, // description
            $density,
            null, // $thermalConductivity
            null, // $thermalResistance
            true, // $isReference
            null, // $fHsHi
            $minLifeTime
        );

        $this->Log->notice('Created '.$ProcessConfig->getName().' ['.$ProcessConfig->getId().']', __METHOD__);

        /**
         * Add trivial conversion
         */
        ElcaProcessConversion::create(
            $ProcessConfig->getId(),
            $ProcessDO->refUnit,
            $ProcessDO->refUnit,
            1,
            ElcaProcessConversion::IDENT_PRODUCTION
        );

        return $ProcessConfig;
    }
    // End getProcessCategoryNodeIdByClassId

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
        $problems = [];

        $hasGrossDensity = false;
        foreach ($processDO->MatProperties->conversions as $ident => $convDO) {
            $conversion = ElcaProcessConversion::findByProcessConfigIdAndIdent($processConfig->getId(), $convDO->ident);

            /**
             * Retry with manual inserted conversions (ident isnull)
             */
            if (!$conversion->isInitialized()) {
                $conversion = ElcaProcessConversion::findByProcessConfigIdAndInOut(
                    $processConfig->getId(),
                    $convDO->inUnit,
                    $convDO->outUnit,
                    true
                );
            }

            if ($conversion->isInitialized()) {
                if ($conversion->getIdent() === null) {
                    $this->Log->notice(
                        sprintf(
                            'Found manually added ProcessConversion for %s: [in=%s,out=%s,f=%s] which will be overwritten by %s [in=%s,out=%s,f=%s]',
                            $processConfig->getName(),
                            $conversion->getInUnit(),
                            $conversion->getOutUnit(),
                            $conversion->getFactor(),
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
                            '%ident%'     => $conversion->getIdent(),
                            '%in%'        => $conversion->getInUnit(),
                            '%out%'       => $conversion->getOutUnit(),
                            '%factor%'    => $conversion->getFactor(),
                        ]
                    );
                } elseif ($conversion->getIdent() === ConversionType::INITIAL) {
                    $this->Log->notice(
                        sprintf(
                            'Found previously added ProcessConversion for %s: %s [in=%s,out=%s,f=%s] which will be overwritten by %s [in=%s,out=%s,f=%s]',
                            $processConfig->getName(),
                            $conversion->getIdent(),
                            $conversion->getInUnit(),
                            $conversion->getOutUnit(),
                            $conversion->getFactor(),
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
                            '%ident%'     => $conversion->getIdent(),
                            '%in%'        => $conversion->getInUnit(),
                            '%out%'       => $conversion->getOutUnit(),
                            '%factor%'    => $conversion->getFactor(),
                        ]
                    );
                } elseif (false === FloatCalc::cmp($convDO->factor, $conversion->getFactor())) {
                    $this->Log->error(
                        sprintf(
                            'Skipped updating a ProcessConversion for %s: %s [in=%s,out=%s,f=%f] which conflicts with existing %s [in=%s,out=%s,f=%f]',
                            $processConfig->getName(),
                            $convDO->ident,
                            $convDO->inUnit,
                            $convDO->outUnit,
                            $convDO->factor,
                            $conversion->getIdent(),
                            $conversion->getInUnit(),
                            $conversion->getOutUnit(),
                            $conversion->getFactor()
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
                            '%ident%'     => $conversion->getIdent(),
                            '%in%'        => $conversion->getInUnit(),
                            '%out%'       => $conversion->getOutUnit(),
                            '%factor%'    => $conversion->getFactor(),
                        ]
                    );
                    continue;
                }

                $conversion->setInUnit($convDO->inUnit);
                $conversion->setOutUnit($convDO->outUnit);
                $conversion->setFactor($convDO->factor);
                $conversion->setIdent($convDO->ident);

                if ($conversion->isValid()) {
                    $conversion->update();
                    $this->Log->debug(
                        'Updated ProcessConversion `'.$convDO->ident.'\' for `'.$processConfig->getName(
                        ).'\': [in='.$convDO->inUnit.',out='.$convDO->outUnit.',f='.$convDO->factor.']',
                        __METHOD__
                    );
                } else {
                    $this->Log->error(
                        'Failed to update ProcessConversion `'.$convDO->ident.'\' for `'.$processConfig->getName(
                        ).'\': [in='.$convDO->inUnit.',out='.$convDO->outUnit.',f='.$convDO->factor.']:',
                        __METHOD__
                    );
                    $this->Log->error($conversion->getValidator()->getErrors(), __METHOD__);
                }
            }
            else {
                $conversion = ElcaProcessConversion::create(
                    $processConfig->getId(),
                    $convDO->inUnit,
                    $convDO->outUnit,
                    $convDO->factor,
                    $convDO->ident
                );

                if ($conversion->isValid()) {
                    $this->Log->debug(
                        'Inserted ProcessConversion `'.$convDO->ident.'\' for `'.$processConfig->getName(
                        ).'\': [in='.$convDO->inUnit.',out='.$convDO->outUnit.',f='.$convDO->factor.']',
                        __METHOD__
                    );
                } else {
                    $this->Log->error(
                        'Failed to insert new ProcessConversion `'.$convDO->ident.'\' for `'.$processConfig->getName(
                        ).'\': [in='.$convDO->inUnit.',out='.$convDO->outUnit.',f='.$convDO->factor.']:',
                        __METHOD__
                    );
                    $this->Log->error($conversion->getValidator()->getErrors(), __METHOD__);
                }
            }

            if ($conversion->isValid() && $convDO->ident === ElcaProcessConversion::IDENT_GROSS_DENSITY) {
                //$processConfig->setDensity($convDO->factor);
                $processConfig->update();
                $this->Log->debug('Updated density in ProcessConfig `'.$processConfig->getName().'\'', __METHOD__);

                $hasGrossDensity = true;
            }
        }

        /**
         * Check if a gross density conversion was included in the given material properties. If not,
         * remove the ident of the manually added density conversion, if it exists.
         *
         * The ident field is being used to determine whether a conversion has been created manually or via
         * the soda interface
         */
        if (false === $hasGrossDensity) {
            $conversion = ElcaProcessConversion::findByProcessConfigIdAndInOut($processConfig->getId(), Unit::CUBIC_METER, Unit::KILOGRAMM);

            if ($conversion->isInitialized() && $conversion->getIdent()) {
                $conversion->setIdent(null);
                $conversion->update();

                $this->Log->debug('Unset the ident of density process conversion `'.$conversion->getId().'\'. This conversion has been manually added.', __METHOD__);
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
}
// End Soda4LcaImporter