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
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessLifeCycleAssignment extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_life_cycle_assignments';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * processLifeCycleAssignmentId
     */
    private $id;

    /**
     * processConfigId
     */
    private $processConfigId;

    /**
     * processId
     */
    private $processId;

    /**
     * ratio
     */
    private $ratio;

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
                                        'processId'       => PDO::PARAM_INT,
                                        'ratio'           => PDO::PARAM_STR,
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
     * @param  integer  $processConfigId - processConfigId
     * @param  integer  $processId      - processId
     * @param  number  $ratio          - ratio
     */
    public static function create($processConfigId, $processId, $ratio = 1)
    {
        $ElcaProcessLifeCycleAssignment = new ElcaProcessLifeCycleAssignment();
        $ElcaProcessLifeCycleAssignment->setProcessConfigId($processConfigId);
        $ElcaProcessLifeCycleAssignment->setProcessId($processId);
        $ElcaProcessLifeCycleAssignment->setRatio($ratio);

        if($ElcaProcessLifeCycleAssignment->getValidator()->isValid())
            $ElcaProcessLifeCycleAssignment->insert();

        return $ElcaProcessLifeCycleAssignment;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessLifeCycleAssignment' by its primary key
     *
     * @param  integer  $id    - processLifeCycleAssignmentId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessLifeCycleAssignment
     */
    public static function findById($id, $force = false)
    {
        if(!is_numeric($id))
            return new ElcaProcessLifeCycleAssignment();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , process_id
                             , ratio
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
     * Inits a `ElcaProcessLifeCycleAssignment' by its process_config_id & process_id
     *
     * @param  integer  $processConfigId - process config id
     * @param  integer  $processId       - process id
     * @param  boolean  $force           - Bypass caching
     * @return ElcaProcessLifeCycleAssignment
     */
    public static function findByProcessConfigIdAndProcessId($processConfigId,$processId, $force = false)
    {
        if(!$processConfigId or !$processId)
            return new ElcaProcessLifeCycleAssignment();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , process_id
                             , ratio
                             , created
                             , modified
                          FROM %s
                         WHERE process_id = :pid
                           AND process_config_id = :pcid"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('pid' => $processId, 'pcid' => $processConfigId), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of the current process lifeCycle assignment
     *
     * @param  int $processId
     * @return ElcaProcessLifeCycleAssignment
     */
    public function copy($processId)
    {
        if(!$this->isInitialized() || !$processId)
            return new ElcaProcessLifeCycleAssignment();

        /**
         * Create copy
         */
        return self::create($this->processConfigId,
                            $processId,
                            $this->ratio);
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processConfigId
     *
     * @param  integer  $processConfigId - processConfigId
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
     * Sets the property processId
     *
     * @param  integer  $processId - processId
     * @return
     */
    public function setProcessId($processId)
    {
        if(!$this->getValidator()->assertNotEmpty('processId', $processId))
            return;

        $this->processId = (int)$processId;
    }
    // End setProcessId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ratio
     *
     * @param  number  $ratio - ratio
     * @return
     */
    public function setRatio($ratio)
    {
        if(!$this->getValidator()->assertNotEmpty('ratio', $ratio))
            return;

        $this->ratio = $ratio;
    }
    // End setRatio

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
     * Returns the property processId
     *
     * @return integer
     */
    public function getProcessId()
    {
        return $this->processId;
    }
    // End getProcessId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcess by property processId
     *
     * @param  boolean  $force
     * @return ElcaProcess
     */
    public function getProcess($force = false)
    {
        return ElcaProcess::findById($this->processId, $force);
    }
    // End getProcess

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property ratio
     *
     * @return number
     */
    public function getRatio()
    {
        return $this->ratio;
    }
    // End getRatio

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
     * @param  integer  $id    - processLifeCycleAssignmentId
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
                             , process_id      = :processId
                             , ratio           = :ratio
                             , created         = :created
                             , modified        = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'processId'      => $this->processId,
                                        'ratio'          => $this->ratio,
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

        $sql = sprintf("INSERT INTO %s (id, process_config_id, process_id, ratio, created, modified)
                               VALUES  (:id, :processConfigId, :processId, :ratio, :created, :modified)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'processId'      => $this->processId,
                                        'ratio'          => $this->ratio,
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
        $this->processId       = (int)$DO->process_id;
        $this->ratio           = $DO->ratio;
        $this->created         = $DO->created;
        $this->modified        = $DO->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessLifeCycleAssignment