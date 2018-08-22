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
class ElcaCacheItem extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.items';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * itemId
     */
    private $id;

    /**
     * parent item
     */
    private $parentId;

    /**
     * project id
     */
    private $projectId;

    /**
     * item type
     */
    private $type;

    /**
     * if it is outdated, it needs updating
     */
    private $isOutdated;

    /**
     * Virtual items will not be included in aggregation
     */
    private $isVirtual;

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
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'parentId'       => PDO::PARAM_INT,
                                        'projectId'      => PDO::PARAM_INT,
                                        'type'           => PDO::PARAM_STR,
                                        'isOutdated'     => PDO::PARAM_BOOL,
                                        'isVirtual'     => PDO::PARAM_BOOL,
                                        'created'        => PDO::PARAM_STR,
                                        'modified'       => PDO::PARAM_STR);

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
     * @param          $projectId
     * @param  string  $type       - item type
     * @param  integer $parentId   - parent item
     * @param bool     $isVirtual
     * @param  boolean $isOutdated - if it is outdated, it needs updating
     * @return ElcaCacheItem
     */
    public static function create($projectId, $type, $parentId = null, $isVirtual = false, $isOutdated = true)
    {
        $ElcaCacheItem = new ElcaCacheItem();
        $ElcaCacheItem->setProjectId($projectId);
        $ElcaCacheItem->setType($type);
        $ElcaCacheItem->setParentId($parentId);
        $ElcaCacheItem->setIsOutdated($isOutdated);
        $ElcaCacheItem->setIsVirtual($isVirtual);

        if($ElcaCacheItem->getValidator()->isValid())
            $ElcaCacheItem->insert();

        return $ElcaCacheItem;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaCacheItem' by its primary key
     *
     * @param  integer  $id    - itemId
     * @param  boolean  $force - Bypass caching
     * @return ElcaCacheItem
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaCacheItem();

        $sql = sprintf("SELECT id
                             , parent_id
                             , project_id
                             , type
                             , is_outdated
                             , is_virtual
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
     * Sets the property parentId
     *
     * @param  integer  $parentId - parent item
     * @return
     */
    public function setParentId($parentId = null)
    {
        $this->parentId = $parentId;
    }
    // End setParentId


    /**
     * Sets the property parentId
     *
     * @param  integer $projectId - parent item
     * @return
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }
    // End setProjectId


    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property type
     *
     * @param  string   $type  - item type
     * @return
     */
    public function setType($type)
    {
        if(!$this->getValidator()->assertNotEmpty('type', $type))
            return;

        if(!$this->getValidator()->assertMaxLength('type', 100, $type))
            return;

        $this->type = (string)$type;
    }
    // End setType

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property isOutdated
     *
     * @param  boolean  $isOutdated - if it is outdated, it needs updating
     * @return
     */
    public function setIsOutdated($isOutdated = false)
    {
        $this->isOutdated = (bool)$isOutdated;
    }
    // End setIsOutdated

    /**
     * Sets the property isVirtual
     *
     * @param  boolean  $isVirtual - if it is outdated, it needs updating
     */
    public function setIsVirtual($isVirtual = false)
    {
        $this->isVirtual = (bool)$isVirtual;
    }
    // End setIsVirtual

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
     * Returns the property parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @return mixed
     */
    public function getProjectId()
    {
        return $this->projectId;
    }


    /**
     * Returns the associated ElcaCacheItem by property parentId
     *
     * @param  boolean  $force
     * @return ElcaCacheItem
     */
    public function getParent($force = false)
    {
        return ElcaCacheItem::findById($this->parentId, $force);
    }
    // End getParent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    // End getType

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property isOutdated
     *
     * @return boolean
     */
    public function isOutdated()
    {
        return $this->isOutdated;
    }
    // End isOutdated

    /**
     * @return mixed
     */
    public function isVirtual()
    {
        return $this->isVirtual;
    }


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
     * @param  integer  $id    - itemId
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
                           SET parent_id      = :parentId
                             , project_id     = :projectId
                             , type           = :type
                             , is_outdated    = :isOutdated
                             , is_virtual     = :isVirtual
                             , created        = :created
                             , modified       = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'parentId'      => $this->parentId,
                                        'projectId'     => $this->projectId,
                                        'type'          => $this->type,
                                        'isOutdated'    => $this->isOutdated,
                                        'isVirtual'     => $this->isVirtual,
                                        'created'       => $this->created,
                                        'modified'      => $this->modified)
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
        $this->created        = self::getCurrentTime();
        $this->modified       = null;

        $sql = sprintf("INSERT INTO %s (id, parent_id, project_id, type, is_outdated, is_virtual, created, modified)
                               VALUES  (:id, :parentId, :projectId, :type, :isOutdated, :isVirtual, :created, :modified)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'parentId'      => $this->parentId,
                                        'projectId'     => $this->projectId,
                                        'type'          => $this->type,
                                        'isOutdated'    => $this->isOutdated,
                                        'isVirtual'     => $this->isVirtual,
                                        'created'       => $this->created,
                                        'modified'      => $this->modified)
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
        $this->parentId       = $DO->parent_id;
        $this->projectId      = $DO->project_id;
        $this->type           = $DO->type;
        $this->isOutdated     = (bool)$DO->is_outdated;
        $this->isVirtual      = (bool)$DO->is_virtual;
        $this->created        = $DO->created;
        $this->modified       = $DO->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheItem