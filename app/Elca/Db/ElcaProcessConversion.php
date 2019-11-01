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
use Elca\Model\Process\ProcessDbId;
use PDO;

/**
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConversion extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_conversions';

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
     * input unit of measure
     */
    private $inUnit;

    /**
     * output unit of measure
     */
    private $outUnit;


    /**
     * extension properties
     */

    /**
     * processDbId
     */
    private $processDbId;

    /**
     * conversion factor
     */
    private $factor;

    /**
     * internal ident
     */
    private $ident;

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
                                        'inUnit'          => PDO::PARAM_STR,
                                        'outUnit'         => PDO::PARAM_STR,
                                        );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('factor'          => PDO::PARAM_STR,
                                           'ident'           => PDO::PARAM_STR,
                                           'created'         => PDO::PARAM_STR,
                                           'modified'        => PDO::PARAM_STR);

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer $processConfigId - processConfigId
     * @param  string $inUnit - input unit of measure
     * @param  string $outUnit - output unit of measure
     * @return ElcaProcessConversion
     */
    public static function create($processConfigId, $inUnit, $outUnit)
    {
        $processConversion = new ElcaProcessConversion();
        $processConversion->setProcessConfigId($processConfigId);
        $processConversion->setInUnit($inUnit);
        $processConversion->setOutUnit($outUnit);

        if ($processConversion->getValidator()->isValid()) {
            $processConversion->insert();
        }

        return $processConversion;
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessConversion' by its primary key
     *
     * @param  integer  $id    - processLifeCycleAssignmentId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConversion
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProcessConversion();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , in_unit
                             , out_unit
                             , created
                             , modified
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById


    /**
     * Inits a `ElcaProcessConversion' by its unique key (processConfigId, ident)
     *
     * @param  integer  $processConfigId - processConfigId
     * @param  string   $ident          - internal ident
     * @param  boolean  $force          - Bypass caching
     * @return ElcaProcessConversion
     */
    public static function findByProcessConfigIdAndInOut($processConfigId, $in, $out, $checkInversion = false, $force = false)
    {
        if(!$processConfigId || !$in || !$out)
            return new ElcaProcessConversion();

        if ($checkInversion) {
            $condition = '(:in, :out) IN ((in_unit, out_unit), (out_unit, in_unit))';
        } else {
            $condition = '(in_unit, out_unit) = (:in, :out)';
        }

        $sql = sprintf("SELECT id
                              , process_config_id
                              , in_unit
                              , out_unit
                              , created
                              , modified
                           FROM %s
                          WHERE process_config_id = :processConfigId
                            AND %s"
                       , self::TABLE_NAME
                       , $condition
                       );

        return self::findBySql(get_class(), $sql, ['processConfigId' => $processConfigId, 'in' => $in, 'out' => $out], $force);
    }
    // End findByProcessConfigIdAndIdent

    /**
     * @param $newProcessConfigId
     * @return ElcaProcessConversion
     */
    public function copy($newProcessConfigId)
    {
        return self::create(
            $newProcessConfigId,
            $this->inUnit,
            $this->outUnit
        );
    }

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
     * Sets the property inUnit
     *
     * @param  string   $inUnit - input unit of measure
     * @return
     */
    public function setInUnit($inUnit)
    {
        if(!$this->getValidator()->assertNotEmpty('inUnit', $inUnit))
            return;

        if(!$this->getValidator()->assertMaxLength('inUnit', 10, $inUnit))
            return;

        $this->inUnit = (string)$inUnit;
    }
    // End setInUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property outUnit
     *
     * @param  string   $outUnit - output unit of measure
     * @return
     */
    public function setOutUnit($outUnit)
    {
        if(!$this->getValidator()->assertNotEmpty('outUnit', $outUnit))
            return;

        if(!$this->getValidator()->assertMaxLength('outUnit', 10, $outUnit))
            return;

        $this->outUnit = (string)$outUnit;
    }
    // End setOutUnit

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
     * Returns the property inUnit
     *
     * @return string
     */
    public function getInUnit()
    {
        return $this->inUnit;
    }
    // End getInUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property outUnit
     *
     * @return string
     */
    public function getOutUnit()
    {
        return $this->outUnit;
    }
    // End getOutUnit

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


    public function getVersionFor(ProcessDbId $processDbId) : ElcaProcessConversionVersion
    {
        return ElcaProcessConversionVersion::findByPK($this->id, $processDbId->value());
    }

    /**
     * Returns true if this conversion is associated with element components
     *
     * @return boolean
     */
    public function isInUse()
    {
        return (bool)ElcaElementComponentSet::findByProcessConversionId($this->getId(), array(), null, 1)->count();
    }
    // End isInUse

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns true if this conversion converts nothing
     *
     * @return boolean
     */
    public function isIdentity()
    {
        return ($this->inUnit === $this->outUnit);
    }

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
                             , in_unit         = :inUnit
                             , out_unit        = :outUnit
                             , created         = :created
                             , modified        = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  [
                                      'id'              => $this->id,
                                      'processConfigId' => $this->processConfigId,
                                      'inUnit'          => $this->inUnit,
                                      'outUnit'         => $this->outUnit,
                                      'created'         => $this->created,
                                      'modified'        => $this->modified
                                  ]
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

        $sql = sprintf("INSERT INTO %s (id, process_config_id, in_unit, out_unit,created, modified)
                               VALUES  (:id, :processConfigId, :inUnit, :outUnit, :created, :modified)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'inUnit'         => $this->inUnit,
                                        'outUnit'        => $this->outUnit,
                                        'created'        => $this->created,
                                        'modified'       => $this->modified)
                                  );
    }
    // End insert

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $dataObject - Data object
     */
    protected function initByDataObject(\stdClass $dataObject = null)
    {
        if (null === $dataObject) {
            return;
        }

        $this->id              = (int)$dataObject->id;
        $this->processConfigId = (int)$dataObject->process_config_id;
        $this->inUnit          = $dataObject->in_unit;
        $this->outUnit         = $dataObject->out_unit;
        $this->created         = $dataObject->created;
        $this->modified        = $dataObject->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessConversion