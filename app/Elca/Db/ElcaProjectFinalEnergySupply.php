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
 *
 * @package    elca
 * @class      ElcaProjectFinalEnergySupply
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaProjectFinalEnergySupply extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_final_energy_supplies';

    /**
     * projectFinalEnergyDemandId
     */
    private $id;

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * process config id
     */
    private $processConfigId;

    /**
     * ratio included in en ev
     */
    private $enEvRatio;

    /**
     * quantity in kWh/a
     */
    private $quantity;

    /**
     * description
     */
    private $description;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'               => PDO::PARAM_INT,
                                        'projectVariantId' => PDO::PARAM_INT,
                                        'processConfigId'  => PDO::PARAM_INT,
                                        'enEvRatio'        => PDO::PARAM_STR,
                                        'quantity'         => PDO::PARAM_STR,
                                        'description'      => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    /**
     * Creates the object
     *
     * @param  int      $projectVariantId - projectVariantId
     * @param  int      $processConfigId  - process config id
     * @param  float    $quantity         - quantity in kWh/a
     * @param  string   $description      - description
     * @param float|int $enEvRatio        - ratio included in en ev
     * @return ElcaProjectFinalEnergySupply
     */
    public static function create($projectVariantId, $processConfigId, $quantity, $description, $enEvRatio = 1)
    {
        $ElcaProjectFinalEnergySupply = new ElcaProjectFinalEnergySupply();
        $ElcaProjectFinalEnergySupply->setProjectVariantId($projectVariantId);
        $ElcaProjectFinalEnergySupply->setProcessConfigId($processConfigId);
        $ElcaProjectFinalEnergySupply->setQuantity($quantity);
        $ElcaProjectFinalEnergySupply->setDescription($description);
        $ElcaProjectFinalEnergySupply->setEnEvRatio($enEvRatio);
        
        if($ElcaProjectFinalEnergySupply->getValidator()->isValid())
            $ElcaProjectFinalEnergySupply->insert();
        
        return $ElcaProjectFinalEnergySupply;
    }
    // End create
    

    /**
     * Inits a `ElcaProjectFinalEnergySupply' by its primary key
     *
     * @param  int      $id    - projectFinalEnergyDemandId
     * @param  bool     $force - Bypass caching
     * @return ElcaProjectFinalEnergySupply
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectFinalEnergySupply();
        
        $sql = sprintf("SELECT id
                             , project_variant_id
                             , process_config_id
                             , en_ev_ratio
                             , quantity
                             , description
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById


    /**
     * Creates a deep copy from this
     *
     * @param  int $projectVariantId new project variant id
     * @return ElcaProjectFinalEnergySupply - the new element copy
     */
    public function copy($projectVariantId)
    {
        if (!$this->isInitialized() || !$projectVariantId)
            return new ElcaProjectFinalEnergySupply();

        $copy = self::create(
            $projectVariantId,
            $this->processConfigId,
            $this->quantity,
            $this->description,
            $this->enEvRatio
        );

        return $copy;
    }


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
     * Sets the property processConfigId
     *
     * @param  int      $processConfigId - process config id
     * @return void
     */
    public function setProcessConfigId($processConfigId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId))
            return;
        
        $this->processConfigId = (int)$processConfigId;
    }
    // End setProcessConfigId
    

    /**
     * Sets the property enEvRatio
     *
     * @param  float    $enEvRatio - ratio included in en ev
     * @return void
     */
    public function setEnEvRatio($enEvRatio = 1)
    {
        $this->enEvRatio = $enEvRatio;
    }
    // End setEnEvRatio
    

    /**
     * Sets the property quantity
     *
     * @param  float    $quantity - quantity in kWh/a
     * @return void
     */
    public function setQuantity($quantity)
    {
        if(!$this->getValidator()->assertNotEmpty('quantity', $quantity))
            return;
        
        $this->quantity = $quantity;
    }
    // End setquantity
    

    /**
     * Sets the property description
     *
     * @param  string   $description - description
     * @return void
     */
    public function setDescription($description)
    {
        if(!$this->getValidator()->assertNotEmpty('description', $description))
            return;
        
        $this->description = (string)$description;
    }
    // End setDescription
    

    /**
     * Returns the property id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    // End getId
    

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
     * Returns the property processConfigId
     *
     * @return int
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId
    

    /**
     * Returns the associated ElcaProcessConfig by property processConfigId
     *
     * @param  bool     $force
     * @return ElcaProcessConfig
     */
    public function getProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->processConfigId, $force);
    }
    // End getProcessConfig
    

    /**
     * Returns the property enEvRatio
     *
     * @return float
     */
    public function getEnEvRatio()
    {
        return $this->enEvRatio;
    }
    // End getEnEvRatio
    

    /**
     * Returns the property quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    // End getquantity
    

    /**
     * Returns the property description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    // End getDescription
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - projectFinalEnergyDemandId
     * @param  bool     $force - Bypass caching
     * @return bool
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
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
                             , process_config_id = :processConfigId
                             , en_ev_ratio      = :enEvRatio
                             , quantity            = :quantity
                             , description      = :description
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'processConfigId' => $this->processConfigId,
                                        'enEvRatio'       => $this->enEvRatio,
                                        'quantity'           => $this->quantity,
                                        'description'     => $this->description)
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
                              WHERE id = :id"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('id' => $this->id));
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
        $this->id               = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, project_variant_id, process_config_id, en_ev_ratio, quantity, description)
                               VALUES  (:id, :projectVariantId, :processConfigId, :enEvRatio, :quantity, :description)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'processConfigId' => $this->processConfigId,
                                        'enEvRatio'       => $this->enEvRatio,
                                        'quantity'           => $this->quantity,
                                        'description'     => $this->description)
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
        $this->id               = (int)$DO->id;
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->processConfigId  = (int)$DO->process_config_id;
        $this->enEvRatio        = $DO->en_ev_ratio;
        $this->quantity            = $DO->quantity;
        $this->description      = $DO->description;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectFinalEnergySupply