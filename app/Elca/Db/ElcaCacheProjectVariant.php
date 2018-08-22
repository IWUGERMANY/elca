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
use Beibob\Blibs\DbHandle;

/**
 * ElcaCacheProjectVariants are the root cache items (in a hierarchy of other items)
 * bound to a project variant. Therefor there CacheItem's parentItem is always null.
 *
 * Ther hierarchical order is this
 *
 *                     projectVariant
 *                   /       |        \
 *   finalEnergieDemand  elementType  transportMean
 *                        /      \
 *               elementType300  elementType400 ...
 *                 /    |    \
 *               eT310 eT320 ...
 *               /    |    \
 *             eT311 eT312 ...
 *             /
 *          element ...
 *          /
 *  elementComponent ...
 *
 * @package    elca
 * @class      ElcaCacheProjectVariant
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaCacheProjectVariant extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.project_variants';

    /**
     * itemId
     */
    private $itemId;

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * Primary key
     */
    private static $primaryKey = array('itemId');

    /**
     * Column types
     */
    private static $columnTypes = array('itemId'           => PDO::PARAM_INT,
                                        'projectVariantId' => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    /**
     * Creates the object
     *
     * @param  int $projectVariantId - projectVariantId
     * @param  int $itemId           - itemId
     * @throws Exception
     * @return ElcaCacheProjectVariant
     */
    public static function create($projectVariantId, $itemId = null)
    {
        $Dbh = DbHandle::getInstance();

        try {
            $Dbh->begin();

            if (is_null($itemId)) {
                $projectId = ElcaProjectVariant::findById($projectVariantId)->getProjectId();
                $itemId = ElcaCacheItem::create($projectId, get_class())->getId();
            }

            $ElcaCacheProjectVariant = new ElcaCacheProjectVariant();
            $ElcaCacheProjectVariant->setItemId($itemId);
            $ElcaCacheProjectVariant->setProjectVariantId($projectVariantId);

            if ($ElcaCacheProjectVariant->getValidator()->isValid())
                $ElcaCacheProjectVariant->insert();

            $Dbh->commit();

        } catch (Exception $Exception) {
            $Dbh->rollback();
            throw $Exception;
        }

        return $ElcaCacheProjectVariant;
    }
    // End create
    

    /**
     * Inits a `ElcaCacheProjectVariant' by its primary key
     *
     * @param  int      $itemId - itemId
     * @param  bool     $force - Bypass caching
     * @return ElcaCacheProjectVariant
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheProjectVariant();
        
        $sql = sprintf("SELECT item_id
                             , project_variant_id
                          FROM %s
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('itemId' => $itemId), $force);
    }
    // End findByItemId
    

    /**
     * Inits a `ElcaCacheProjectVariant' by its unique key (projectVariantId)
     *
     * @param  int      $projectVariantId - projectVariantId
     * @param  bool     $force           - Bypass caching
     * @return ElcaCacheProjectVariant
     */
    public static function findByProjectVariantId($projectVariantId, $force = false)
    {
        if(!$projectVariantId)
            return new ElcaCacheProjectVariant();
        
        $sql = sprintf("SELECT item_id
                             , project_variant_id
                          FROM %s
                         WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId), $force);
    }
    // End findByProjectVariantId
    

    /**
     * Sets the property itemId
     *
     * @param  int      $itemId - itemId
     * @return void
     */
    public function setItemId($itemId)
    {
        if(!$this->getValidator()->assertNotEmpty('itemId', $itemId))
            return;
        
        $this->itemId = (int)$itemId;
    }
    // End setItemId
    

    /**
     * Sets the property projectVariantId
     *
     * @param  int      $projectVariantId - projectVariantId
     * @return void
     */
    public function setProjectVariantId($projectVariantId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId))
            return;
        
        $this->projectVariantId = (int)$projectVariantId;
    }
    // End setProjectVariantId
    

    /**
     * Returns the property itemId
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }
    // End getItemId
    

    /**
     * Returns the associated ElcaCacheItem by property itemId
     *
     * @param  bool     $force
     * @return ElcaCacheItem
     */
    public function getItem($force = false)
    {
        return ElcaCacheItem::findById($this->itemId, $force);
    }
    // End getItem
    

    /**
     * Returns the property projectVariantId
     *
     * @return int
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId
    

    /**
     * Returns the associated ElcaProjectVariant by property projectVariantId
     *
     * @param  bool     $force
     * @return ElcaProjectVariant
     */
    public function getProjectVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->projectVariantId, $force);
    }
    // End getProjectVariant
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $itemId - itemId
     * @param  bool     $force - Bypass caching
     * @return bool
     */
    public static function exists($itemId, $force = false)
    {
        return self::findByItemId($itemId, $force)->isInitialized();
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
                           SET project_variant_id = :projectVariantId
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('itemId'          => $this->itemId,
                                        'projectVariantId' => $this->projectVariantId)
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
                              WHERE item_id = :itemId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('itemId' => $this->itemId));
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
        
        $sql = sprintf("INSERT INTO %s (item_id, project_variant_id)
                               VALUES  (:itemId, :projectVariantId)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('itemId'          => $this->itemId,
                                        'projectVariantId' => $this->projectVariantId)
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
        $this->itemId           = (int)$DO->item_id;
        $this->projectVariantId = (int)$DO->project_variant_id;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheProjectVariant