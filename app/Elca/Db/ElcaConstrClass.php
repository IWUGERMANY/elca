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
 * @translate db Elca\Db\ElcaConstrClassSet::find() name
 *
 */
class ElcaConstrClass extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.constr_classes';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * constrClassId
     */
    private $id;

    /**
     * name
     */
    private $name;

    /**
     * reference number
     */
    private $refNum;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'name'           => PDO::PARAM_STR,
                                        'refNum'         => PDO::PARAM_INT);

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
     * @param  string   $name  - name
     * @param  integer  $refNum - reference number
     */
    public static function create($name, $refNum)
    {
        $ElcaConstrClass = new ElcaConstrClass();
        $ElcaConstrClass->setName($name);
        $ElcaConstrClass->setRefNum($refNum);
        
        if($ElcaConstrClass->getValidator()->isValid())
            $ElcaConstrClass->insert();
        
        return $ElcaConstrClass;
    }
    // End create
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaConstrClass' by its primary key
     *
     * @param  integer  $id    - constrClassId
     * @param  boolean  $force - Bypass caching
     * @return ElcaConstrClass
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaConstrClass();
        
        $sql = sprintf("SELECT id
                             , name
                             , ref_num
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaConstrClass' by its unique key (refNum)
     *
     * @param  integer  $refNum - reference number
     * @param  boolean  $force - Bypass caching
     * @return ElcaConstrClass
     */
    public static function findByRefNum($refNum, $force = false)
    {
        if(!$refNum)
            return new ElcaConstrClass();
        
        $sql = sprintf("SELECT id
                             , name
                             , ref_num
                          FROM %s
                         WHERE ref_num = :refNum"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('refNum' => $refNum), $force);
    }
    // End findByRefNum
    
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
        
        $this->name = (string)$name;
    }
    // End setName
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refNum
     *
     * @param  integer  $refNum - reference number
     * @return 
     */
    public function setRefNum($refNum)
    {
        if(!$this->getValidator()->assertNotEmpty('refNum', $refNum))
            return;
        
        $this->refNum = (int)$refNum;
    }
    // End setRefNum
    
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
     * Returns the property refNum
     *
     * @return integer
     */
    public function getRefNum()
    {
        return $this->refNum;
    }
    // End getRefNum
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - constrClassId
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
                           SET name           = :name
                             , ref_num        = :refNum
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'name'          => $this->name,
                                        'refNum'        => $this->refNum)
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
        
        $sql = sprintf("INSERT INTO %s (id, name, ref_num)
                               VALUES  (:id, :name, :refNum)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'name'          => $this->name,
                                        'refNum'        => $this->refNum)
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
        $this->name           = $DO->name;
        $this->refNum         = (int)$DO->ref_num;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaConstrClass