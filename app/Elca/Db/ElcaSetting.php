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
 * @class      ElcaSetting
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class ElcaSetting extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.settings';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * settingId
     */
    private $id;

    /**
     * section name
     */
    private $section;

    /**
     * setting identifier
     */
    private $ident;

    /**
     * caption
     */
    private $caption;

    /**
     * number value
     */
    private $numericValue;

    /**
     * text value
     */
    private $textValue;

    /**
     * presentation order
     */
    private $pOrder;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'section'        => PDO::PARAM_STR,
                                        'ident'          => PDO::PARAM_STR,
                                        'caption'        => PDO::PARAM_STR,
                                        'numericValue'   => PDO::PARAM_STR,
                                        'textValue'      => PDO::PARAM_STR,
                                        'pOrder'         => PDO::PARAM_INT);

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
     * @param  string   $section     - section name
     * @param  string   $ident       - setting identifier
     * @param  string   $caption     - caption
     * @param  number  $numericValue - number value
     * @param  string   $textValue   - text value
     * @param  integer  $pOrder      - presentation order
     */
    public static function create($section, $ident, $caption = null, $numericValue = null, $textValue = null, $pOrder = null)
    {
        $ElcaSetting = new ElcaSetting();
        $ElcaSetting->setSection($section);
        $ElcaSetting->setIdent($ident);
        $ElcaSetting->setCaption($caption);
        $ElcaSetting->setNumericValue($numericValue);
        $ElcaSetting->setTextValue($textValue);
        $ElcaSetting->setPOrder($pOrder);
        
        if($ElcaSetting->getValidator()->isValid())
            $ElcaSetting->insert();
        
        return $ElcaSetting;
    }
    // End create
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaSetting' by its primary key
     *
     * @param  integer  $id    - settingId
     * @param  boolean  $force - Bypass caching
     * @return ElcaSetting
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaSetting();
        
        $sql = sprintf("SELECT id
                             , section
                             , ident
                             , caption
                             , numeric_value
                             , text_value
                             , p_order
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaSetting' by its unique key (section, ident)
     *
     * @param  string   $section - section name
     * @param  string   $ident  - setting identifier
     * @param  boolean  $force  - Bypass caching
     * @return ElcaSetting
     */
    public static function findBySectionAndIdent($section, $ident, $force = false)
    {
        if(!$section || !$ident)
            return new ElcaSetting();
        
        $sql = sprintf("SELECT id
                             , section
                             , ident
                             , caption
                             , numeric_value
                             , text_value
                             , p_order
                          FROM %s
                         WHERE section = :section
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('section' => $section, 'ident' => $ident), $force);
    }
    // End findBySectionAndIdent
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property section
     *
     * @param  string   $section - section name
     * @return 
     */
    public function setSection($section)
    {
        if(!$this->getValidator()->assertNotEmpty('section', $section))
            return;
        
        if(!$this->getValidator()->assertMaxLength('section', 250, $section))
            return;
        
        $this->section = (string)$section;
    }
    // End setSection
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ident
     *
     * @param  string   $ident - setting identifier
     * @return 
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;
        
        if(!$this->getValidator()->assertMaxLength('ident', 250, $ident))
            return;
        
        $this->ident = (string)$ident;
    }
    // End setIdent
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property caption
     *
     * @param  string   $caption - caption
     * @return 
     */
    public function setCaption($caption = null)
    {
        if(!$this->getValidator()->assertMaxLength('caption', 250, $caption))
            return;
        
        $this->caption = $caption;
    }
    // End setCaption
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property numericValue
     *
     * @param  number  $numericValue - number value
     * @return 
     */
    public function setNumericValue($numericValue = null)
    {
        $this->numericValue = $numericValue;
    }
    // End setNumericValue
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property textValue
     *
     * @param  string   $textValue - text value
     * @return 
     */
    public function setTextValue($textValue = null)
    {
        $this->textValue = $textValue;
    }
    // End setTextValue
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property pOrder
     *
     * @param  integer  $pOrder - presentation order
     * @return 
     */
    public function setPOrder($pOrder = null)
    {
        $this->pOrder = $pOrder;
    }
    // End setPOrder
    
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
     * Returns the property section
     *
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }
    // End getSection
    
    //////////////////////////////////////////////////////////////////////////////////////

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
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }
    // End getCaption
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property numericValue
     *
     * @return number
     */
    public function getNumericValue()
    {
        return $this->numericValue;
    }
    // End getNumericValue
    
    //////////////////////////////////////////////////////////////////////////////////////

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
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property pOrder
     *
     * @return integer
     */
    public function getPOrder()
    {
        return $this->pOrder;
    }
    // End getPOrder
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - settingId
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
        $sql = sprintf("UPDATE %s
                           SET section        = :section
                             , ident          = :ident
                             , caption        = :caption
                             , numeric_value  = :numericValue
                             , text_value     = :textValue
                             , p_order        = :pOrder
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'section'       => $this->section,
                                        'ident'         => $this->ident,
                                        'caption'       => $this->caption,
                                        'numericValue'  => $this->numericValue,
                                        'textValue'     => $this->textValue,
                                        'pOrder'        => $this->pOrder)
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
        $this->id             = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, section, ident, caption, numeric_value, text_value, p_order)
                               VALUES  (:id, :section, :ident, :caption, :numericValue, :textValue, :pOrder)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'section'       => $this->section,
                                        'ident'         => $this->ident,
                                        'caption'       => $this->caption,
                                        'numericValue'  => $this->numericValue,
                                        'textValue'     => $this->textValue,
                                        'pOrder'        => $this->pOrder)
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
        $this->id             = (int)$DO->id;
        $this->section        = $DO->section;
        $this->ident          = $DO->ident;
        $this->caption        = $DO->caption;
        $this->numericValue   = $DO->numeric_value;
        $this->textValue      = $DO->text_value;
        $this->pOrder         = $DO->p_order;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaSetting