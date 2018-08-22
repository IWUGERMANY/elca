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
class ElcaCacheFinalEnergyDemand extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.final_energy_demands';



    /**
     * itemId
     */
    private $itemId;

    /**
     * finalEnergyDemandId
     */
    private $finalEnergyDemandId;

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
                                        'finalEnergyDemandId' => PDO::PARAM_INT,
                                        'quantity'            => PDO::PARAM_STR,
                                        'refUnit'             => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    // public


    /**
     * Creates the object
     *
     * @param  integer $finalEnergyDemandId - finalEnergyDemandId
     * @param  number $quantity            - quantity in refUnit / m2[NGF]a
     * @param  string  $refUnit             - refUnit
     * @param  integer $itemId              - itemId
     * @throws Exception
     * @return ElcaCacheFinalEnergyDemand
     */
    public static function create($finalEnergyDemandId, $quantity = null, $refUnit = null, $itemId = null)
    {
        $Dbh = DbHandle::getInstance();

        try
        {
            $Dbh->begin();

            if(is_null($itemId))
            {
                $ProjectFinalEnergyDemand = ElcaProjectFinalEnergyDemand::findById($finalEnergyDemandId);
                $CacheRoot = ElcaCacheProjectVariant::findByProjectVariantId($ProjectFinalEnergyDemand->getProjectVariantId());

                if(!$CacheRoot->isInitialized())
                    $CacheRoot = ElcaCacheProjectVariant::create($ProjectFinalEnergyDemand->getProjectVariantId());

                $projectId = $ProjectFinalEnergyDemand->getProjectVariant()->getProjectId();

                $itemId = ElcaCacheItem::create($projectId, get_class(), $CacheRoot->getItemId())->getId();
            }

            $ElcaCacheFinalEnergyDemand = new ElcaCacheFinalEnergyDemand();
            $ElcaCacheFinalEnergyDemand->setItemId($itemId);
            $ElcaCacheFinalEnergyDemand->setFinalEnergyDemandId($finalEnergyDemandId);
            $ElcaCacheFinalEnergyDemand->setQuantity($quantity);
            $ElcaCacheFinalEnergyDemand->setRefUnit($refUnit);

            if($ElcaCacheFinalEnergyDemand->getValidator()->isValid())
                $ElcaCacheFinalEnergyDemand->insert();

            $Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $Dbh->rollback();
            throw $Exception;
        }

        return $ElcaCacheFinalEnergyDemand;
    }
    // End create



    /**
     * Inits a `ElcaCacheFinalEnergyDemand' by its primary key
     *
     * @param  integer  $itemId - itemId
     * @param  boolean  $force - Bypass caching
     * @return ElcaCacheFinalEnergyDemand
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheFinalEnergyDemand();

        $sql = sprintf("SELECT item_id
                             , final_energy_demand_id
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
     * Inits a `ElcaCacheFinalEnergyDemand' by its unique key (finalEnergyDemandId)
     *
     * @param  integer  $finalEnergyDemandId - finalEnergyDemandId
     * @param  boolean  $force              - Bypass caching
     * @return ElcaCacheFinalEnergyDemand
     */
    public static function findByFinalEnergyDemandId($finalEnergyDemandId, $force = false)
    {
        if(!$finalEnergyDemandId)
            return new ElcaCacheFinalEnergyDemand();

        $sql = sprintf("SELECT item_id
                             , final_energy_demand_id
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE final_energy_demand_id = :finalEnergyDemandId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('finalEnergyDemandId' => $finalEnergyDemandId), $force);
    }
    // End findByFinalEnergyDemandId



    /**
     * Creates a copy from this cache element
     *
     * @param  int $finalEnergyDemandId - new finalEnergyDemandId
     * @return ElcaCacheFinalEnergyDemand - the copy
     */
    public function copy($finalEnergyDemandId)
    {
        if(!$this->isInitialized() || !$finalEnergyDemandId)
            return new ElcaCacheFinalEnergyDemand();

        $Copy = self::create($finalEnergyDemandId,
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
     * Sets the property finalEnergyDemandId
     *
     * @param  integer $finalEnergyDemandId - finalEnergyDemandId
     * @return void
     */
    public function setFinalEnergyDemandId($finalEnergyDemandId)
    {
        if(!$this->getValidator()->assertNotEmpty('finalEnergyDemandId', $finalEnergyDemandId))
            return;

        $this->finalEnergyDemandId = (int)$finalEnergyDemandId;
    }
    // End setFinalEnergyDemandId


    /**
     * Sets the property quantity
     *
     * @param  number $quantity - quantity in refUnit / m2[NGF]a
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
     * @param  string $refUnit - refUnit
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
     * Returns the property finalEnergyDemandId
     *
     * @return integer
     */
    public function getFinalEnergyDemandId()
    {
        return $this->finalEnergyDemandId;
    }
    // End getFinalEnergyDemandId



    /**
     * Returns the associated ElcaProjectFinalEnergyDemand by property finalEnergyDemandId
     *
     * @param  boolean  $force
     * @return ElcaProjectFinalEnergyDemand
     */
    public function getFinalEnergyDemand($force = false)
    {
        return ElcaProjectFinalEnergyDemand::findById($this->finalEnergyDemandId, $force);
    }
    // End getFinalEnergyDemand



    /**
     * Returns the property quantity
     *
     * @return number
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
                           SET final_energy_demand_id = :finalEnergyDemandId
                             , quantity            = :quantity
                             , ref_unit            = :refUnit
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('itemId'             => $this->itemId,
                                        'finalEnergyDemandId' => $this->finalEnergyDemandId,
                                        'quantity'           => $this->quantity,
                                        'refUnit'            => $this->refUnit)
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
        $sql = sprintf("INSERT INTO %s (item_id, final_energy_demand_id, quantity, ref_unit)
                               VALUES  (:itemId, :finalEnergyDemandId, :quantity, :refUnit)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('itemId'             => $this->itemId,
                                        'finalEnergyDemandId' => $this->finalEnergyDemandId,
                                        'quantity'           => $this->quantity,
                                        'refUnit'            => $this->refUnit)
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
        $this->itemId              = (int)$DO->item_id;
        $this->finalEnergyDemandId = (int)$DO->final_energy_demand_id;
        $this->quantity            = $DO->quantity;
        $this->refUnit             = $DO->ref_unit;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheFinalEnergyDemand