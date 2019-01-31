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

use Beibob\Blibs\DbObject;
use Beibob\Blibs\Group;
use Beibob\Blibs\User;
use Elca\Elca;
use Elca\Security\EncryptedPassword;
use Exception;
use PDO;

/**
 * eLCA project
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaProject extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.projects';

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'id'                 => PDO::PARAM_INT,
        'processDbId'        => PDO::PARAM_INT,
        'currentVariantId'   => PDO::PARAM_INT,
        'ownerId'            => PDO::PARAM_INT,
        'accessGroupId'      => PDO::PARAM_INT,
        'name'               => PDO::PARAM_STR,
        'description'        => PDO::PARAM_STR,
        'projectNr'          => PDO::PARAM_STR,
        'constrMeasure'      => PDO::PARAM_INT,
        'lifeTime'           => PDO::PARAM_INT,
        'constrClassId'      => PDO::PARAM_INT,
        'editor'             => PDO::PARAM_STR,
        'isReference'        => PDO::PARAM_BOOL,
        'benchmarkVersionId' => PDO::PARAM_INT,
        'password'           => PDO::PARAM_STR,
        'created'            => PDO::PARAM_STR,
        'modified'           => PDO::PARAM_STR,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * projectId
     */
    private $id;

    /**
     * process data sets to base lca on
     */
    private $processDbId;

    /**
     * the most recent project variant id
     */
    private $currentVariantId;

    /**
     * the group id that is allowed accessing this project
     */
    private $accessGroupId;

    /**
     * the id of the initial creator of the project
     */
    private $ownerId;

    /**
     * project title
     */
    private $name;

    /**
     * description
     */
    private $description;

    /**
     * project number
     */
    private $projectNr;

    /**
     * construction measure
     */
    private $constrMeasure;

    /**
     * project life time
     */
    private $lifeTime;

    /**
     * construction classification
     */
    private $constrClassId;

    /**
     * editor name
     */
    private $editor;

    /**
     * marks a reference project
     */
    private $isReference;

    /**
     * Reference to benchmark version
     */
    private $benchmarkVersionId;

    private $password;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;

    /**
     * @var array
     */
    private $accessUserIds;

    /**
     * Creates the object
     *
     * @param  integer $processDbId      - process data sets to base lca on
     * @param  integer $accessGroupId    - the group id that is allowed accessing this project
     * @param  string  $name             - project title
     * @param  integer $lifeTime         - project life time
     * @param  integer $currentVariantId - the most recent project variant id
     * @param  string  $description      - description
     * @param  string  $projectNr        - project number
     * @param int      $constrMeasure    - construction measure
     * @param  integer $constrClassId    - construction classification
     * @param  string  $editor           - editor name
     * @param  boolean $isReference      - marks a reference project
     * @param null     $benchmarkVersionId
     * @return ElcaProject
     */
    public static function create(
        $processDbId,
        $ownerId,
        $accessGroupId,
        $name,
        $lifeTime,
        $currentVariantId = null,
        $description = null,
        $projectNr = null,
        $constrMeasure = Elca::CONSTR_MEASURE_PRIVATE,
        $constrClassId = null,
        $editor = null,
        $isReference = false,
        $benchmarkVersionId = null,
        $password = null
    ) {
        $ElcaProject = new ElcaProject();
        $ElcaProject->setProcessDbId($processDbId);
        $ElcaProject->setOwnerId($ownerId);
        $ElcaProject->setAccessGroupId($accessGroupId);
        $ElcaProject->setName($name);
        $ElcaProject->setLifeTime($lifeTime);
        $ElcaProject->setCurrentVariantId($currentVariantId);
        $ElcaProject->setDescription($description);
        $ElcaProject->setProjectNr($projectNr);
        $ElcaProject->setConstrMeasure($constrMeasure);
        $ElcaProject->setConstrClassId($constrClassId);
        $ElcaProject->setEditor($editor);
        $ElcaProject->setIsReference($isReference);
        $ElcaProject->setBenchmarkVersionId($benchmarkVersionId);
        $ElcaProject->setPassword($password);

        if ($ElcaProject->getValidator()->isValid()) {
            $ElcaProject->insert();
        }

        return $ElcaProject;
    }
    // End create


    /**
     * Inits a `ElcaProject' by its primary key
     *
     * @param  integer $id    - projectId
     * @param  boolean $force - Bypass caching
     * @return ElcaProject
     */
    public static function findById($id, $force = false)
    {
        if (!$id) {
            return new ElcaProject();
        }

        $sql = sprintf(
            "SELECT id
                             , process_db_id
                             , current_variant_id
                             , access_group_id
                             , owner_id
                             , name
                             , description
                             , project_nr
                             , constr_measure
                             , life_time
                             , constr_class_id
                             , editor
                             , is_reference
                             , benchmark_version_id
                             , password
                             , created
                             , modified
                             , user_ids
                          FROM %s
                         WHERE id = :id"
            ,
            ElcaProjectSet::PROJECTS_VIEW
        );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }

    /**
     * @param      $projectVariantId
     * @param bool $force
     * @return bool|ElcaProject
     */
    public static function findByProjectVariantId($projectVariantId, $force = false)
    {
        if (!$projectVariantId) {
            return new ElcaProject();
        }

        $sql = sprintf(
            "SELECT p.id
                             , p.process_db_id
                             , p.current_variant_id
                             , p.access_group_id
                             , p.owner_id
                             , p.name
                             , p.description
                             , p.project_nr
                             , p.constr_measure
                             , p.life_time
                             , p.constr_class_id
                             , p.editor
                             , p.is_reference
                             , p.benchmark_version_id
                             , p.password
                             , p.created
                             , p.modified
                             , p.user_ids
                          FROM %s p
                          JOIN %s v ON p.id = v.project_id
                         WHERE v.id = :projectVariantId"
            ,
            ElcaProjectSet::PROJECTS_VIEW
            ,
            ElcaProjectVariant::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId), $force);
    }

    /**
     * Checks, if the object exists
     *
     * @param  integer $id    - projectId
     * @param  boolean $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End copy

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
    // End setCurrentVariantId

    /**
     * Creates a deep copy from this projhect
     *
     * @param  int     $projectId new projectId
     * @param bool|int $copyName  -> namen 1:1 kopieren
     * @throws Exception
     * @return ElcaProject - the new project copy
     */
    public function copy($projectId, $copyName = false)
    {
        if (!$this->isInitialized() || !$projectId) {
            return new ElcaProject();
        }

        try {
            $this->Dbh->begin();
            $Copy = self::create(
                $this->processDbId,
                $this->ownerId,
                $this->accessGroupId,
                $copyName ? $this->name : t('Kopie von').' '.$this->name,
                $this->lifeTime,
                null, // currentVariantId
                $this->description,
                $this->projectNr,
                $this->constrMeasure,
                $this->constrClassId,
                $this->editor,
                false, // dont pass isReference flag
                $this->benchmarkVersionId,
                $this->password
            );

            /**
             * Copy variants
             */
            if ($Copy->isInitialized()) {
                $ProjectVariants = ElcaProjectVariantSet::find(
                    array('project_id' => $this->getId()),
                    array('id' => 'ASC')
                );
                $CopyVariants    = new ElcaProjectVariantSet();

                $copiedCurrentVariantId = null;
                foreach ($ProjectVariants as $ProjectVariant) {

                    $variantCopy = $ProjectVariant->copy($Copy->getId(), null, true);
                    $CopyVariants->add($variantCopy);

                    if ($ProjectVariant->getId() === $this->currentVariantId) {
                        $copiedCurrentVariantId = $variantCopy->getId();
                    }
                }

                /**
                 * Copy lifeCycle usages
                 */
                foreach (ElcaProjectLifeCycleUsageSet::findByProjectId($this->getId()) as $usage) {
                    ElcaProjectLifeCycleUsage::create(
                        $Copy->getId(),
                        $usage->getLifeCycleIdent(),
                        $usage->getUseInConstruction(),
                        $usage->getUseInMaintenance(),
                        $usage->getUseInEnergyDemand()
                    );
                }

                if ($copiedCurrentVariantId) {
                    $Copy->setCurrentVariantId($copiedCurrentVariantId);
                } else {
                    /**
                     * Set currentVariantId to first variant
                     */
                    $Copy->setCurrentVariantId($CopyVariants[0]->getId());
                }
                $Copy->update();
            }

            $this->Dbh->commit();
        }
        catch (Exception $Exception) {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Copy;
    }
    // End setAccessGroupId

    /**
     * Returns the property id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    // End setOwnerId

    /**
     * Returns the property processDbId
     *
     * @return integer
     */
    public function getProcessDbId()
    {
        return $this->processDbId;
    }
    // End setName

    /**
     * Sets the property processDbId
     *
     * @param  integer $processDbId - process data sets to base lca on
     * @return
     */
    public function setProcessDbId($processDbId)
    {
        if (!$this->getValidator()->assertNotEmpty('processDbId', $processDbId)) {
            return;
        }

        $this->processDbId = (int)$processDbId;
    }
    // End setDescription

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
    // End setProjectNr

    /**
     * Returns the property currentVariantId
     *
     * @return integer
     */
    public function getCurrentVariantId()
    {
        return $this->currentVariantId;
    }
    // End setConstrMeasure

    /**
     * Sets the property currentVariantId
     *
     * @param  integer $currentVariantId - the most recent project variant id
     * @return
     */
    public function setCurrentVariantId($currentVariantId = null)
    {
        $this->currentVariantId = $currentVariantId;
    }
    // End setLifeTime

    /**
     * Returns the associated ElcaProjectVariant by property currentVariantId
     *
     * @param  boolean $force
     * @return ElcaProjectVariant
     */
    public function getCurrentVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->currentVariantId, $force);
    }
    // End setConstrClassId

    /**
     * Returns the property accessGroupId
     *
     * @return integer
     */
    public function getAccessGroupId()
    {
        return $this->accessGroupId;
    }
    // End setEditor

    /**
     * Sets the property accessGroupId
     *
     * @param  integer $accessGroupId - the group id that is allowed accessing this project
     * @return
     */
    public function setAccessGroupId($accessGroupId)
    {
        if (!$this->getValidator()->assertNotEmpty('accessGroupId', $accessGroupId)) {
            return;
        }

        $this->accessGroupId = (int)$accessGroupId;
    }
    // End setIsReference

    /**
     * Returns the associated Group by property accessGroupId
     *
     * @param  boolean $force
     * @return Group
     */
    public function getAccessGroup($force = false)
    {
        return Group::findById($this->accessGroupId, $force);
    }

    /**
     * Returns the property ownerId
     *
     * @return integer
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Sets the property ownerId
     *
     * @param  integer $ownerId
     */
    public function setOwnerId($ownerId)
    {
        if (!$this->getValidator()->assertNotEmpty('ownerId', $ownerId)) {
            return;
        }

        $this->ownerId = (int)$ownerId;
    }
    // End getId


    /**
     * @return User
     */
    public function getOwner()
    {
        return User::findById($this->ownerId);
    }


    /**
     * Returns the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    // End getProcessDbId

    /**
     * Sets the property name
     *
     * @param  string $name - project title
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
    // End getProcessDb

    /**
     * Returns the property description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    // End getCurrentVariantId

    /**
     * Sets the property description
     *
     * @param  string $description - description
     * @return
     */
    public function setDescription($description = null)
    {
        $this->description = $description;
    }
    // End getCurrentVariantId

    /**
     * Returns the property projectNr
     *
     * @return string
     */
    public function getProjectNr()
    {
        return $this->projectNr;
    }
    // End getAccessGroupId

    /**
     * Sets the property projectNr
     *
     * @param  string $projectNr - project number
     * @return
     */
    public function setProjectNr($projectNr = null)
    {
        if (!$this->getValidator()->assertMaxLength('projectNr', 200, $projectNr)) {
            return;
        }

        $this->projectNr = $projectNr;
    }
    // End getAccessGroup

    /**
     * Returns the property constrMeasure
     *
     * @return integer
     */
    public function getConstrMeasure()
    {
        return $this->constrMeasure;
    }
    // End getOwnerId

    /**
     * Sets the property constrMeasure
     *
     * @param  integer $constrMeasure - construction measure
     * @return
     */
    public function setConstrMeasure($constrMeasure = Elca::CONSTR_MEASURE_PRIVATE)
    {
        $this->constrMeasure = (int)$constrMeasure;
    }
    // End getName

    /**
     * Returns the property lifeTime
     *
     * @return integer
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }
    // End getDescription

    /**
     * Sets the property lifeTime
     *
     * @param  integer $lifeTime - project life time
     * @return
     */
    public function setLifeTime($lifeTime)
    {
        if (!$this->getValidator()->assertNotEmpty('lifeTime', $lifeTime)) {
            return;
        }

        $this->lifeTime = (int)$lifeTime;
    }
    // End getProjectNr

    /**
     * Returns the property constrClassId
     *
     * @return  integer  construction classification id
     */
    public function getConstrClassId()
    {
        return $this->constrClassId;
    }
    // End getConstrMeasure

    /**
     * Sets the property constrClassId
     *
     * @param  integer $constrClassId - construction classification
     * @return
     */
    public function setConstrClassId($constrClassId = null)
    {
        $this->constrClassId = $constrClassId;
    }
    // End getLifeTime

    /**
     * Returns the ElcaConstrClass instance associated with this project
     *
     * @return  ElcaConstrClass
     */
    public function getConstrClass()
    {
        return ElcaConstrClass::findById($this->constrClassId);
    }
    // End getConstrClassId

    /**
     * Returns the property editor
     *
     * @return string $editor
     */
    public function getEditor()
    {
        return $this->editor;
    }
    // End getConstrClass

    /**
     * Sets the property editor
     *
     * @param  string $editor
     * @return
     */
    public function setEditor($editor)
    {
        $this->editor = $editor;
    }
    // End getEditor

    /**
     * Returns the property isReference
     *
     * @return boolean
     */
    public function isReference()
    {
        return $this->isReference;
    }
    // End isReference

    /**
     * Sets the property isReference
     *
     * @param  boolean $isReference - marks a reference project
     * @return void
     */
    public function setIsReference($isReference = false)
    {
        $this->isReference = (bool)$isReference;
    }
    // End getBenchmarkVersionId

    /**
     * Returns the property benchmarkVersionId
     *
     * @return  int
     */
    public function getBenchmarkVersionId()
    {
        return $this->benchmarkVersionId;
    }

    /**
     * Sets the property benchmarkVersionId
     *
     * @param  int $benchmarkVersionId
     * @return void
     */
    public function setBenchmarkVersionId($benchmarkVersionId = null)
    {
        $this->benchmarkVersionId = $benchmarkVersionId;
    }

    /**
     * Returns the property benchmarkVersionId
     *
     * @return  ElcaBenchmarkVersion
     */
    public function getBenchmarkVersion()
    {
        return ElcaBenchmarkVersion::findById($this->benchmarkVersionId);
    }

    /**
     * @return bool
     */
    public function hasPassword()
    {
        return null !== $this->password;
    }
    // End getCreated

    /**
     * @return EncryptedPassword
     */
    public function getPassword()
    {
        return new EncryptedPassword($this->password);
    }
    // End getModified

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
    // End getProjectLocation

    /**
     * Returns the property created
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }
    // End getProjectConstruction

    /**
     * Returns the property modified
     *
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }
    // End getProjectEnEv

    /**
     * Returns the Project location
     *
     * @return ElcaProjectLocation
     */
    public function getProjectLocation()
    {
        return ElcaProjectLocation::findByProjectVariantId($this->currentVariantId);
    }

    /**
     * Returns the ProjectConstruction
     *
     * @return ElcaProjectConstruction
     */
    public function getProjectConstruction()
    {
        return ElcaProjectConstruction::findByProjectVariantId($this->currentVariantId);
    }
    // End exists

    /**
     * Returns the ProjectEnEv
     *
     * @return ElcaProjectEnEv
     */
    public function getProjectEnEv()
    {
        return ElcaProjectEnEv::findByProjectVariantId($this->currentVariantId);
    }
    // End update

    /**
     * @param bool $force
     * @return ElcaProjectVariantSet
     */
    public function getProjectVariants($force = false)
    {
        return ElcaProjectVariantSet::findByProjectId($this->getId(), [], [], null, null, $force);
    }
    // End delete

    /**
     * @param int $userId
     * @return bool
     */
    public function userHasAccess($userId)
    {
        if ($this->ownerId === $userId) {
            return true;
        }

        return in_array($userId, $this->accessUserIds, true);
    }

    public function passwordIsExpired(int $expirationInterval, int $now = null): bool
    {
        if (!$this->hasPassword()) {
            return false;
        }

        if (!$pwSetDate = $this->getPasswordSetDate()) {
            return false;
        }

        $now = $now ?? \time();

        return ($now - $pwSetDate->getTimestamp()) > $expirationInterval;
    }

    public function getPasswordSetDate(): ?\DateTime {
        $pwSetDateString = ElcaProjectAttribute::findValue(
            $this->id,ElcaProjectAttribute::IDENT_PW_DATE
        );

        if (!$pwSetDateString) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d', $pwSetDateString);
    }


    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        return $this->Dbh->atomic(
            function () {
                $this->modified = self::getCurrentTime();

                $sql = sprintf(
                    "UPDATE %s
                           SET process_db_id    = :processDbId
                             , current_variant_id = :currentVariantId
                             , access_group_id  = :accessGroupId
                             , owner_id  = :ownerId
                             , name             = :name
                             , description      = :description
                             , project_nr       = :projectNr
                             , constr_measure   = :constrMeasure
                             , life_time        = :lifeTime
                             , constr_class_id  = :constrClassId
                             , editor           = :editor
                             , is_reference     = :isReference
                             , benchmark_version_id = :benchmarkVersionId
                             , password         = :password
                             , created          = :created
                             , modified         = :modified
                         WHERE id = :id"
                    ,
                    self::TABLE_NAME
                );

                $result = $this->updateBySql(
                    $sql,
                    array(
                        'id'                 => $this->id,
                        'processDbId'        => $this->processDbId,
                        'currentVariantId'   => $this->currentVariantId,
                        'accessGroupId'      => $this->accessGroupId,
                        'ownerId'            => $this->ownerId,
                        'name'               => $this->name,
                        'description'        => $this->description,
                        'projectNr'          => $this->projectNr,
                        'constrMeasure'      => $this->constrMeasure,
                        'lifeTime'           => $this->lifeTime,
                        'constrClassId'      => $this->constrClassId,
                        'editor'             => $this->editor,
                        'isReference'        => $this->isReference,
                        'benchmarkVersionId' => $this->benchmarkVersionId,
                        'password'           => $this->password,
                        'created'            => $this->created,
                        'modified'           => $this->modified,
                    )
                );

                $accessGroup = $this->getAccessGroup();

                if (!$accessGroup->isUsergroup()) {
                    $accessGroup->setName(sprintf('%s[%s]', $this->name, $this->id));
                    $accessGroup->update();
                }

                return $result;
            }
        );
    }
    // End getPrimaryKey

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        return $this->Dbh->atomic(
            function () {
                $accessGroup = $this->getAccessGroup();

                $sql = sprintf(
                    "DELETE FROM %s
                              WHERE id = :id"
                    ,
                    self::TABLE_NAME
                );

                $result = $this->deleteBySql(
                    $sql,
                    array('id' => $this->id)
                );

                if (false === $accessGroup->isUsergroup()) {
                    $accessGroup->delete();
                }

                return $result;
            }
        );
    }
    // End getTablename

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

    /**
     * @param bool $force
     * @return ElcaProjectAttributeSet
     */
    public function getAttributes($force = false)
    {
        return ElcaProjectAttributeSet::find(['project_id' => $this->getId()], null, null, null, $force);
    }

    /**
     * @return ElcaProjectLifeCycleUsageSet
     */
    public function getLifeCycleUsage()
    {
        return ElcaProjectLifeCycleUsageSet::findByProjectId($this->id);
    }

    /**
     * @return ElcaProjectLifeCycleUsageSet
     */
    public function getLifeCycleUsageForUseInConstruction()
    {
        return ElcaProjectLifeCycleUsageSet::findByProjectId($this->id, ['use_in_construction' => true]);
    }

    /**
     * @return ElcaProjectLifeCycleUsageSet
     */
    public function getLifeCycleUsageForUseInMaintenance()
    {
        return ElcaProjectLifeCycleUsageSet::findByProjectId($this->id, ['use_in_maintenance' => true]);
    }

    /**
     * @return ElcaProjectLifeCycleUsageSet
     */
    public function getLifeCycleUsageForUseInEnergyDemand()
    {
        return ElcaProjectLifeCycleUsageSet::findByProjectId($this->id, ['use_in_energy_demand' => true]);
    }

    public function clearPassword()
    {
        try {
            $this->Dbh->begin();

            $this->setPassword(null);
            $this->update();

            $attribute = ElcaProjectAttribute::findByProjectIdAndIdent($this->id, ElcaProjectAttribute::IDENT_PW_DATE);
            $attribute->delete();

            $this->Dbh->commit();
        } catch (Exception $exception) {
            $this->Dbh->rollback();
            throw $exception;
        }
    }


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
            "INSERT INTO %s (id, process_db_id, current_variant_id, owner_id, access_group_id, name, description, project_nr, constr_measure, life_time, constr_class_id, editor, is_reference, benchmark_version_id, password, created, modified)
                               VALUES  (:id, :processDbId, :currentVariantId, :ownerId, :accessGroupId, :name, :description, :projectNr, :constrMeasure, :lifeTime, :constrClassId, :editor, :isReference, :benchmarkVersionId, :password, :created, :modified)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            array(
                'id'                 => $this->id,
                'processDbId'        => $this->processDbId,
                'currentVariantId'   => $this->currentVariantId,
                'ownerId'            => $this->ownerId,
                'accessGroupId'      => $this->accessGroupId,
                'name'               => $this->name,
                'description'        => $this->description,
                'projectNr'          => $this->projectNr,
                'constrMeasure'      => $this->constrMeasure,
                'lifeTime'           => $this->lifeTime,
                'constrClassId'      => $this->constrClassId,
                'editor'             => $this->editor,
                'isReference'        => $this->isReference,
                'benchmarkVersionId' => $this->benchmarkVersionId,
                'password'           => $this->password,
                'created'            => $this->created,
                'modified'           => $this->modified,
            )
        );
    }
    // End insert


    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return boolean
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->id                 = (int)$DO->id;
        $this->processDbId        = (int)$DO->process_db_id;
        $this->currentVariantId   = $DO->current_variant_id;
        $this->ownerId            = $DO->owner_id;
        $this->accessGroupId      = (int)$DO->access_group_id;
        $this->name               = $DO->name;
        $this->description        = $DO->description;
        $this->projectNr          = $DO->project_nr;
        $this->constrMeasure      = (int)$DO->constr_measure;
        $this->lifeTime           = (int)$DO->life_time;
        $this->constrClassId      = $DO->constr_class_id;
        $this->editor             = $DO->editor;
        $this->isReference        = (bool)$DO->is_reference;
        $this->benchmarkVersionId = $DO->benchmark_version_id;
        $this->password           = $DO->password;
        $this->created            = $DO->created;
        $this->modified           = $DO->modified;

        /**
         * Set extensions
         */
        $this->accessUserIds = array_map(
            function ($userId) {
                return (int)$userId;
            },
            str_getcsv(trim($DO->user_ids, '{}'))
        );
    }
    // End initByDataObject
}
// End class ElcaProject