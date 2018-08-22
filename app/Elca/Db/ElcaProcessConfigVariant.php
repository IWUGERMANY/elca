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
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      ElcaProcessConfigVariant
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 *
 * $Id$
 */
class ElcaProcessConfigVariant extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_config_variants';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * processConfigId
     */
    private $processConfigId;

    /**
     * uuid
     */
    private $uuid;

    /**
     * name
     */
    private $name;

    /**
     * reference value
     */
    private $refValue;

    /**
     * unit of the reference value
     */
    private $refUnit;

    /**
     * indicates a vendor specific product
     */
    private $isVendorSpecific;

    /**
     * reference to specific process config
     */
    private $specificProcessConfigId;

    /**
     * Primary key
     */
    private static $primaryKey = array('processConfigId', 'uuid');

    /**
     * Column types
     */
    private static $columnTypes = array('processConfigId' => PDO::PARAM_INT,
                                        'uuid'            => PDO::PARAM_STR,
                                        'name'            => PDO::PARAM_STR,
                                        'refValue'        => PDO::PARAM_STR,
                                        'refUnit'                 => PDO::PARAM_STR,
                                        'isVendorSpecific'        => PDO::PARAM_BOOL,
                                        'specificProcessConfigId' => PDO::PARAM_INT);

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
     * @param  string   $uuid           - uuid
     * @param  string   $name           - name
     * @param  string   $refUnit        - unit of the reference value
     * @param  number  $refValue       - reference value
     */
    public static function create($processConfigId, $uuid, $name, $refUnit, $refValue = 1, $isVendorSpecific = false, $specificProcessConfigId = null)
    {
        $ElcaProcessConfigVariant = new ElcaProcessConfigVariant();
        $ElcaProcessConfigVariant->setProcessConfigId($processConfigId);
        $ElcaProcessConfigVariant->setUuid($uuid);
        $ElcaProcessConfigVariant->setName($name);
        $ElcaProcessConfigVariant->setRefUnit($refUnit);
        $ElcaProcessConfigVariant->setRefValue($refValue);
        $ElcaProcessConfigVariant->setIsVendorSpecific($isVendorSpecific);
        $ElcaProcessConfigVariant->setSpecificProcessConfigId($specificProcessConfigId);

        if($ElcaProcessConfigVariant->getValidator()->isValid())
            $ElcaProcessConfigVariant->insert();

        return $ElcaProcessConfigVariant;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessConfigVariant' by its primary key
     *
     * @param  integer  $processConfigId - processConfigId
     * @param  string   $uuid           - uuid
     * @param  boolean  $force          - Bypass caching
     * @return ElcaProcessConfigVariant
     */
    public static function findByPk($processConfigId, $uuid, $force = false)
    {
        if(!$processConfigId || !$uuid)
            return new ElcaProcessConfigVariant();

        $sql = sprintf("SELECT process_config_id
                             , uuid
                             , name
                             , ref_value
                             , ref_unit
                             , is_vendor_specific
                             , specific_process_config_id
                          FROM %s
                         WHERE process_config_id = :processConfigId
                           AND uuid = :uuid"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('processConfigId' => $processConfigId, 'uuid' => $uuid), $force);
    }
    // End findByPk

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
     * Sets the property uuid
     *
     * @param  string   $uuid  - uuid
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
     * Sets the property refValue
     *
     * @param  number  $refValue - reference value
     * @return
     */
    public function setRefValue($refValue = 1)
    {
        $this->refValue = $refValue;
    }
    // End setRefValue

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refUnit
     *
     * @param  string   $refUnit - unit of the reference value
     * @return
     */
    public function setRefUnit($refUnit)
    {
        if(!$this->getValidator()->assertNotEmpty('refUnit', $refUnit))
            return;

        if(!$this->getValidator()->assertMaxLength('refUnit', 10, $refUnit))
            return;

        $this->refUnit = (string)$refUnit;
    }
    // End setRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property isVendorSpecific
     *
     * @param  boolean  $isVendorSpecific - indicates a vendor specific product
     * @return
     */
    public function setIsVendorSpecific($isVendorSpecific = false)
    {
        $this->isVendorSpecific = (bool)$isVendorSpecific;
    }
    // End setIsVendorSpecific

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property specificProcessConfigId
     *
     * @param  integer  $specificProcessConfigId - reference to specific process config
     * @return
     */
    public function setSpecificProcessConfigId($specificProcessConfigId = null)
    {
        $this->specificProcessConfigId = $specificProcessConfigId;
    }
    // End setSpecificProcessConfigId

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
     * Returns the property refValue
     *
     * @return number
     */
    public function getRefValue()
    {
        return $this->refValue;
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
     * Returns the property isVendorSpecific
     *
     * @return boolean
     */
    public function isVendorSpecific()
    {
        return $this->isVendorSpecific;
    }
    // End isVendorSpecific

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property specificProcessConfigId
     *
     * @return integer
     */
    public function getSpecificProcessConfigId()
    {
        return $this->specificProcessConfigId;
    }
    // End getSpecificProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcessConfig by property specificProcessConfigId
     *
     * @param  boolean  $force
     * @return ElcaProcessConfig
     */
    public function getSpecificProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->specificProcessConfigId, $force);
    }
    // End getSpecificProcessConfig

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $processConfigId - processConfigId
     * @param  string   $uuid           - uuid
     * @param  boolean  $force          - Bypass caching
     * @return boolean
     */
    public static function exists($processConfigId, $uuid, $force = false)
    {
        return self::findByPk($processConfigId, $uuid, $force)->isInitialized();
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
                           SET name            = :name
                             , ref_value       = :refValue
                             , ref_unit        = :refUnit
                             , is_vendor_specific      = :isVendorSpecific
                             , specific_process_config_id = :specificProcessConfigId
                         WHERE process_config_id = :processConfigId
                           AND uuid = :uuid"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('processConfigId' => $this->processConfigId,
                                        'uuid'           => $this->uuid,
                                        'name'           => $this->name,
                                        'refValue'       => $this->refValue,
                                        'refUnit'                => $this->refUnit,
                                        'isVendorSpecific'       => $this->isVendorSpecific,
                                        'specificProcessConfigId' => $this->specificProcessConfigId)
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
                              WHERE process_config_id = :processConfigId
                                AND uuid = :uuid"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('processConfigId' => $this->processConfigId, 'uuid' => $this->uuid));
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

        $sql = sprintf("INSERT INTO %s (process_config_id, uuid, name, ref_value, ref_unit, is_vendor_specific, specific_process_config_id)
                               VALUES  (:processConfigId, :uuid, :name, :refValue, :refUnit, :isVendorSpecific, :specificProcessConfigId)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('processConfigId'        => $this->processConfigId,
                                        'uuid'                   => $this->uuid,
                                        'name'                   => $this->name,
                                        'refValue'               => $this->refValue,
                                        'refUnit'                => $this->refUnit,
                                        'isVendorSpecific'       => $this->isVendorSpecific,
                                        'specificProcessConfigId' => $this->specificProcessConfigId)
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
        $this->processConfigId = (int)$DO->process_config_id;
        $this->uuid            = $DO->uuid;
        $this->name            = $DO->name;
        $this->refValue        = $DO->ref_value;
        $this->refUnit         = $DO->ref_unit;
        $this->isVendorSpecific        = (bool)$DO->is_vendor_specific;
        $this->specificProcessConfigId = $DO->specific_process_config_id;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessConfigVariant