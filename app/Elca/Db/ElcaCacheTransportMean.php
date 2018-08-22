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

use Beibob\Blibs\DbHandle;
use PDO;
use Exception;
use Beibob\Blibs\DbObject;
/**
 * 
 *
 * @package    elca
 * @class      ElcaCacheTransportMean
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaCacheTransportMean extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.transport_means';

    /**
     * itemId
     */
    private $itemId;

    /**
     * transportMeanId
     */
    private $transportMeanId;

    /**
     * quantity
     */
    private $quantity;

    /**
     * refUnit
     */
    private $refUnit;

    /**
     * Primary key
     */
    private static $primaryKey = ['itemId'];

    /**
     * Column types
     */
    private static $columnTypes = ['itemId'          => PDO::PARAM_INT,
                                        'transportMeanId' => PDO::PARAM_INT,
                                        'quantity'        => PDO::PARAM_STR,
                                        'refUnit'         => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];


    /**
     * Creates the object
     *
     * @param  int    $transportMeanId - transportMeanId
     * @param  float  $quantity        - quantity
     * @param  string $refUnit         - refUnit
     * @param  int    $itemId          - itemId
     * @throws Exception
     * @return ElcaCacheTransportMean
     */
    public static function create($transportMeanId, $quantity = null, $refUnit = null, $isVirtual = false, $itemId = null)
    {
        $Dbh = DbHandle::getInstance();

        try
        {
            $Dbh->begin();

            if(null === $itemId)
            {
                $ProjectTransportMean = ElcaProjectTransportMean::findById($transportMeanId);
                $projectVariantId = $ProjectTransportMean->getProjectTransport()->getProjectVariantId();
                $projectVariant = ElcaProjectVariant::findById($projectVariantId);

                $CacheRoot = ElcaCacheProjectVariant::findByProjectVariantId($projectVariantId);

                if(!$CacheRoot->isInitialized())
                    $CacheRoot = ElcaCacheProjectVariant::create($projectVariantId);

                $itemId = ElcaCacheItem::create($projectVariant->getProjectId(), get_class(), $CacheRoot->getItemId(), $isVirtual)->getId();
            }

            $ElcaCacheTransportMean = new ElcaCacheTransportMean();
            $ElcaCacheTransportMean->setItemId($itemId);
            $ElcaCacheTransportMean->setTransportMeanId($transportMeanId);
            $ElcaCacheTransportMean->setQuantity($quantity);
            $ElcaCacheTransportMean->setRefUnit($refUnit);

            if($ElcaCacheTransportMean->getValidator()->isValid())
                $ElcaCacheTransportMean->insert();

            $Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $Dbh->rollback();
            throw $Exception;
        }

        return $ElcaCacheTransportMean;
    }
    // End create
    

    /**
     * Inits a `ElcaCacheTransportMean' by its primary key
     *
     * @param  int      $itemId - itemId
     * @param  bool     $force - Bypass caching
     * @return ElcaCacheTransportMean
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheTransportMean();
        
        $sql = sprintf("SELECT item_id
                             , transport_mean_id
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, ['itemId' => $itemId], $force);
    }
    // End findByItemId
    

    /**
     * Inits a `ElcaCacheTransportMean' by its unique key (transportMeanId)
     *
     * @param  int      $transportMeanId - transportMeanId
     * @param  bool     $force          - Bypass caching
     * @return ElcaCacheTransportMean
     */
    public static function findByTransportMeanId($transportMeanId, $force = false)
    {
        if(!$transportMeanId)
            return new ElcaCacheTransportMean();
        
        $sql = sprintf("SELECT item_id
                             , transport_mean_id
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE transport_mean_id = :transportMeanId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, ['transportMeanId' => $transportMeanId], $force);
    }
    // End findByTransportMeanId


    /**
     * Creates a copy from this cache element
     *
     * @param  int $projectTransportMeanId - new projectTransportMeanId
     * @return ElcaCacheTransportMean - the copy
     */
    public function copy($projectTransportMeanId)
    {
        if(!$this->isInitialized() || !$projectTransportMeanId)
            return new ElcaCacheTransportMean();

        $Copy = self::create($projectTransportMeanId,
                             $this->quantity,
                             $this->refUnit);
        /**
         * Copy indicator values
         */
        ElcaCacheIndicatorSet::copy($this->getItemId(), $Copy->getItemId());

        return $Copy;
    }
    // End copy



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
     * Sets the property transportMeanId
     *
     * @param  int      $transportMeanId - transportMeanId
     * @return void
     */
    public function setTransportMeanId($transportMeanId)
    {
        if(!$this->getValidator()->assertNotEmpty('transportMeanId', $transportMeanId))
            return;
        
        $this->transportMeanId = (int)$transportMeanId;
    }
    // End setTransportMeanId
    

    /**
     * Sets the property quantity
     *
     * @param  float    $quantity - quantity
     * @return void
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;
    }
    // End setQuantity
    

    /**
     * Sets the property refUnit
     *
     * @param  string   $refUnit - refUnit
     * @return void
     */
    public function setRefUnit($refUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('refUnit', 10, $refUnit))
            return;
        
        $this->refUnit = $refUnit;
    }
    // End setRefUnit
    

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
     * Returns the property transportMeanId
     *
     * @return int
     */
    public function getTransportMeanId()
    {
        return $this->transportMeanId;
    }
    // End getTransportMeanId
    

    /**
     * Returns the associated ElcaProjectTransportMean by property transportMeanId
     *
     * @param  bool     $force
     * @return ElcaProjectTransportMean
     */
    public function getTransportMean($force = false)
    {
        return ElcaProjectTransportMean::findById($this->transportMeanId, $force);
    }
    // End getTransportMean
    

    /**
     * Returns the property quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    // End getQuantity
    

    /**
     * Returns the property refUnit
     *
     * @return string
     */
    public function getRefUnit()
    {
        return $this->refUnit;
    }
    // End getRefUnit


    /**
     * Sets this outdated
     *
     * @param  boolean $isOutdated - if it is outdated, it needs updating
     * @return void
     */
    public function setIsOutdated($isOutdated = true)
    {
        $Item = $this->getItem();
        $Item->setIsOutdated($isOutdated);
        $Item->update();
    }
    // End setIsOutdated

    //////////////////////////////////////////////////////////////////////////////////////


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
                           SET transport_mean_id = :transportMeanId
                             , quantity        = :quantity
                             , ref_unit        = :refUnit
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  ['itemId'         => $this->itemId,
                                        'transportMeanId' => $this->transportMeanId,
                                        'quantity'       => $this->quantity,
                                        'refUnit'        => $this->refUnit]
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
                                  ['itemId' => $this->itemId]);
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
        
        $primaryKey = [];
        
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
        
        $sql = sprintf("INSERT INTO %s (item_id, transport_mean_id, quantity, ref_unit)
                               VALUES  (:itemId, :transportMeanId, :quantity, :refUnit)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  ['itemId'         => $this->itemId,
                                        'transportMeanId' => $this->transportMeanId,
                                        'quantity'       => $this->quantity,
                                        'refUnit'        => $this->refUnit]
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
        $this->itemId          = (int)$DO->item_id;
        $this->transportMeanId = (int)$DO->transport_mean_id;
        $this->quantity        = $DO->quantity;
        $this->refUnit         = $DO->ref_unit;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheTransportMean