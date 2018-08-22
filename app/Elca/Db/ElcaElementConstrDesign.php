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
class ElcaElementConstrDesign extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.element_constr_designs';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * elementId
     */
    private $elementId;

    /**
     * constrDesignId
     */
    private $constrDesignId;

    /**
     * Primary key
     */
    private static $primaryKey = array('elementId', 'constrDesignId');

    /**
     * Column types
     */
    private static $columnTypes = array('elementId'      => PDO::PARAM_INT,
                                        'constrDesignId' => PDO::PARAM_INT);

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
     * @param  integer  $elementId     - elementId
     * @param  integer  $constrDesignId - constrDesignId
     */
    public static function create($elementId, $constrDesignId)
    {
        $ElcaElementConstrDesign = new ElcaElementConstrDesign();
        $ElcaElementConstrDesign->setElementId($elementId);
        $ElcaElementConstrDesign->setConstrDesignId($constrDesignId);
        
        if($ElcaElementConstrDesign->getValidator()->isValid())
            $ElcaElementConstrDesign->insert();
        
        return $ElcaElementConstrDesign;
    }
    // End create
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaElementConstrDesign' by its primary key
     *
     * @param  integer  $elementId     - elementId
     * @param  integer  $constrDesignId - constrDesignId
     * @param  boolean  $force         - Bypass caching
     * @return ElcaElementConstrDesign
     */
    public static function findByPk($elementId, $constrDesignId, $force = false)
    {
        if(!$elementId || !$constrDesignId)
            return new ElcaElementConstrDesign();
        
        $sql = sprintf("SELECT element_id
                             , constr_design_id
                          FROM %s
                         WHERE element_id = :elementId
                           AND constr_design_id = :constrDesignId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('elementId' => $elementId, 'constrDesignId' => $constrDesignId), $force);
    }
    // End findByPk
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property elementId
     *
     * @param  integer  $elementId - elementId
     * @return 
     */
    public function setElementId($elementId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementId', $elementId))
            return;
        
        $this->elementId = (int)$elementId;
    }
    // End setElementId
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property constrDesignId
     *
     * @param  integer  $constrDesignId - constrDesignId
     * @return 
     */
    public function setConstrDesignId($constrDesignId)
    {
        if(!$this->getValidator()->assertNotEmpty('constrDesignId', $constrDesignId))
            return;
        
        $this->constrDesignId = (int)$constrDesignId;
    }
    // End setConstrDesignId
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property elementId
     *
     * @return integer
     */
    public function getElementId()
    {
        return $this->elementId;
    }
    // End getElementId
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaElement by property elementId
     *
     * @param  boolean  $force
     * @return ElcaElement
     */
    public function getElement($force = false)
    {
        return ElcaElement::findById($this->elementId, $force);
    }
    // End getElement
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property constrDesignId
     *
     * @return integer
     */
    public function getConstrDesignId()
    {
        return $this->constrDesignId;
    }
    // End getConstrDesignId
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaConstrDesign by property constrDesignId
     *
     * @param  boolean  $force
     * @return ElcaConstrDesign
     */
    public function getConstrDesign($force = false)
    {
        return ElcaConstrDesign::findById($this->constrDesignId, $force);
    }
    // End getConstrDesign
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $elementId     - elementId
     * @param  integer  $constrDesignId - constrDesignId
     * @param  boolean  $force         - Bypass caching
     * @return boolean
     */
    public static function exists($elementId, $constrDesignId, $force = false)
    {
        return self::findByPk($elementId, $constrDesignId, $force)->isInitialized();
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
                           SET                = :
                         WHERE element_id = :elementId
                           AND constr_design_id = :constrDesignId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('elementId'     => $this->elementId,
                                        'constrDesignId' => $this->constrDesignId)
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
                              WHERE element_id = :elementId
                                AND constr_design_id = :constrDesignId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('elementId' => $this->elementId, 'constrDesignId' => $this->constrDesignId));
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
        
        $sql = sprintf("INSERT INTO %s (element_id, constr_design_id)
                               VALUES  (:elementId, :constrDesignId)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('elementId'     => $this->elementId,
                                        'constrDesignId' => $this->constrDesignId)
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
        $this->elementId      = (int)$DO->element_id;
        $this->constrDesignId = (int)$DO->constr_design_id;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaElementConstrDesign