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
namespace Elca\Db;

use Beibob\Blibs\Url;
use Elca\Elca;
use PDO;
use Exception;
use Beibob\Blibs\DbObject;

/**
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcess extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.processes';

    const EPD_TYPE_GENERIC = 'generic dataset';
    const EPD_TYPE_SPECIFIC = 'specific dataset';
    const EPD_TYPE_REPRESENTATIVE = 'representative dataset';
    const EPD_TYPE_AVERAGE = 'average dataset';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'id'                    => PDO::PARAM_INT,
        'processDbId'           => PDO::PARAM_INT,
        'processCategoryNodeId' => PDO::PARAM_INT,
        'name'                  => PDO::PARAM_STR,
        'nameOrig'              => PDO::PARAM_STR,
        'uuid'                  => PDO::PARAM_STR,
        'version'               => PDO::PARAM_STR,
        'dateOfLastRevision'    => PDO::PARAM_STR,
        'lifeCycleIdent'        => PDO::PARAM_STR,
        'refValue'              => PDO::PARAM_STR,
        'refUnit'               => PDO::PARAM_STR,
        'scenarioId'            => PDO::PARAM_INT,
        'description'           => PDO::PARAM_STR,
        'epdType'               => PDO::PARAM_STR,
        'created'               => PDO::PARAM_STR,
        'modified'              => PDO::PARAM_STR,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array(
        'processLifeCycleAssignmentId' => PDO::PARAM_STR,
        'processConfigId'              => PDO::PARAM_INT,
        'ratio'                        => PDO::PARAM_INT,
        'lifeCycleName'                => PDO::PARAM_STR,
        'lifeCyclePhase'               => PDO::PARAM_STR,
        'processCategoryNodeName'      => PDO::PARAM_STR,
    );

    /**
     * processId
     */
    private $id;

    /**
     * database id
     */
    private $processDbId;

    /**
     * category node id
     */
    private $processCategoryNodeId;

    /**
     * name
     */
    private $name;

    /**
     * original name
     */
    private $nameOrig;

    /**
     * uuid of the process
     */
    private $uuid;

    /**
     * version string
     */
    private $version;

    /**
     * dateOfLastRevision
     */
    private $dateOfLastRevision;

    /**
     * life cycle ident
     */
    private $lifeCycleIdent;

    /**
     * reference value
     */
    private $refValue;

    /**
     * unit of the reference value
     */
    private $refUnit;

    /**
     * scenarioId
     */
    private $scenarioId;

    /**
     * some description
     */
    private $description;

    private $epdType;

    private $geographicalRepresentativeness;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;

    /**
     * extension: process life cycle assignment id
     */
    private $processLifeCycleAssignmentId;

    /**
     * extension: process config id
     */
    private $processConfigId;

    /**
     * extension: ratio (between 0 and 1)
     */
    private $ratio;

    /**
     * extension: life cycle name
     */
    private $lifeCycleName;

    /**
     * extension: life cycle phase
     */
    private $lifeCyclePhase;

    /**
     * extension: category node name
     */
    private $processCategoryNodeName;

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer $processDbId           - database id
     * @param  integer $processCategoryNodeId - category node id
     * @param  string  $name                  - name
     * @param  string  $nameOrig              - original name
     * @param  string  $uuid                  - uuid of the process
     * @param  string  $lifeCycleIdent        - life cycle ident
     * @param  string  $refUnit               - unit of the reference value
     * @param  string  $version               - version string
     * @param  number  $refValue              - reference value
     * @param  string  $description           - some description
     */
    public static function create(
        $processDbId,
        $processCategoryNodeId,
        $name,
        $nameOrig,
        $uuid,
        $lifeCycleIdent,
        $refUnit,
        $version = null,
        $refValue = 1,
        $scenarioId = null,
        $description = null,
        $dateOfLastRevision = null,
        $epdType = null,
        $geographicalRepresentativeness = null
    ) {
        $ElcaProcess = new ElcaProcess();
        $ElcaProcess->setProcessDbId($processDbId);
        $ElcaProcess->setProcessCategoryNodeId($processCategoryNodeId);
        $ElcaProcess->setName($name);
        $ElcaProcess->setNameOrig($nameOrig);
        $ElcaProcess->setUuid($uuid);
        $ElcaProcess->setLifeCycleIdent($lifeCycleIdent);
        $ElcaProcess->setRefUnit($refUnit);
        $ElcaProcess->setVersion($version);
        $ElcaProcess->setRefValue($refValue);
        $ElcaProcess->setDescription($description);
        $ElcaProcess->setDateOfLastRevision($dateOfLastRevision);
        $ElcaProcess->setScenarioId($scenarioId);
        $ElcaProcess->setEpdType($epdType);
        $ElcaProcess->setGeographicalRepresentativeness($geographicalRepresentativeness);

        if ($ElcaProcess->getValidator()->isValid()) {
            $ElcaProcess->insert();
        }

        return $ElcaProcess;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcess' by its primary key
     *
     * @param  integer $id    - processId
     * @param  boolean $force - Bypass caching
     * @return ElcaProcess
     */
    public static function findById($id, $extended = false, $force = false)
    {
        if (!$id) {
            return new ElcaProcess();
        }

        $sql = sprintf(
            "SELECT *
                          FROM %s
                         WHERE id = :id"
            ,
            $extended ? self::TABLE_NAME : ElcaProcessSet::VIEW_ELCA_PROCESSES
        );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcess' by its unique key (uuid, processDbId, $lifeCycleIdent, $scenarioId)
     *
     * @param  string  $uuid        - uuid of the process
     * @param  integer $processDbId - database id
     * @param  string  $lifeCycleIdent
     * @param  boolean $force       - Bypass caching
     * @return ElcaProcess
     */
    public static function findByUuidAndProcessDbIdAndLifeCycleIdentAndScenarioId(
        $uuid,
        $processDbId,
        $lifeCycleIdent,
        $scenarioId = null,
        $extended = false,
        $force = false
    ) {
        if (!$uuid || !$processDbId || !$lifeCycleIdent) {
            return new ElcaProcess();
        }

        $initValues = array(
            'uuid'           => $uuid,
            'processDbId'    => $processDbId,
            'lifeCycleIdent' => $lifeCycleIdent,
        );

        $sql = sprintf(
            "SELECT *
                          FROM %s
                         WHERE process_db_id = :processDbId
                           AND uuid = :uuid
                           AND life_cycle_ident = :lifeCycleIdent"
            ,
            $extended ? self::TABLE_NAME : ElcaProcessSet::VIEW_ELCA_PROCESSES
        );

        if ($scenarioId) {
            $sql .= ' AND scenario_id = :scenarioId';
            $initValues['scenarioId'] = $scenarioId;
        } else {
            $sql .= ' AND scenario_id IS NULL';
        }

        return self::findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByUuidAndProcessDbIdAndLifeCycleIdent



    /**
     * Inits a `ElcaProcess' by its unique key (uuid, processDbId, $lifeCycleIdent, $scenarioId)
     *
     * @param  string  $uuid        - uuid of the process
     * @param  integer $processDbId - database id
     * @param  string  $lifeCycleIdent
     * @param  boolean $force       - Bypass caching
     * @return ElcaProcess
     */
    public static function findByUuidAndProcessDbIdAndLifeCycleIdentAndScenarioIdent(
        $uuid,
        $processDbId,
        $lifeCycleIdent,
        $scenarioIdent = null,
        $force = false
    ) {
        if (!$uuid || !$processDbId || !$lifeCycleIdent) {
            return new ElcaProcess();
        }

        $initValues = array(
            'uuid'           => $uuid,
            'processDbId'    => $processDbId,
            'lifeCycleIdent' => $lifeCycleIdent,
        );

        $sql = sprintf(
            'SELECT p.*
               FROM %s p
               LEFT JOIN %s s ON p.scenario_id = s.id                         
              WHERE process_db_id = :processDbId
                AND uuid = :uuid
                AND life_cycle_ident = :lifeCycleIdent',
            self::TABLE_NAME,
            ElcaProcessScenario::TABLE_NAME
        );

        if ($scenarioIdent) {
            $sql .= ' AND s.ident = :scenarioIdent';
            $initValues['scenarioIdent'] = $scenarioIdent;
        }
        else {
            $sql .= ' AND p.scenario_id IS NULL';
        }

        return self::findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByUuidAndProcessDbIdAndLifeCycleIdent

    /**
     * Inits a `ElcaProcess' by its name and processDbId
     *
     * @param  string  $name        - name of the process
     * @param  integer $processDbId - database id
     * @param  boolean $force       - Bypass caching
     * @return ElcaProcess
     */
    public static function findByNameAndProcessDbId($name, $processDbId, $extended = false, $force = false)
    {
        if (!$name || !$processDbId) {
            return new ElcaProcess();
        }

        $sql = sprintf(
            "SELECT *
                          FROM %s
                         WHERE name = :name
                           AND process_db_id = :processDbId"
            ,
            $extended ? self::TABLE_NAME : ElcaProcessSet::VIEW_ELCA_PROCESSES
        );

        return self::findBySql(get_class(), $sql, array('name' => $name, 'processDbId' => $processDbId), $force);
    }
    // End findByUuidAndProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer $id    - processId
     * @param  boolean $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the tablename constant. This is used
     * as interface for other objects.
     *
     * @return string
     */
    public static function getTablename()
    {
        return self::TABLE_NAME;
    }
    // End setProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  boolean $extColumns
     * @param  mixed   $column
     * @return mixed
     */
    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns ? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if ($column) {
            return $columnTypes[$column];
        }

        return $columnTypes;
    }
    // End setProcessCategoryNodeId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a deep copy of the current process
     *
     * @param  int $processDbId
     * @return ElcaProcess
     */
    public function copy($processDbId)
    {
        if (!$this->isInitialized() || !$processDbId) {
            return new ElcaProcess();
        }

        try {
            $this->Dbh->begin();

            /**
             * Create copy
             */
            $Copy = self::create(
                $processDbId,
                $this->processCategoryNodeId,
                $this->name,
                $this->nameOrig,
                $this->uuid,
                $this->lifeCycleIdent,
                $this->refUnit,
                $this->version,
                $this->refValue,
                $this->scenarioId,
                $this->description,
                $this->dateOfLastRevision,
                $this->epdType,
                $this->geographicalRepresentativeness
            );

            /**
             * Copy process indicators
             */
            $ProcessIndicatorSet = ElcaProcessIndicatorSet::findByProcessId($this->getId());
            $newProcessId        = $Copy->getId();
            foreach ($ProcessIndicatorSet as $ProcessIndicator) {
                $ProcessIndicator->copy($newProcessId);
            }

            /**
             * Copy process assignments
             */
            $LifeCycleAssignmentSet = ElcaProcessLifeCycleAssignmentSet::find(array('process_id' => $this->getId()));
            foreach ($LifeCycleAssignmentSet as $Assignment) {
                $Assignment->copy($newProcessId);
            }

            $this->Dbh->commit();
        } catch (Exception $Exception) {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Copy;
    }
    // End setName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    // End setNameOrig

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property processDbId
     *
     * @return integer
     */
    public function getProcessDbId()
    {
        return $this->processDbId;
    }
    // End setUuid

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processDbId
     *
     * @param  integer $processDbId - database id
     * @return
     */
    public function setProcessDbId($processDbId)
    {
        if (!$this->getValidator()->assertNotEmpty('processDbId', $processDbId)) {
            return;
        }

        $this->processDbId = (int)$processDbId;
    }
    // End setVersion

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcessDb by property processDbId
     *
     * @param  boolean $force
     * @return ElcaProcessDb
     */
    public function getProcessDb($force = false)
    {
        return ElcaProcessDb::findById($this->processDbId, $force);
    }
    // End setDateOfLastRevision

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property processCategoryNodeId
     *
     * @return integer
     */
    public function getProcessCategoryNodeId()
    {
        return $this->processCategoryNodeId;
    }
    // End setLifeCycleIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processCategoryNodeId
     *
     * @param  integer $processCategoryNodeId - category node id
     * @return
     */
    public function setProcessCategoryNodeId($processCategoryNodeId)
    {
        if (!$this->getValidator()->assertNotEmpty('processCategoryNodeId', $processCategoryNodeId)) {
            return;
        }

        $this->processCategoryNodeId = (int)$processCategoryNodeId;
    }
    // End setRefValue

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcessCategory by property processCategoryNodeId
     *
     * @param  boolean $force
     * @return ElcaProcessCategory
     */
    public function getProcessCategoryNode($force = false)
    {
        return ElcaProcessCategory::findByNodeId($this->processCategoryNodeId, $force);
    }
    // End setRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the extension property processCategoryNodeName
     *
     * @return string
     */
    public function getProcessCategoryNodeName()
    {
        return $this->processCategoryNodeName ? $this->processCategoryNodeName
            : $this->getProcessCategoryNode()->getName();
    }
    // End setScenarioId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    // End setDescription

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property name
     *
     * @param  string $name - name
     * @return
     */
    public function setName($name)
    {
        if (!$this->getValidator()->assertNotEmpty('name', $name)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('name', 250, $name)) {
            return;
        }

        $this->name = (string)$name;
    }
    // End getId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property nameOrig
     *
     * @return string
     */
    public function getNameOrig()
    {
        return $this->nameOrig;
    }
    // End getProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property nameOrig
     *
     * @param  string $nameOrig - original name
     * @return
     */
    public function setNameOrig($nameOrig)
    {
        if (!$this->getValidator()->assertNotEmpty('nameOrig', $nameOrig)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('nameOrig', 250, $nameOrig)) {
            return;
        }

        $this->nameOrig = (string)$nameOrig;
    }
    // End getProcessDb

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
    // End getProcessCategoryNodeId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property uuid
     *
     * @param  string $uuid - uuid of the process
     * @return
     */
    public function setUuid($uuid)
    {
        if (!$this->getValidator()->assertNotEmpty('uuid', $uuid)) {
            return;
        }

        $this->uuid = (string)$uuid;
    }
    // End getProcessCategoryNode

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
    // End getProcessCategoryNodeName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property version
     *
     * @param  string $version - version string
     * @return
     */
    public function setVersion($version = null)
    {
        if (!$this->getValidator()->assertMaxLength('version', 50, $version)) {
            return;
        }

        $this->version = $version;
    }
    // End getName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property dateOfLastRevision
     *
     * @return string
     */
    public function getDateOfLastRevision()
    {
        return $this->dateOfLastRevision;
    }
    // End getNameOrig

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property dateOfLastRevision
     *
     * @param  string $dateOfLastRevision
     * @return
     */
    public function setDateOfLastRevision($dateOfLastRevision = null)
    {
        $this->dateOfLastRevision = $dateOfLastRevision;
    }
    // End getUuid

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property lifeCycleIdent
     *
     * @return string
     */
    public function getLifeCycleIdent()
    {
        return $this->lifeCycleIdent;
    }
    // End getVersion

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property lifeCycleIdent
     *
     * @param  string $lifeCycleIdent - life cycle ident
     * @return
     */
    public function setLifeCycleIdent($lifeCycleIdent)
    {
        if (!$this->getValidator()->assertNotEmpty('lifeCycleIdent', $lifeCycleIdent)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('lifeCycleIdent', 20, $lifeCycleIdent)) {
            return;
        }

        $this->lifeCycleIdent = (string)$lifeCycleIdent;
    }
    // End getDateOfLastRevision

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaLifeCycle by property lifeCycleIdent
     *
     * @param  boolean $force
     * @return ElcaLifeCycle
     */
    public function getLifeCycle($force = false)
    {
        return ElcaLifeCycle::findByIdent($this->lifeCycleIdent, $force);
    }
    // End getLifeCycleIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property lifeCycleName
     *
     * @return string
     */
    public function getLifeCycleName()
    {
        return $this->lifeCycleName ? $this->lifeCycleName
            : ElcaLifeCycle::findByIdent($this->lifeCycleIdent)->getName();
    }
    // End getLifeCycle

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property lifeCyclePhase
     *
     * @return string
     */
    public function getLifeCyclePhase()
    {
        return $this->lifeCyclePhase
            ? $this->lifeCyclePhase
            : ElcaLifeCycle::findByIdent(
                $this->lifeCycleIdent
            )->getPhase();
    }
    // End getLifeCycleName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property refValue
     *
     * @return number
     */
    public function getRefValue()
    {
        return $this->refValue;
    }
    // End getLifeCyclePhase

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refValue
     *
     * @param  number $refValue - reference value
     * @return
     */
    public function setRefValue($refValue = 1)
    {
        $this->refValue = $refValue;
    }
    // End getRefValue

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property refUnit
     *
     * @return string
     */
    public function getRefUnit()
    {
        return $this->refUnit;
    }
    // End getRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refUnit
     *
     * @param  string $refUnit - unit of the reference value
     * @return
     */
    public function setRefUnit($refUnit)
    {
        if (!$this->getValidator()->assertNotEmpty('refUnit', $refUnit)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('refUnit', 10, $refUnit)) {
            return;
        }

        $this->refUnit = (string)$refUnit;
    }
    // End getScenarioId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property scenarioId
     *
     * @return integer
     */
    public function getScenarioId()
    {
        return $this->scenarioId;
    }
    // End getScenario

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property scenarioId
     *
     * @param  integer $scenarioId - scenarioId
     * @return
     */
    public function setScenarioId($scenarioId = null)
    {
        $this->scenarioId = $scenarioId;
    }
    // End getDescription

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the scenario
     *
     * @return ElcaProcessScenario
     */
    public function getScenario()
    {
        return ElcaProcessScenario::findById($this->scenarioId);
    }

    /**
     * @return mixed
     */
    public function getEpdType()
    {
        return $this->epdType;
    }

    /**
     * @param mixed $epdType
     */
    public function setEpdType($epdType)
    {
        $this->epdType = $epdType;
    }

    public function getGeographicalRepresentativeness()
    {
        return $this->geographicalRepresentativeness;
    }

    public function setGeographicalRepresentativeness($geographicalRepresentativeness = null)
    {
        $this->geographicalRepresentativeness = $geographicalRepresentativeness;
    }

    /**
     * Returns the property description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    // End getModified

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property description
     *
     * @param  string $description - some description
     * @return
     */
    public function setDescription($description = null)
    {
        $this->description = $description;
    }
    // End getProcessLifeCycleAssignmentId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property created
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }
    // End getProcessLifeCycleAssignment

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property modified
     *
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }
    // End getProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the extension processLifeCycleAssignmentId if set
     * only when initialized by view ElcaProcessSet::VIEW_PROCESS_ASSIGNMENTS
     *
     * @return int
     */
    public function getProcessLifeCycleAssignmentId()
    {
        return $this->processLifeCycleAssignmentId;
    }
    // End getProcessConfig

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the ProcessLifeCycleAssignment object if set
     * only when initialized by view ElcaProcessSet::VIEW_PROCESS_ASSIGNMENTS
     *
     * @return ElcaProcessLifeCycleAssignment
     */
    public function getProcessLifeCycleAssignment()
    {
        return ElcaProcessLifeCycleAssignment::findById($this->processLifeCycleAssignmentId);
    }
    // End getRatio

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the extension processConfigId if set
     * only when initialized by view ElcaProcessSet::VIEW_PROCESS_ASSIGNMENTS
     *
     * @return int
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getIndicators

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the ProcessConfig object if set
     * only when initialized by view ElcaProcessSet::VIEW_PROCESS_ASSIGNMENTS
     *
     * @return ElcaProcessConfig
     */
    public function getProcessConfig()
    {
        return ElcaProcessConfig::findById($this->processConfigId);
    }
    // End getProcessIndicators

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the extension ratio if set
     * only when initialized by view ElcaProcessSet::VIEW_PROCESS_ASSIGNMENTS
     *
     * @return float
     */
    public function getRatio()
    {
        return $this->ratio;
    }

    /**
     * Returns a IndicatorSet with all Indicators for the current process
     *
     * @param bool $withHidden
     * @return ElcaIndicatorSet
     */
    public function getIndicators($withHidden = false)
    {
        return ElcaIndicatorSet::findByProcessDbId($this->processDbId, false, $withHidden);
    }
    // End exists

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a ElcaProcessIndicatorSet with all ProcessIndicators for the current process
     *
     * @return ElcaProcessIndicatorSet
     */
    public function getProcessIndicators()
    {
        return ElcaProcessIndicatorSet::findByProcessId($this->id);
    }
    // End update

    //////////////////////////////////////////////////////////////////////////////////////

    public function hasSourceUri()
    {
        return (bool)$this->sourceUri;
    }

    /**
     * @return string
     */
    public function getDataSheetUrl()
    {
        $processDb = $this->getProcessDb();

        if (!$processDb->hasSourceUri()) {
            return null;
        }

        return (string)Url::factory(
            $processDb->getSourceUri() .'/../../processes/'. $this->getUuid(),
            [
                'lang' => Elca::getInstance()->getLocale(),
                'version' => $this->getVersion()
            ]
        );
    }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $this->modified = self::getCurrentTime();

        $sql = sprintf(
            "UPDATE %s
                           SET process_db_id         = :processDbId
                             , process_category_node_id = :processCategoryNodeId
                             , name                  = :name
                             , name_orig             = :nameOrig
                             , uuid                  = :uuid
                             , version               = :version
                             , date_of_last_revision = :dateOfLastRevision
                             , life_cycle_ident      = :lifeCycleIdent
                             , ref_value             = :refValue
                             , ref_unit              = :refUnit
                             , scenario_id     = :scenarioId
                             , description           = :description
                             , epd_type              = :epdType
                             , geographical_representativeness = :geographicalRepresentativeness
                             , created               = :created
                             , modified              = :modified
                         WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->updateBySql(
            $sql,
            array(
                'id'                    => $this->id,
                'processDbId'           => $this->processDbId,
                'processCategoryNodeId' => $this->processCategoryNodeId,
                'name'                  => $this->name,
                'nameOrig'              => $this->nameOrig,
                'uuid'                  => $this->uuid,
                'version'               => $this->version,
                'dateOfLastRevision'    => $this->dateOfLastRevision,
                'lifeCycleIdent'        => $this->lifeCycleIdent,
                'refValue'              => $this->refValue,
                'refUnit'               => $this->refUnit,
                'scenarioId'            => $this->scenarioId,
                'description'           => $this->description,
                'epdType'               => $this->epdType,
                'geographicalRepresentativeness' => $this->geographicalRepresentativeness,
                'created'               => $this->created,
                'modified'              => $this->modified,
            )
        );
    }
    // End getPrimaryKey

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf(
            "DELETE FROM %s
                              WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->deleteBySql(
            $sql,
            array('id' => $this->id)
        );
    }
    // End getTablename

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  boolean $propertiesOnly
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if ($propertiesOnly) {
            return self::$primaryKey;
        }

        $primaryKey = array();

        foreach (self::$primaryKey as $key) {
            $primaryKey[$key] = $this->$key;
        }

        return $primaryKey;
    }
    // End getColumnTypes

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $this->id       = $this->getNextSequenceValue();
        $this->created  = self::getCurrentTime();
        $this->modified = null;

        $sql = sprintf(
            "INSERT INTO %s (id, process_db_id, process_category_node_id, name, name_orig, uuid, version, date_of_last_revision, life_cycle_ident, ref_value, ref_unit, scenario_id, epd_type, geographical_representativeness, description, created, modified)
                               VALUES  (:id, :processDbId, :processCategoryNodeId, :name, :nameOrig, :uuid, :version, :dateOfLastRevision, :lifeCycleIdent, :refValue, :refUnit, :scenarioId, :epdType, :geographicalRepresentativeness, :description, :created, :modified)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            array(
                'id'                    => $this->id,
                'processDbId'           => $this->processDbId,
                'processCategoryNodeId' => $this->processCategoryNodeId,
                'name'                  => $this->name,
                'nameOrig'              => $this->nameOrig,
                'uuid'                  => $this->uuid,
                'version'               => $this->version,
                'dateOfLastRevision'    => $this->dateOfLastRevision,
                'lifeCycleIdent'        => $this->lifeCycleIdent,
                'refValue'              => $this->refValue,
                'refUnit'               => $this->refUnit,
                'description'           => $this->description,
                'scenarioId'            => $this->scenarioId,
                'epdType'               => $this->epdType,
                'geographicalRepresentativeness' => $this->geographicalRepresentativeness,
                'created'               => $this->created,
                'modified'              => $this->modified,
            )
        );
    }
    // End insert

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return boolean
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->id                    = (int)$DO->id;
        $this->processDbId           = (int)$DO->process_db_id;
        $this->processCategoryNodeId = (int)$DO->process_category_node_id;
        $this->name                  = $DO->name;
        $this->nameOrig              = $DO->name_orig;
        $this->uuid                  = $DO->uuid;
        $this->version               = $DO->version;
        $this->dateOfLastRevision    = $DO->date_of_last_revision;
        $this->lifeCycleIdent        = $DO->life_cycle_ident;
        $this->refValue              = $DO->ref_value;
        $this->refUnit               = $DO->ref_unit;
        $this->scenarioId            = $DO->scenario_id;
        $this->epdType               = $DO->epd_type;
        $this->description           = $DO->description;
        $this->created               = $DO->created;
        $this->modified              = $DO->modified;
        $this->geographicalRepresentativeness = $DO->geographical_representativeness;

        /**
         * Set extensions
         */
        if (isset($DO->life_cycle_name)) {
            $this->lifeCycleName = $DO->life_cycle_name;
        }

        if (isset($DO->life_cycle_phase)) {
            $this->lifeCyclePhase = $DO->life_cycle_phase;
        }

        if (isset($DO->process_life_cycle_assignment_id)) {
            $this->processLifeCycleAssignmentId = $DO->process_life_cycle_assignment_id;
        }

        if (isset($DO->process_config_id)) {
            $this->processConfigId = $DO->process_config_id;
        }

        if (isset($DO->ratio)) {
            $this->ratio = $DO->ratio;
        }

        if (isset($DO->process_category_node_name)) {
            $this->processCategoryNodeName = $DO->process_category_node_name;
        }
    }
    // End initByDataObject
}
// End class ElcaProcess