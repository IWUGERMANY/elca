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

use PDO;
use Exception;
use Beibob\Blibs\DbObject;
/**
 * Represents the sanity status of a process config
 *
 * @package    elca
 * @class      ElcaProcessConfigSanity
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaProcessConfigSanity extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_config_sanities';

    /**
     * Procedures
     */
    const PROCEDURE_UPDATE_PROCESS_CONFIG_SANITIES = 'elca.update_process_config_sanities()';

    /**
     * Status
     */
    const STATUS_MISSING_PRODUCTION = 'MISSING_PRODUCTION';
    const STATUS_MISSING_EOL = 'MISSING_EOL';
    const STATUS_MISSING_CONVERSIONS = 'MISSING_CONVERSIONS';
    const STATUS_MISSING_LIFE_TIME = 'MISSING_LIFE_TIME';
    const STATUS_MISSING_DENSITY = 'MISSING_DENSITY';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * processConfigSanityId
     */
    private $id;

    /**
     * process_config
     */
    private $processConfigId;

    /**
     * status info
     */
    private $status;

    /**
     * database id
     */
    private $processDbId;

    /**
     * detail info
     */
    private $details;

    /**
     * flags as false positive
     */
    private $isFalsePositive;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'              => PDO::PARAM_INT,
                                        'processConfigId' => PDO::PARAM_INT,
                                        'status'          => PDO::PARAM_STR,
                                        'processDbId'     => PDO::PARAM_INT,
                                        'details'         => PDO::PARAM_STR,
                                        'isFalsePositive' => PDO::PARAM_BOOL,
                                        'created'         => PDO::PARAM_STR,
                                        'modified'        => PDO::PARAM_STR);

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
     * @param  integer  $processConfigId - process_config
     * @param  string   $status         - status info
     * @param  integer  $processDbId    - database id
     * @param  string   $details        - detail info
     * @param  boolean  $isFalsePositive - flags as false positive
     */
    public static function create($processConfigId, $status, $processDbId = null, $details = null, $isFalsePositive = false)
    {
        $ElcaProcessConfigSanity = new ElcaProcessConfigSanity();
        $ElcaProcessConfigSanity->setProcessConfigId($processConfigId);
        $ElcaProcessConfigSanity->setStatus($status);
        $ElcaProcessConfigSanity->setProcessDbId($processDbId);
        $ElcaProcessConfigSanity->setDetails($details);
        $ElcaProcessConfigSanity->setIsFalsePositive($isFalsePositive);

        if($ElcaProcessConfigSanity->getValidator()->isValid())
            $ElcaProcessConfigSanity->insert();

        return $ElcaProcessConfigSanity;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessConfigSanity' by its primary key
     *
     * @param  integer  $id    - processConfigSanityId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfigSanity
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProcessConfigSanity();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , status
                             , process_db_id
                             , details
                             , is_false_positive
                             , created
                             , modified
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessConfigSanity' by its unique key (processConfigId, status, processDbId)
     *
     * @param  integer  $processConfigId - process_config
     * @param  string   $status         - status info
     * @param  integer  $processDbId    - database id
     * @param  boolean  $force          - Bypass caching
     * @return ElcaProcessConfigSanity
     */
    public static function findByProcessConfigIdAndStatusAndProcessDbId($processConfigId, $status, $processDbId, $force = false)
    {
        if(!$processConfigId || !$status || !$processDbId)
            return new ElcaProcessConfigSanity();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , status
                             , process_db_id
                             , details
                             , is_false_positive
                             , created
                             , modified
                          FROM %s
                         WHERE process_config_id = :processConfigId
                           AND status = :status
                           AND process_db_id = :processDbId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('processConfigId' => $processConfigId, 'status' => $status, 'processDbId' => $processDbId), $force);
    }
    // End findByProcessConfigIdAndStatusAndProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the sanities table
     *
     * @param  integer  $id    - processConfigSanityId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfigSanity
     */
    public static function refreshEntries()
    {
        $sql = sprintf("SELECT %s", self::PROCEDURE_UPDATE_PROCESS_CONFIG_SANITIES);

        return self::performSql(get_class(), $sql);
    }
    // End refreshEntries

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processConfigId
     *
     * @param  integer  $processConfigId - process_config
     * @return
     */
    public function setProcessConfigId($processConfigId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId))
            return;

        $this->processConfigId = (int)$processConfigId;
    }
    // End setProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property status
     *
     * @param  string   $status - status info
     * @return
     */
    public function setStatus($status)
    {
        if(!$this->getValidator()->assertNotEmpty('status', $status))
            return;

        if(!$this->getValidator()->assertMaxLength('status', 50, $status))
            return;

        $this->status = (string)$status;
    }
    // End setStatus

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processDbId
     *
     * @param  integer  $processDbId - database id
     * @return
     */
    public function setProcessDbId($processDbId = null)
    {
        $this->processDbId = $processDbId;
    }
    // End setProcessDbId

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
     * Sets the property isFalsePositive
     *
     * @param  boolean  $isFalsePositive - flags as false positive
     * @return
     */
    public function setIsFalsePositive($isFalsePositive = false)
    {
        $this->isFalsePositive = (bool)$isFalsePositive;
    }
    // End setIsFalsePositive

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
    // End getId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property processConfigId
     *
     * @return integer
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcessConfig by property processConfigId
     *
     * @param  boolean  $force
     * @return ElcaProcessConfig
     */
    public function getProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->processConfigId, $force);
    }
    // End getProcessConfig

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
     * Returns the property processDbId
     *
     * @return integer
     */
    public function getProcessDbId()
    {
        return $this->processDbId;
    }
    // End getProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcessDb by property processDbId
     *
     * @param  boolean  $force
     * @return ElcaProcessDb
     */
    public function getProcessDb($force = false)
    {
        return ElcaProcessDb::findById($this->processDbId, $force);
    }
    // End getProcessDb

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
     * Returns the property isFalsePositive
     *
     * @return boolean
     */
    public function isFalsePositive()
    {
        return $this->isFalsePositive;
    }
    // End isFalsePositive

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
     * Returns the property modified
     *
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }
    // End getModified

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - processConfigSanityId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
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
        $this->modified = self::getCurrentTime();

        $sql = sprintf("UPDATE %s
                           SET process_config_id = :processConfigId
                             , status          = :status
                             , process_db_id   = :processDbId
                             , details         = :details
                             , is_false_positive = :isFalsePositive
                             , created         = :created
                             , modified        = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'status'         => $this->status,
                                        'processDbId'    => $this->processDbId,
                                        'details'        => $this->details,
                                        'isFalsePositive' => $this->isFalsePositive,
                                        'created'        => $this->created,
                                        'modified'       => $this->modified)
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
                              WHERE id = :id"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('id' => $this->id));
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
        $this->id              = $this->getNextSequenceValue();
        $this->created         = self::getCurrentTime();
        $this->modified        = null;

        $sql = sprintf("INSERT INTO %s (id, process_config_id, status, process_db_id, details, is_false_positive, created, modified)
                               VALUES  (:id, :processConfigId, :status, :processDbId, :details, :isFalsePositive, :created, :modified)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'status'         => $this->status,
                                        'processDbId'    => $this->processDbId,
                                        'details'        => $this->details,
                                        'isFalsePositive' => $this->isFalsePositive,
                                        'created'        => $this->created,
                                        'modified'       => $this->modified)
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
        $this->id              = (int)$DO->id;
        $this->processConfigId = (int)$DO->process_config_id;
        $this->status          = $DO->status;
        $this->processDbId     = $DO->process_db_id;
        $this->details         = $DO->details;
        $this->isFalsePositive = (bool)$DO->is_false_positive;
        $this->created         = $DO->created;
        $this->modified        = $DO->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessConfigSanity