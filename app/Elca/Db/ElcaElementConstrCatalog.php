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
class ElcaElementConstrCatalog extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.element_constr_catalogs';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * elementId
     */
    private $elementId;

    /**
     * constrCatalogId
     */
    private $constrCatalogId;

    /**
     * Primary key
     */
    private static $primaryKey = array('elementId', 'constrCatalogId');

    /**
     * Column types
     */
    private static $columnTypes = array('elementId'       => PDO::PARAM_INT,
                                        'constrCatalogId' => PDO::PARAM_INT);

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
     * @param  integer  $elementId      - elementId
     * @param  integer  $constrCatalogId - constrCatalogId
     */
    public static function create($elementId, $constrCatalogId)
    {
        $ElcaElementConstrCatalog = new ElcaElementConstrCatalog();
        $ElcaElementConstrCatalog->setElementId($elementId);
        $ElcaElementConstrCatalog->setConstrCatalogId($constrCatalogId);
        
        if($ElcaElementConstrCatalog->getValidator()->isValid())
            $ElcaElementConstrCatalog->insert();
        
        return $ElcaElementConstrCatalog;
    }
    // End create
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaElementConstrCatalog' by its primary key
     *
     * @param  integer  $elementId      - elementId
     * @param  integer  $constrCatalogId - constrCatalogId
     * @param  boolean  $force          - Bypass caching
     * @return ElcaElementConstrCatalog
     */
    public static function findByPk($elementId, $constrCatalogId, $force = false)
    {
        if(!$elementId || !$constrCatalogId)
            return new ElcaElementConstrCatalog();
        
        $sql = sprintf("SELECT element_id
                             , constr_catalog_id
                          FROM %s
                         WHERE element_id = :elementId
                           AND constr_catalog_id = :constrCatalogId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('elementId' => $elementId, 'constrCatalogId' => $constrCatalogId), $force);
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
     * Sets the property constrCatalogId
     *
     * @param  integer  $constrCatalogId - constrCatalogId
     * @return 
     */
    public function setConstrCatalogId($constrCatalogId)
    {
        if(!$this->getValidator()->assertNotEmpty('constrCatalogId', $constrCatalogId))
            return;
        
        $this->constrCatalogId = (int)$constrCatalogId;
    }
    // End setConstrCatalogId
    
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
     * Returns the property constrCatalogId
     *
     * @return integer
     */
    public function getConstrCatalogId()
    {
        return $this->constrCatalogId;
    }
    // End getConstrCatalogId
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaConstrCatalog by property constrCatalogId
     *
     * @param  boolean  $force
     * @return ElcaConstrCatalog
     */
    public function getConstrCatalog($force = false)
    {
        return ElcaConstrCatalog::findById($this->constrCatalogId, $force);
    }
    // End getConstrCatalog
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $elementId      - elementId
     * @param  integer  $constrCatalogId - constrCatalogId
     * @param  boolean  $force          - Bypass caching
     * @return boolean
     */
    public static function exists($elementId, $constrCatalogId, $force = false)
    {
        return self::findByPk($elementId, $constrCatalogId, $force)->isInitialized();
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
                           SET                 = :
                         WHERE element_id = :elementId
                           AND constr_catalog_id = :constrCatalogId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('elementId'      => $this->elementId,
                                        'constrCatalogId' => $this->constrCatalogId)
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
                                AND constr_catalog_id = :constrCatalogId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('elementId' => $this->elementId, 'constrCatalogId' => $this->constrCatalogId));
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
        
        $sql = sprintf("INSERT INTO %s (element_id, constr_catalog_id)
                               VALUES  (:elementId, :constrCatalogId)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('elementId'      => $this->elementId,
                                        'constrCatalogId' => $this->constrCatalogId)
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
        $this->elementId       = (int)$DO->element_id;
        $this->constrCatalogId = (int)$DO->constr_catalog_id;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaElementConstrCatalog