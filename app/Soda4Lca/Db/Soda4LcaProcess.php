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

namespace Soda4Lca\Db;

use PDO;
use Beibob\Blibs\DbObject;

/**
 *
 * @package    soda4lca
 * @class      Soda4LcaProcess
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 *
 * $Id$
 */
class Soda4LcaProcess extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'soda4lca.processes';

    /**
     * Status
     */
    const STATUS_OK = 'OK';
    const STATUS_SKIPPED = 'SKIPPED';
    const STATUS_UNASSIGNED = 'UNASSIGNED';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * import id
     */
    private $importId;

    /**
     * process uuid
     */
    private $uuid;

    /**
     * process version
     */
    private $version;

    /**
     * process latest version
     */
    private $latestVersion;

    /**
     * name
     */
    private $name;

    /**
     * classification
     */
    private $classId;

    /**
     * epdModules
     */
    private $epdModules;

    /**
     * import status
     */
    private $status;

    /**
     * errorCode
     */
    private $errorCode;

    /**
     * detail info
     */
    private $details;

    /**
     * creation time
     */
    private $created;

    /**
     * Primary key
     */
    private static $primaryKey = array('importId', 'uuid');

    /**
     * Column types
     */
    private static $columnTypes = array('importId'       => PDO::PARAM_INT,
                                        'uuid'           => PDO::PARAM_STR,
                                        'version'        => PDO::PARAM_STR,
                                        'latestVersion'  => PDO::PARAM_STR,
                                        'name'           => PDO::PARAM_STR,
                                        'classId'        => PDO::PARAM_STR,
                                        'epdModules'     => PDO::PARAM_STR,
                                        'status'         => PDO::PARAM_STR,
                                        'errorCode'      => PDO::PARAM_STR,
                                        'details'        => PDO::PARAM_STR,
                                        'created'        => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer $importId   - import id
     * @param  string  $uuid       - process uuid
     * @param  string  $name       - name
     * @param  string  $classId    - classification
     * @param  string  $status     - import status
     * @param  string  $version    - process version
     * @param  string  $details    - detail info
     * @param  string  $epdModules - epdModules
     * @param  string  $errorCode  - errorCode
     *
     * @return Soda4LcaProcess
     */
    public static function create($importId, $uuid, $name, $classId, $status, $version = null, $details = null, $epdModules = null, $errorCode = null)
    {
        $Soda4LcaProcess = new Soda4LcaProcess();
        $Soda4LcaProcess->setImportId($importId);
        $Soda4LcaProcess->setUuid($uuid);
        $Soda4LcaProcess->setVersion($version);
        $Soda4LcaProcess->setName($name);
        $Soda4LcaProcess->setClassId($classId);
        $Soda4LcaProcess->setEpdModules($epdModules);
        $Soda4LcaProcess->setStatus($status);
        $Soda4LcaProcess->setErrorCode($errorCode);
        $Soda4LcaProcess->setDetails($details);

        if($Soda4LcaProcess->getValidator()->isValid())
            $Soda4LcaProcess->insert();

        return $Soda4LcaProcess;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `Soda4LcaProcess' by its primary key
     *
     * @param  integer  $importId - import id
     * @param  string   $uuid    - process uuid
     * @param  boolean  $force   - Bypass caching
     * @return Soda4LcaProcess
     */
    public static function findByPk($importId, $uuid, $force = false)
    {
        if(!$importId || !$uuid)
            return new Soda4LcaProcess();

        $sql = sprintf("SELECT import_id
                             , uuid
                             , version
                             , latest_version
                             , name
                             , status
                             , class_id
                             , epd_modules
                             , error_code
                             , details
                             , created
                          FROM %s
                         WHERE import_id = :importId
                           AND uuid = :uuid"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('importId' => $importId, 'uuid' => $uuid), $force);
    }
    // End findByPk

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property importId
     *
     * @param  integer  $importId - import id
     * @return
     */
    public function setImportId($importId)
    {
        if(!$this->getValidator()->assertNotEmpty('importId', $importId))
            return;

        $this->importId = (int)$importId;
    }
    // End setImportId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property uuid
     *
     * @param  string   $uuid  - process uuid
     * @return
     */
    public function setUuid($uuid)
    {
        if(!$this->getValidator()->assertNotEmpty('uuid', $uuid))
            return;

        $this->uuid = (string)$uuid;
    }
    // End setUuid

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property version
     *
     * @param  string   $version - process version
     * @return
     */
    public function setVersion($version)
    {
        $this->version = (string)$version;
    }
    // End setVersion

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property latestVersion
     *
     * @param  string   $latestVersion - process latest version
     * @return
     */
    public function setLatestVersion($latestVersion)
    {
        $this->latestVersion = (string)$latestVersion;
    }
    // End setLatestVersion

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property name
     *
     * @param  string   $name  - name
     * @return
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;

        if(!$this->getValidator()->assertMaxLength('name', 250, $name))
            return;

        $this->name = (string)$name;
    }
    // End setName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property classId
     *
     * @param  string   $classId - classification
     * @return
     */
    public function setClassId($classId)
    {
        if(!$this->getValidator()->assertNotEmpty('classId', $classId))
            return;

        if(!$this->getValidator()->assertMaxLength('classId', 50, $classId))
            return;

        $this->classId = (string)$classId;
    }
    // End setClassId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property epdModules
     *
     * @param  string   $epdModules - epdModules
     * @return
     */
    public function setEpdModules($epdModules = null)
    {
        $this->epdModules = $epdModules;
    }
    // End setEpdModules

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property status
     *
     * @param  string   $status - import status
     * @return
     */
    public function setStatus($status)
    {
        if(!$this->getValidator()->assertNotEmpty('status', $status))
            return;

        if(!$this->getValidator()->assertMaxLength('status', 20, $status))
            return;

        $this->status = (string)$status;
    }
    // End setStatus

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property errorCode
     *
     * @param  int   $errorCode - errorCode
     * @return
     */
    public function setErrorCode($errorCode = null)
    {
        $this->errorCode = $errorCode;
    }
    // End setErrorCode

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property details
     *
     * @param  string   $details - detail info
     * @return
     */
    public function setDetails($details = null)
    {
        $this->details = $details;
    }
    // End setDetails

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property importId
     *
     * @return integer
     */
    public function getImportId()
    {
        return $this->importId;
    }
    // End getImportId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated Soda4LcaImport by property importId
     *
     * @param  boolean  $force
     * @return Soda4LcaImport
     */
    public function getImport($force = false)
    {
        return Soda4LcaImport::findById($this->importId, $force);
    }
    // End getImport

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
    // End getUuid

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
    // End getVersion

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property latest version
     *
     * @return string
     */
    public function getLatestVersion()
    {
        return $this->latestVersion;
    }
    // End getVersion

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
    // End getName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property classId
     *
     * @return string
     */
    public function getClassId()
    {
        return $this->classId;
    }
    // End getClassId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property epdModules
     *
     * @return string
     */
    public function getEpdModules()
    {
        return $this->epdModules;
    }
    // End getEpdModules

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    // End getStatus

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property errorCode
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
    // End getErrorCode

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property details
     *
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }
    // End getDetails

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
    // End getCreated

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $importId - import id
     * @param  string   $uuid    - process uuid
     * @param  boolean  $force   - Bypass caching
     * @return boolean
     */
    public static function exists($importId, $uuid, $force = false)
    {
        return self::findByPk($importId, $uuid, $force)->isInitialized();
    }
    // End exists

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET version        = :version
                             , latest_version = :latestVersion
                             , name           = :name
                             , status         = :status
                             , class_id       = :classId
                             , epd_modules    = :epdModules
                             , error_code     = :errorCode
                             , details        = :details
                             , created        = :created
                         WHERE import_id = :importId
                           AND uuid = :uuid"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('importId'      => $this->importId,
                                        'uuid'          => $this->uuid,
                                        'version'       => $this->version,
                                        'latestVersion' => $this->latestVersion,
                                        'name'          => $this->name,
                                        'classId'       => $this->classId,
                                        'epdModules'    => $this->epdModules,
                                        'status'        => $this->status,
                                        'errorCode'     => $this->errorCode,
                                        'details'       => $this->details,
                                        'created'       => $this->created)
                                  );
    }
    // End update

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE import_id = :importId
                                AND uuid = :uuid"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('importId' => $this->importId, 'uuid' => $this->uuid));
    }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  boolean  $propertiesOnly
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if($propertiesOnly)
            return self::$primaryKey;

        $primaryKey = array();

        foreach(self::$primaryKey as $key)
            $primaryKey[$key] = $this->$key;

        return $primaryKey;
    }
    // End getPrimaryKey

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
    // End getTablename

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  boolean  $extColumns
     * @param  mixed    $column
     * @return mixed
     */
    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if($column)
            return $columnTypes[$column];

        return $columnTypes;
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
        $this->created        = self::getCurrentTime();

        $sql = sprintf("INSERT INTO %s (import_id, uuid, version, latest_version, name, class_id, epd_modules, status, error_code, details, created)
                               VALUES  (:importId, :uuid, :version, :latestVersion, :name, :classId, :epdModules, :status, :errorCode, :details, :created)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('importId'      => $this->importId,
                                        'uuid'          => $this->uuid,
                                        'version'       => $this->version,
                                        'latestVersion' => $this->latestVersion,
                                        'name'          => $this->name,
                                        'classId'       => $this->classId,
                                        'epdModules'    => $this->epdModules,
                                        'status'        => $this->status,
                                        'errorCode'     => $this->errorCode,
                                        'details'       => $this->details,
                                        'created'       => $this->created)
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
        $this->importId       = (int)$DO->import_id;
        $this->uuid           = $DO->uuid;
        $this->version        = $DO->version;
        $this->latestVersion  = $DO->latest_version;
        $this->name           = $DO->name;
        $this->classId        = $DO->class_id;
        $this->epdModules     = $DO->epd_modules;
        $this->status         = $DO->status;
        $this->errorCode      = $DO->error_code;
        $this->details        = $DO->details;
        $this->created        = $DO->created;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class Soda4LcaProcess