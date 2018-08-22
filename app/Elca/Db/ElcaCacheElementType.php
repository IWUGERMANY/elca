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
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaCacheElementType extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.element_types';



    /**
     * itemId
     */
    private $itemId;

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * elementTypeNodeId
     */
    private $elementTypeNodeId;

    /**
     * mass aggregation
     */
    private $mass;

    /**
     * Primary key
     */
    private static $primaryKey = array('itemId');

    /**
     * Column types
     */
    private static $columnTypes = array('itemId'            => PDO::PARAM_INT,
                                        'projectVariantId'  => PDO::PARAM_INT,
                                        'elementTypeNodeId' => PDO::PARAM_INT,
                                        'mass'              => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    // public


    /**
     * Creates the object
     *
     * @param  integer $projectVariantId  - projectVariantId
     * @param  integer $elementTypeNodeId - elementTypeNodeId
     * @param  float   $mass              - mass aggregation
     * @param  integer $itemId            - itemId
     * @throws Exception
     * @return ElcaCacheElementType
     */
    public static function create($projectVariantId, $elementTypeNodeId, $mass = null, $itemId = null)
    {
        $Dbh = DbHandle::getInstance();

        try
        {
            $Dbh->begin();

            if(is_null($itemId))
            {
                $projectId = ElcaProjectVariant::findById($projectVariantId)->getProjectId();

                $ParentElementType = ElcaElementType::findParentByNodeId($elementTypeNodeId);

                /**
                 * Only the root node has the project variant item as parent
                 */
                if($ParentElementType->isInitialized())
                {
                    $CElementType = ElcaCacheElementType::findByProjectVariantIdAndElementTypeNodeId($projectVariantId, $ParentElementType->getNodeId());

                    if(!$CElementType->isInitialized())
                        $CElementType = ElcaCacheElementType::create($projectVariantId, $ParentElementType->getNodeId());

                    $itemId = ElcaCacheItem::create($projectId, get_class(), $CElementType->getItemId())->getId();
                }
                else {

                    $CacheVariant = ElcaCacheProjectVariant::findByProjectVariantId($projectVariantId);

                    if (!$CacheVariant->isInitialized()) {
                        $CacheVariant = ElcaCacheProjectVariant::create($projectVariantId);
                    }
                    $itemId = ElcaCacheItem::create($projectId, get_class(), $CacheVariant->getItemId())->getId();
                }
            }

            $ElcaCacheElementType = new ElcaCacheElementType();
            $ElcaCacheElementType->setItemId($itemId);
            $ElcaCacheElementType->setProjectVariantId($projectVariantId);
            $ElcaCacheElementType->setElementTypeNodeId($elementTypeNodeId);
            $ElcaCacheElementType->setMass($mass);

            if($ElcaCacheElementType->getValidator()->isValid())
                $ElcaCacheElementType->insert();

            $Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $Dbh->rollback();
            throw $Exception;
        }

        return $ElcaCacheElementType;
    }
    // End create



    /**
     * Inits a `ElcaCacheElementType' by its primary key
     *
     * @param  integer  $itemId - itemId
     * @param  boolean  $force - Bypass caching
     * @return ElcaCacheElementType
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheElementType();

        $sql = sprintf("SELECT item_id
                             , project_variant_id
                             , element_type_node_id
                             , mass
                          FROM %s
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('itemId' => $itemId), $force);
    }
    // End findByItemId



    /**
     * Inits a `ElcaCacheElementType' by its unique key (projectVariantId, elementTypeNodeId)
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  integer  $elementTypeNodeId - elementTypeNodeId
     * @param  boolean  $force            - Bypass caching
     * @return ElcaCacheElementType
     */
    public static function findByProjectVariantIdAndElementTypeNodeId($projectVariantId, $elementTypeNodeId, $force = false)
    {
        if(!$projectVariantId || !$elementTypeNodeId)
            return new ElcaCacheElementType();

        $sql = sprintf("SELECT item_id
                             , project_variant_id
                             , element_type_node_id
                             , mass
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND element_type_node_id = :elementTypeNodeId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId, 'elementTypeNodeId' => $elementTypeNodeId), $force);
    }
    // End findByProjectVariantIdAndElementTypeNodeId


    /**
     * Sets the property itemId
     *
     * @param  integer $itemId - itemId
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
     * @param  integer $projectVariantId - projectVariantId
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
     * Sets the property elementTypeNodeId
     *
     * @param  integer $elementTypeNodeId - elementTypeNodeId
     * @return void
     */
    public function setElementTypeNodeId($elementTypeNodeId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementTypeNodeId', $elementTypeNodeId))
            return;

        $this->elementTypeNodeId = (int)$elementTypeNodeId;
    }
    // End setElementTypeNodeId


    /**
     * Sets the property mass
     *
     * @param  number $mass - mass aggregation
     * @return void
     */
    public function setMass($mass = null)
    {
        $this->mass = $mass;
    }
    // End setMass



    /**
     * Returns the property itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }
    // End getItemId



    /**
     * Returns the associated ElcaCacheItem by property itemId
     *
     * @param  boolean  $force
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
     * @return integer
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId



    /**
     * Returns the associated ElcaProjectVariant by property projectVariantId
     *
     * @param  boolean  $force
     * @return ElcaProjectVariant
     */
    public function getProjectVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->projectVariantId, $force);
    }
    // End getProjectVariant



    /**
     * Returns the property elementTypeNodeId
     *
     * @return integer
     */
    public function getElementTypeNodeId()
    {
        return $this->elementTypeNodeId;
    }
    // End getElementTypeNodeId



    /**
     * Returns the associated ElcaElementType by property elementTypeNodeId
     *
     * @param  boolean  $force
     * @return ElcaElementType
     */
    public function getElementTypeNode($force = false)
    {
        return ElcaElementType::findByNodeId($this->elementTypeNodeId, $force);
    }
    // End getElementTypeNode



    /**
     * Returns the property mass
     *
     * @return number
     */
    public function getMass()
    {
        return $this->mass;
    }
    // End getMass



    /**
     * Checks, if the object exists
     *
     * @param  integer  $itemId - itemId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($itemId, $force = false)
    {
        return self::findByItemId($itemId, $force)->isInitialized();
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
                           SET project_variant_id = :projectVariantId
                             , element_type_node_id = :elementTypeNodeId
                             , mass              = :mass
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('itemId'           => $this->itemId,
                                        'projectVariantId' => $this->projectVariantId,
                                        'elementTypeNodeId' => $this->elementTypeNodeId,
                                        'mass'             => $this->mass)
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

        $sql = sprintf("INSERT INTO %s (item_id, project_variant_id, element_type_node_id, mass)
                               VALUES  (:itemId, :projectVariantId, :elementTypeNodeId, :mass)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('itemId'           => $this->itemId,
                                        'projectVariantId' => $this->projectVariantId,
                                        'elementTypeNodeId' => $this->elementTypeNodeId,
                                        'mass'             => $this->mass)
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
        $this->itemId            = (int)$DO->item_id;
        $this->projectVariantId  = (int)$DO->project_variant_id;
        $this->elementTypeNodeId = (int)$DO->element_type_node_id;
        $this->mass              = $DO->mass;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheElementType