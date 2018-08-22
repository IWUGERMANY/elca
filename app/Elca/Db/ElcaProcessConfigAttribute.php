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
 * ProcessConfig attributes
 *
 * @package    elca
 * @class      ElcaProcessConfigAttribute
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaProcessConfigAttribute extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_config_attributes';
    const IDENT_TRANSPORT_PAYLOAD = 'transport.payload';
    const IDENT_TRANSPORT_EFFICIENCY = 'transport.efficiency';
    const IDENT_OP_AS_SUPPLY = 'op.asSupply';
    const IDENT_OP_INVERT_VALUES = 'op.invertValues';
    const IDENT_4108_COMPAT = '4108_compat';

    /**
     * processConfigAttributeId
     */
    private $id;

    /**
     * processConfigId
     */
    private $processConfigId;

    /**
     * attribute identifier
     */
    private $ident;

    /**
     * number value
     */
    private $numericValue;

    /**
     * text value
     */
    private $textValue;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'              => PDO::PARAM_INT,
                                        'processConfigId' => PDO::PARAM_INT,
                                        'ident'           => PDO::PARAM_STR,
                                        'numericValue'    => PDO::PARAM_STR,
                                        'textValue'       => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Returns the value for an attribute
     *
     * @param int  $processConfigId
     * @param string $ident
     * @param bool $force
     * @return mixed
     */
    public static function findValue($processConfigId, $ident, $force = false)
    {
        $Attribute = ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent($processConfigId, $ident, $force);

        if ($Attribute->isInitialized())
            return is_null($Attribute->numericValue)? $Attribute->textValue : $Attribute->numericValue;

        return null;
    }
    // End findValue

    /**
     * Sets and updated the value for an attribute
     *
     * @param int    $processConfigId
     * @param string $ident
     * @param mixed  $value
     * @param bool   $forceTextValue
     * @return ElcaProcessConfigAttribute
     */
    public static function updateValue($processConfigId, $ident, $value, $forceTextValue = false)
    {
        $textValue = $numValue = null;

        if ($forceTextValue || !is_numeric($value))
            $textValue = $value;
        else
            $numValue = $value;

        $Attribute = ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent($processConfigId, $ident);

        if ($Attribute->isInitialized()) {
            $Attribute->setTextValue($textValue);
            $Attribute->setNumericValue($numValue);
            $Attribute->update();
        } else {
            $Attribute = ElcaProcessConfigAttribute::create($processConfigId, $ident, $numValue, $textValue);
        }
        return $Attribute;
    }
    // End updateValue


    /**
     * Creates the object
     *
     * @param  int      $processConfigId - processConfigId
     * @param  string   $ident          - attribute identifier
     * @param  string   $textValue      - text value
     * @return ElcaProcessConfigAttribute
     */
    public static function create($processConfigId, $ident, $numericValue = null, $textValue = null)
    {
        $ElcaProcessConfigAttribute = new ElcaProcessConfigAttribute();
        $ElcaProcessConfigAttribute->setProcessConfigId($processConfigId);
        $ElcaProcessConfigAttribute->setIdent($ident);
        $ElcaProcessConfigAttribute->setNumericValue($numericValue);
        $ElcaProcessConfigAttribute->setTextValue($textValue);
        
        if($ElcaProcessConfigAttribute->getValidator()->isValid())
            $ElcaProcessConfigAttribute->insert();
        return $ElcaProcessConfigAttribute;
    }
    // End create
    

    /**
     * Inits a `ElcaProcessConfigAttribute' by its primary key
     *
     * @param  int      $id    - processConfigAttributeId
     * @param  bool     $force - Bypass caching
     * @return ElcaProcessConfigAttribute
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProcessConfigAttribute();
        
        $sql = sprintf("SELECT id
                             , process_config_id
                             , ident
                             , numeric_value
                             , text_value
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    

    /**
     * Inits a `ElcaProcessConfigAttribute' by its unique key (processConfigId, ident)
     *
     * @param  int      $processConfigId - processConfigId
     * @param  string   $ident          - attribute identifier
     * @param  bool     $force          - Bypass caching
     * @return ElcaProcessConfigAttribute
     */
    public static function findByProcessConfigIdAndIdent($processConfigId, $ident, $force = false)
    {
        if(!$processConfigId || !$ident)
            return new ElcaProcessConfigAttribute();
        
        $sql = sprintf("SELECT id
                             , process_config_id
                             , ident
                             , numeric_value
                             , text_value
                          FROM %s
                         WHERE process_config_id = :processConfigId
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('processConfigId' => $processConfigId, 'ident' => $ident), $force);
    }
    // End findByProcessConfigIdAndIdent
    

    /**
     * Sets the property processConfigId
     *
     * @param  int      $processConfigId - processConfigId
     * @return void
     */
    public function setProcessConfigId($processConfigId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId))
            return;
        
        $this->processConfigId = (int)$processConfigId;
    }
    // End setProcessConfigId
    

    /**
     * Sets the property ident
     *
     * @param  string   $ident - attribute identifier
     * @return void
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;
        
        if(!$this->getValidator()->assertMaxLength('ident', 150, $ident))
            return;
        
        $this->ident = (string)$ident;
    }
    // End setIdent
    

    /**
     * Sets the property numericValue
     *
     * @param  float    $numericValue - number value
     * @return void
     */
    public function setNumericValue($numericValue = null)
    {
        $this->numericValue = $numericValue;
    }
    // End setNumericValue
    

    /**
     * Sets the property textValue
     *
     * @param  string   $textValue - text value
     * @return void
     */
    public function setTextValue($textValue = null)
    {
        $this->textValue = $textValue;
    }
    // End setTextValue
    

    /**
     * Returns the property id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    // End getId
    

    /**
     * Returns the property processConfigId
     *
     * @return int
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId
    

    /**
     * Returns the associated ElcaProcessConfig by property processConfigId
     *
     * @param  bool     $force
     * @return ElcaProcessConfig
     */
    public function getProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->processConfigId, $force);
    }
    // End getProcessConfig
    

    /**
     * Returns the property ident
     *
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getIdent
    

    /**
     * Returns the property numericValue
     *
     * @return float
     */
    public function getNumericValue()
    {
        return $this->numericValue;
    }
    // End getNumericValue
    

    /**
     * Returns the property textValue
     *
     * @return string
     */
    public function getTextValue()
    {
        return $this->textValue;
    }
    // End getTextValue
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - processConfigAttributeId
     * @param  bool     $force - Bypass caching
     * @return bool
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End exists
    

    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET process_config_id = :processConfigId
                             , ident           = :ident
                             , numeric_value   = :numericValue
                             , text_value      = :textValue
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'ident'          => $this->ident,
                                        'numericValue'   => $this->numericValue,
                                        'textValue'      => $this->textValue)
                                  );
    }
    // End update
    

    /**
     * Deletes the object from the table
     *
     * @return bool
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
    

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  bool     $propertiesOnly
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
    

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  bool     $extColumns
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

    /**
     * Inserts a new object in the table
     *
     * @return bool
     */
    protected function insert()
    {
        $this->id              = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, process_config_id, ident, numeric_value, text_value)
                               VALUES  (:id, :processConfigId, :ident, :numericValue, :textValue)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'ident'          => $this->ident,
                                        'numericValue'   => $this->numericValue,
                                        'textValue'      => $this->textValue)
                                  );
    }
    // End insert
    

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return bool
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->id              = (int)$DO->id;
        $this->processConfigId = (int)$DO->process_config_id;
        $this->ident           = $DO->ident;
        $this->numericValue    = $DO->numeric_value;
        $this->textValue       = $DO->text_value;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessConfigAttribute