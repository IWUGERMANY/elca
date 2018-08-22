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
 * @package    -
 * @class      ElcaCacheFinalEnergyRefModel
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaCacheFinalEnergyRefModel extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.final_energy_ref_models';

    /**
     * itemId
     */
    private $itemId;

    /**
     * finalEnergyRefModelId
     */
    private $finalEnergyRefModelId;

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
    private static $columnTypes = array('itemId'                => PDO::PARAM_INT,
                                        'finalEnergyRefModelId' => PDO::PARAM_INT,
                                        'quantity'              => PDO::PARAM_STR,
                                        'refUnit'               => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

	/**
	 * Creates the object
	 *
	 * @param  int    $finalEnergyRefModelId - finalEnergyRefModelId
	 * @param  float  $quantity              - quantity in refUnit / m2[NGF]a
	 * @param  string $refUnit               - refUnit
	 * @param  int    $itemId                - itemId
	 *
	 * @throws Exception
	 * @return ElcaCacheFinalEnergyRefModel
	 */
    public static function create($finalEnergyRefModelId, $quantity = null, $refUnit = null, $itemId = null)
    {
	    $Dbh = DbHandle::getInstance();

	    try
	    {
		    $Dbh->begin();

		    if(is_null($itemId))
		    {
                $projectFinalEnergyRefModel = ElcaProjectFinalEnergyRefModel::findById($finalEnergyRefModelId);
                $cacheRoot = ElcaCacheProjectVariant::findByProjectVariantId($projectFinalEnergyRefModel->getProjectVariantId());

                if(!$cacheRoot->isInitialized())
                    $cacheRoot = ElcaCacheProjectVariant::create($projectFinalEnergyRefModel->getProjectVariantId());

                $projectId = $projectFinalEnergyRefModel->getProjectVariant()->getProjectId();

                /**
			     * Create virtual. this excludes values from lca
			     */
			    $itemId = ElcaCacheItem::create($projectId, get_class(), $cacheRoot->getItemId(), true)->getId();
		    }

		    $ElcaCacheFinalEnergyRefModel = new ElcaCacheFinalEnergyRefModel();
	        $ElcaCacheFinalEnergyRefModel->setItemId($itemId);
	        $ElcaCacheFinalEnergyRefModel->setFinalEnergyRefModelId($finalEnergyRefModelId);
	        $ElcaCacheFinalEnergyRefModel->setQuantity($quantity);
	        $ElcaCacheFinalEnergyRefModel->setRefUnit($refUnit);

	        if($ElcaCacheFinalEnergyRefModel->getValidator()->isValid())
	            $ElcaCacheFinalEnergyRefModel->insert();

		    $Dbh->commit();
	    }
	    catch(Exception $Exception)
	    {
		    $Dbh->rollback();
		    throw $Exception;
	    }

	    return $ElcaCacheFinalEnergyRefModel;
    }
    // End create
    

    /**
     * Inits a `ElcaCacheFinalEnergyRefModel' by its primary key
     *
     * @param  int      $itemId - itemId
     * @param  bool     $force - Bypass caching
     * @return ElcaCacheFinalEnergyRefModel
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheFinalEnergyRefModel();
        
        $sql = sprintf("SELECT item_id
                             , final_energy_ref_model_id
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
     * Inits a `ElcaCacheFinalEnergyRefModel' by its unique key (finalEnergyRefModelId)
     *
     * @param  int      $finalEnergyRefModelId - finalEnergyRefModelId
     * @param  bool     $force                - Bypass caching
     * @return ElcaCacheFinalEnergyRefModel
     */
    public static function findByFinalEnergyRefModelId($finalEnergyRefModelId, $force = false)
    {
        if(!$finalEnergyRefModelId)
            return new ElcaCacheFinalEnergyRefModel();
        
        $sql = sprintf("SELECT item_id
                             , final_energy_ref_model_id
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE final_energy_ref_model_id = :finalEnergyRefModelId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('finalEnergyRefModelId' => $finalEnergyRefModelId), $force);
    }
    // End findByFinalEnergyRefModelId


	/**
	 * Creates a copy from this cache element
	 *
	 * @param  int $finalEnergyRefModelId - new finalEnergyRefModelId
	 * @return ElcaCacheFinalEnergyRefModel - the copy
	 */
	public function copy($finalEnergyRefModelId)
	{
		if(!$this->isInitialized() || !$finalEnergyRefModelId)
			return new ElcaCacheFinalEnergyRefModel();

		$Copy = self::create($finalEnergyRefModelId,
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
     * Sets the property finalEnergyRefModelId
     *
     * @param  int      $finalEnergyRefModelId - finalEnergyRefModelId
     * @return void
     */
    public function setFinalEnergyRefModelId($finalEnergyRefModelId)
    {
        if(!$this->getValidator()->assertNotEmpty('finalEnergyRefModelId', $finalEnergyRefModelId))
            return;
        
        $this->finalEnergyRefModelId = (int)$finalEnergyRefModelId;
    }
    // End setFinalEnergyRefModelId
    

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
     * Returns the property finalEnergyRefModelId
     *
     * @return int
     */
    public function getFinalEnergyRefModelId()
    {
        return $this->finalEnergyRefModelId;
    }
    // End getFinalEnergyRefModelId
    

    /**
     * Returns the associated ElcaProjectFinalEnergyRefModel by property finalEnergyRefModelId
     *
     * @param  bool     $force
     * @return ElcaProjectFinalEnergyRefModel
     */
    public function getFinalEnergyRefModel($force = false)
    {
        return ElcaProjectFinalEnergyRefModel::findById($this->finalEnergyRefModelId, $force);
    }
    // End getFinalEnergyRefModel
    

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
                           SET final_energy_ref_model_id = :finalEnergyRefModelId
                             , quantity              = :quantity
                             , ref_unit              = :refUnit
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('itemId'               => $this->itemId,
                                        'finalEnergyRefModelId' => $this->finalEnergyRefModelId,
                                        'quantity'             => $this->quantity,
                                        'refUnit'              => $this->refUnit)
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
        
        $sql = sprintf("INSERT INTO %s (item_id, final_energy_ref_model_id, quantity, ref_unit)
                               VALUES  (:itemId, :finalEnergyRefModelId, :quantity, :refUnit)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('itemId'               => $this->itemId,
                                        'finalEnergyRefModelId' => $this->finalEnergyRefModelId,
                                        'quantity'             => $this->quantity,
                                        'refUnit'              => $this->refUnit)
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
        $this->itemId                = (int)$DO->item_id;
        $this->finalEnergyRefModelId = (int)$DO->final_energy_ref_model_id;
        $this->quantity              = $DO->quantity;
        $this->refUnit               = $DO->ref_unit;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheFinalEnergyRefModel