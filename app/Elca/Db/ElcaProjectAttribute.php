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
 * Project attribute
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaProjectAttribute extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_attributes';

    /**
     * Idents
     */
    const IDENT_IS_LISTED = 'elca.IS_LISTED';
    const IDENT_BNB_NR = 'elca.bnb_nr';
    const IDENT_EGIS_NR = 'elca.egis_nr';
    const IDENT_PW_DATE = 'elca.pw.date';

    /**
     * All available idents
     */
    public static $idents = array(self::IDENT_IS_LISTED => 'Denkmalschutz',
                                  self::IDENT_BNB_NR => 'BNB Nummer',
                                  self::IDENT_EGIS_NR => 'eGis Nummer'
    );

    /**
     * projectAttributeId
     */
    private $id;

    /**
     * projectId
     */
    private $projectId;

    /**
     * attribute identifier
     */
    private $ident;

    /**
     * attribute caption
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
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'projectId'      => PDO::PARAM_INT,
                                        'ident'          => PDO::PARAM_STR,
                                        'caption'        => PDO::PARAM_STR,
                                        'numericValue'   => PDO::PARAM_STR,
                                        'textValue'      => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    /**
     * Returns the value for an attribute
     *
     * @param int  $projectId
     * @param string $ident
     * @param bool $force
     * @return mixed
     */
    public static function findValue($projectId, $ident, $force = false)
    {
        $Attribute = ElcaProjectAttribute::findByProjectIdAndIdent($projectId, $ident, $force);

        if ($Attribute->isInitialized())
            return is_null($Attribute->numericValue)? $Attribute->textValue : $Attribute->numericValue;

        return null;
    }
    // End findValue

    /**
     * Sets and updated the value for an attribute
     *
     * @param int    $projectId
     * @param string $ident
     * @param mixed  $value
     * @param bool   $forceTextValue
     * @param string   $caption
     * @return ElcaProjectAttribute
     */
    public static function updateValue($projectId, $ident, $value, $forceTextValue = false, $caption = null)
    {
        $textValue = $numValue = null;

        if ($forceTextValue || !is_numeric($value))
            $textValue = $value;
        else
            $numValue = $value;

        $Attribute = ElcaProjectAttribute::findByProjectIdAndIdent($projectId, $ident);

        if ($Attribute->isInitialized()) {
            $Attribute->setTextValue($textValue);
            $Attribute->setNumericValue($numValue);
            $Attribute->update();
        } else {
            $Attribute = ElcaProjectAttribute::create($projectId, $ident, $caption, $numValue, $textValue);
        }

        return $Attribute;
    }
    // End updateValue


    /**
     * Creates the object
     *
     * @param  integer $projectId    - projectId
     * @param  string  $ident        - attribute identifier
     * @param  string  $caption      - attribute caption
     * @param  number $numericValue - number value
     * @param  string  $textValue    - text value
     * @return ElcaProjectAttribute
     */
    public static function create($projectId, $ident, $caption = null, $numericValue = null, $textValue = null)
    {
        $ElcaProjectAttribute = new ElcaProjectAttribute();
        $ElcaProjectAttribute->setProjectId($projectId);
        $ElcaProjectAttribute->setIdent($ident);
        $ElcaProjectAttribute->setCaption($caption? $caption : (isset(self::$idents[$ident])? self::$idents[$ident] : ''));
        $ElcaProjectAttribute->setNumericValue($numericValue);
        $ElcaProjectAttribute->setTextValue($textValue);
        
        if($ElcaProjectAttribute->getValidator()->isValid())
            $ElcaProjectAttribute->insert();
        
        return $ElcaProjectAttribute;
    }
    // End create
    


    /**
     * Inits a `ElcaProjectAttribute' by its primary key
     *
     * @param  integer  $id    - projectAttributeId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProjectAttribute
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectAttribute();
        
        $sql = sprintf("SELECT id
                             , project_id
                             , ident
                             , caption
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
     * Inits a `ElcaProjectAttribute' by its unique key (projectId, ident)
     *
     * @param  integer  $projectId - projectId
     * @param  string   $ident    - attribute identifier
     * @param  boolean  $force    - Bypass caching
     * @return ElcaProjectAttribute
     */
    public static function findByProjectIdAndIdent($projectId, $ident, $force = false)
    {
        if(!$projectId || !$ident)
            return new ElcaProjectAttribute();
        
        $sql = sprintf("SELECT id
                             , project_id
                             , ident
                             , caption
                             , numeric_value
                             , text_value
                          FROM %s
                         WHERE project_id = :projectId
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('projectId' => $projectId, 'ident' => $ident), $force);
    }
    // End findByProjectIdAndIdent
    


    /**
     * Sets the property projectId
     *
     * @param  integer  $projectId - projectId
     * @return 
     */
    public function setProjectId($projectId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectId', $projectId))
            return;
        
        $this->projectId = (int)$projectId;
    }
    // End setProjectId
    


    /**
     * Sets the property ident
     *
     * @param  string   $ident - attribute identifier
     * @return 
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
     * Sets the property caption
     *
     * @param  string   $caption - attribute caption
     * @return 
     */
    public function setCaption($caption)
    {
        if(!$this->getValidator()->assertNotEmpty('caption', $caption))
            return;
        
        if(!$this->getValidator()->assertMaxLength('caption', 150, $caption))
            return;
        
        $this->caption = (string)$caption;
    }
    // End setCaption
    


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
    


    /**
     * Returns the property projectId
     *
     * @return integer
     */
    public function getProjectId()
    {
        return $this->projectId;
    }
    // End getProjectId
    


    /**
     * Returns the associated ElcaProject by property projectId
     *
     * @param  boolean  $force
     * @return ElcaProject
     */
    public function getProject($force = false)
    {
        return ElcaProject::findById($this->projectId, $force);
    }
    // End getProject
    


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
     * Returns the property caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }
    // End getCaption
    


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
     *
     */
    public function getValue()
    {
        return is_null($this->numericValue)? $this->textValue : $this->numericValue;
    }

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - projectAttributeId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End exists
    


    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET project_id     = :projectId
                             , ident          = :ident
                             , caption        = :caption
                             , numeric_value  = :numericValue
                             , text_value     = :textValue
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'projectId'     => $this->projectId,
                                        'ident'         => $this->ident,
                                        'caption'       => $this->caption,
                                        'numericValue'  => $this->numericValue,
                                        'textValue'     => $this->textValue)
                                  );
    }
    // End update
    


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


    // protected


    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $this->id             = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, project_id, ident, caption, numeric_value, text_value)
                               VALUES  (:id, :projectId, :ident, :caption, :numericValue, :textValue)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'projectId'     => $this->projectId,
                                        'ident'         => $this->ident,
                                        'caption'       => $this->caption,
                                        'numericValue'  => $this->numericValue,
                                        'textValue'     => $this->textValue)
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
        $this->id             = (int)$DO->id;
        $this->projectId      = (int)$DO->project_id;
        $this->ident          = $DO->ident;
        $this->caption        = $DO->caption;
        $this->numericValue   = $DO->numeric_value;
        $this->textValue      = $DO->text_value;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectAttribute