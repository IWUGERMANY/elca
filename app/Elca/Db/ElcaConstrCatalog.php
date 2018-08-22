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
 * @translate db Elca\Db\ElcaConstrCatalogSet::find() name
 */
class ElcaConstrCatalog extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.constr_catalogs';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * constrCatalogId
     */
    private $id;

    /**
     * name of catalog
     */
    private $name;

    /**
     * internal short name
     */
    private $ident;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'name'           => PDO::PARAM_STR,
                                        'ident'          => PDO::PARAM_STR);

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
     * @param  string   $name - name of catalog
     * @param  string   $ident - internal short name
     */
    public static function create($name, $ident = null)
    {
        $ElcaConstrCatalog = new ElcaConstrCatalog();
        $ElcaConstrCatalog->setName($name);
        $ElcaConstrCatalog->setIdent($ident);

        if($ElcaConstrCatalog->getValidator()->isValid())
            $ElcaConstrCatalog->insert();

        return $ElcaConstrCatalog;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaConstrCatalog' by its primary key
     *
     * @param  integer  $id    - constrCatalogId
     * @param  boolean  $force - Bypass caching
     * @return ElcaConstrCatalog
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaConstrCatalog();

        $sql = sprintf("SELECT id
                             , name
                             , ident
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaConstrCatalog' by its ident
     *
     * @param  string   $ident
     * @param  boolean  $force - Bypass caching
     * @return ElcaConstrCatalog
     */
    public static function findByIdent($ident, $force = false)
    {
        $sql = sprintf("SELECT id
                             , name
                             , ident
                          FROM %s
                         WHERE ident = :ident"
                       , self::TABLE_NAME
                       );


        return self::findBySql(get_class(), $sql, array('ident' => (string)$ident), $force);
    }
    // End findByIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaConstrCatalog' by its name
     *
     * @param  string   $name
     * @param  boolean  $force - Bypass caching
     * @return ElcaConstrCatalog
     */
    public static function findByName($name, $force = false)
    {
        if(!$name)
            return new ElcaConstrCatalog();

        $sql = sprintf("SELECT id
                             , name
                             , ident
                          FROM %s
                         WHERE name = :name"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('name' => $name), $force);
    }
    // End findByName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property name
     *
     * @param  string   $name  - name of catalog
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
     * Sets the property ident
     *
     * @param  string   $ident - internal short name
     * @return
     */
    public function setIdent($ident = null)
    {
        if(!$this->getValidator()->assertMaxLength('ident', 100, $ident))
            return;

        $this->ident = $ident;
    }
    // End setIdent

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
     * Checks, if the object exists
     *
     * @param  integer  $id    - constrCatalogId
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
                             , ident          = :ident
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'name'          => $this->name,
                                        'ident'         => $this->ident)
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

        $sql = sprintf("INSERT INTO %s (id, name, ident)
                               VALUES  (:id, :name, :ident)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'name'          => $this->name,
                                        'ident'         => $this->ident)
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
        $this->ident          = $DO->ident;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaConstrCatalog