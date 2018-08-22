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
 *
 * @package    elca
 * @class      ElcaCacheFinalEnergySupply
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaCacheFinalEnergySupply extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.final_energy_supplies';

    /**
     * itemId
     */
    private $itemId;

    /**
     * finalEnergySupplyId
     */
    private $finalEnergySupplyId;

    /**
     * quantity in refUnit / m2[NGF]a
     */
    private $quantity;

    /**
     * refUnit
     */
    private $refUnit;

    /**
     * Primary key
     */
    private static $primaryKey = array('itemId');

    /**
     * Column types
     */
    private static $columnTypes = array('itemId'              => PDO::PARAM_INT,
                                        'finalEnergySupplyId' => PDO::PARAM_INT,
                                        'quantity'            => PDO::PARAM_STR,
                                        'refUnit'             => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    /**
     * Creates the object
     *
     * @param  int    $finalEnergySupplyId - finalEnergySupplyId
     * @param  float  $quantity            - quantity in refUnit / m2[NGF]a
     * @param  string $refUnit             - refUnit
     * @param  int    $itemId              - itemId
     * @throws Exception
     * @return ElcaCacheFinalEnergySupply
     */
    public static function create($finalEnergySupplyId, $quantity = null, $refUnit = null, $itemId = null)
    {
        $Dbh = DbHandle::getInstance();

        try
        {
            $Dbh->begin();

            if(is_null($itemId))
            {
                $ProjectFinalEnergySupply = ElcaProjectFinalEnergySupply::findById($finalEnergySupplyId);
                $CacheRoot = ElcaCacheProjectVariant::findByProjectVariantId($ProjectFinalEnergySupply->getProjectVariantId());

                if(!$CacheRoot->isInitialized())
                    $CacheRoot = ElcaCacheProjectVariant::create($ProjectFinalEnergySupply->getProjectVariantId());

                $projectId = $ProjectFinalEnergySupply->getProjectVariant()->getProjectId();

                $itemId = ElcaCacheItem::create($projectId, get_class(), $CacheRoot->getItemId())->getId();
            }

            $ElcaCacheFinalEnergySupply = new ElcaCacheFinalEnergySupply();
            $ElcaCacheFinalEnergySupply->setItemId($itemId);
            $ElcaCacheFinalEnergySupply->setFinalEnergySupplyId($finalEnergySupplyId);
            $ElcaCacheFinalEnergySupply->setQuantity($quantity);
            $ElcaCacheFinalEnergySupply->setRefUnit($refUnit);

            if($ElcaCacheFinalEnergySupply->getValidator()->isValid())
                $ElcaCacheFinalEnergySupply->insert();

            $Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $Dbh->rollback();
            throw $Exception;
        }

        return $ElcaCacheFinalEnergySupply;
    }
    // End create
    

    /**
     * Inits a `ElcaCacheFinalEnergySupply' by its primary key
     *
     * @param  int      $itemId - itemId
     * @param  bool     $force - Bypass caching
     * @return ElcaCacheFinalEnergySupply
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheFinalEnergySupply();
        
        $sql = sprintf("SELECT item_id
                             , final_energy_supply_id
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('itemId' => $itemId), $force);
    }
    // End findByItemId
    

    /**
     * Inits a `ElcaCacheFinalEnergySupply' by its unique key (finalEnergySupplyId)
     *
     * @param  int      $finalEnergySupplyId - finalEnergySupplyId
     * @param  bool     $force              - Bypass caching
     * @return ElcaCacheFinalEnergySupply
     */
    public static function findByFinalEnergySupplyId($finalEnergySupplyId, $force = false)
    {
        if(!$finalEnergySupplyId)
            return new ElcaCacheFinalEnergySupply();
        
        $sql = sprintf("SELECT item_id
                             , final_energy_supply_id
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE final_energy_supply_id = :finalEnergySupplyId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('finalEnergySupplyId' => $finalEnergySupplyId), $force);
    }
    // End findByFinalEnergySupplyId


    /**
     * Creates a copy from this cache element
     *
     * @param  int $finalEnergySupplyId - new finalEnergyDemandId
     * @return ElcaCacheFinalEnergySupply - the copy
     */
    public function copy($finalEnergySupplyId)
    {
        if(!$this->isInitialized() || !$finalEnergySupplyId)
            return new ElcaCacheFinalEnergySupply();

        $Copy = self::create($finalEnergySupplyId,
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
     * Sets the property finalEnergySupplyId
     *
     * @param  int      $finalEnergySupplyId - finalEnergySupplyId
     * @return void
     */
    public function setFinalEnergySupplyId($finalEnergySupplyId)
    {
        if(!$this->getValidator()->assertNotEmpty('finalEnergySupplyId', $finalEnergySupplyId))
            return;
        
        $this->finalEnergySupplyId = (int)$finalEnergySupplyId;
    }
    // End setFinalEnergySupplyId
    

    /**
     * Sets the property quantity
     *
     * @param  float    $quantity - quantity in refUnit / m2[NGF]a
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
     * Returns the property finalEnergySupplyId
     *
     * @return int
     */
    public function getFinalEnergySupplyId()
    {
        return $this->finalEnergySupplyId;
    }
    // End getFinalEnergySupplyId
    

    /**
     * Returns the associated ElcaProjectFinalEnergySupply by property finalEnergySupplyId
     *
     * @param  bool     $force
     * @return ElcaProjectFinalEnergySupply
     */
    public function getFinalEnergySupply($force = false)
    {
        return ElcaProjectFinalEnergySupply::findById($this->finalEnergySupplyId, $force);
    }
    // End getFinalEnergySupply
    

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
                           SET final_energy_supply_id = :finalEnergySupplyId
                             , quantity            = :quantity
                             , ref_unit            = :refUnit
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('itemId'             => $this->itemId,
                                        'finalEnergySupplyId' => $this->finalEnergySupplyId,
                                        'quantity'           => $this->quantity,
                                        'refUnit'            => $this->refUnit)
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
        
        $sql = sprintf("INSERT INTO %s (item_id, final_energy_supply_id, quantity, ref_unit)
                               VALUES  (:itemId, :finalEnergySupplyId, :quantity, :refUnit)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('itemId'             => $this->itemId,
                                        'finalEnergySupplyId' => $this->finalEnergySupplyId,
                                        'quantity'           => $this->quantity,
                                        'refUnit'            => $this->refUnit)
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
        $this->itemId              = (int)$DO->item_id;
        $this->finalEnergySupplyId = (int)$DO->final_energy_supply_id;
        $this->quantity            = $DO->quantity;
        $this->refUnit             = $DO->ref_unit;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheFinalEnergySupply