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
 * @class      ElcaProjectFinalEnergyRefModel
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaProjectFinalEnergyRefModel extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_final_energy_ref_models';

    /**
     * projectFinalEnergyRefModelId
     */
    private $id;

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * ref model ident
     */
    private $ident;

    /**
     * heating in kWh/(m2*a)
     */
    private $heating;

    /**
     * water in kWh/(m2*a)
     */
    private $water;

    /**
     * lighting in kWh/(m2*a)
     */
    private $lighting;

    /**
     * ventilation in kWh/(m2*a)
     */
    private $ventilation;

    /**
     * cooling in kWh/(m2*a)
     */
    private $cooling;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'               => PDO::PARAM_INT,
                                        'projectVariantId' => PDO::PARAM_INT,
                                        'ident'            => PDO::PARAM_STR,
                                        'heating'          => PDO::PARAM_STR,
                                        'water'            => PDO::PARAM_STR,
                                        'lighting'         => PDO::PARAM_STR,
                                        'ventilation'      => PDO::PARAM_STR,
                                        'cooling'          => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $projectVariantId - projectVariantId
     * @param  string   $ident           - ref model ident
     * @param  float    $heating         - heating in kWh/(m2*a)
     * @param  float    $water           - water in kWh/(m2*a)
     * @param  float    $lighting        - lighting in kWh/(m2*a)
     * @param  float    $ventilation     - ventilation in kWh/(m2*a)
     * @param  float    $cooling         - cooling in kWh/(m2*a)
     * @return ElcaProjectFinalEnergyRefModel
     */
    public static function create($projectVariantId, $ident, $heating = null, $water = null, $lighting = null, $ventilation = null, $cooling = null)
    {
        $ElcaProjectFinalEnergyRefModel = new ElcaProjectFinalEnergyRefModel();
        $ElcaProjectFinalEnergyRefModel->setProjectVariantId($projectVariantId);
        $ElcaProjectFinalEnergyRefModel->setIdent($ident);
        $ElcaProjectFinalEnergyRefModel->setHeating($heating);
        $ElcaProjectFinalEnergyRefModel->setWater($water);
        $ElcaProjectFinalEnergyRefModel->setLighting($lighting);
        $ElcaProjectFinalEnergyRefModel->setVentilation($ventilation);
        $ElcaProjectFinalEnergyRefModel->setCooling($cooling);
        
        if($ElcaProjectFinalEnergyRefModel->getValidator()->isValid())
            $ElcaProjectFinalEnergyRefModel->insert();
        
        return $ElcaProjectFinalEnergyRefModel;
    }
    // End create
    

    /**
     * Inits a `ElcaProjectFinalEnergyRefModel' by its primary key
     *
     * @param  int      $id    - projectFinalEnergyRefModelId
     * @param  bool     $force - Bypass caching
     * @return ElcaProjectFinalEnergyRefModel
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectFinalEnergyRefModel();
        
        $sql = sprintf("SELECT id
                             , project_variant_id
                             , ident
                             , heating
                             , water
                             , lighting
                             , ventilation
                             , cooling
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    

    /**
     * Inits a `ElcaProjectFinalEnergyRefModel' by its unique key (projectVariantId, ident)
     *
     * @param  int      $projectVariantId - projectVariantId
     * @param  string   $ident           - ref model ident
     * @param  bool     $force           - Bypass caching
     * @return ElcaProjectFinalEnergyRefModel
     */
    public static function findByProjectVariantIdAndIdent($projectVariantId, $ident, $force = false)
    {
        if(!$projectVariantId || !$ident)
            return new ElcaProjectFinalEnergyRefModel();
        
        $sql = sprintf("SELECT id
                             , project_variant_id
                             , ident
                             , heating
                             , water
                             , lighting
                             , ventilation
                             , cooling
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId, 'ident' => $ident), $force);
    }
    // End findByProjectVariantIdAndIdent
    

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
     * Sets the property ident
     *
     * @param  string   $ident - ref model ident
     * @return void
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;
        
        if(!$this->getValidator()->assertMaxLength('ident', 30, $ident))
            return;
        
        $this->ident = (string)$ident;
    }
    // End setIdent
    

    /**
     * Sets the property heating
     *
     * @param  float    $heating - heating in kWh/(m2*a)
     * @return void
     */
    public function setHeating($heating = null)
    {
        $this->heating = $heating;
    }
    // End setHeating
    

    /**
     * Sets the property water
     *
     * @param  float    $water - water in kWh/(m2*a)
     * @return void
     */
    public function setWater($water = null)
    {
        $this->water = $water;
    }
    // End setWater
    

    /**
     * Sets the property lighting
     *
     * @param  float    $lighting - lighting in kWh/(m2*a)
     * @return void
     */
    public function setLighting($lighting = null)
    {
        $this->lighting = $lighting;
    }
    // End setLighting
    

    /**
     * Sets the property ventilation
     *
     * @param  float    $ventilation - ventilation in kWh/(m2*a)
     * @return void
     */
    public function setVentilation($ventilation = null)
    {
        $this->ventilation = $ventilation;
    }
    // End setVentilation
    

    /**
     * Sets the property cooling
     *
     * @param  float    $cooling - cooling in kWh/(m2*a)
     * @return void
     */
    public function setCooling($cooling = null)
    {
        $this->cooling = $cooling;
    }
    // End setCooling
    

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
     * Returns the property ident
     *
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getIdent
    

    /**
     * Returns the property heating
     *
     * @return float
     */
    public function getHeating()
    {
        return $this->heating;
    }
    // End getHeating
    

    /**
     * Returns the property water
     *
     * @return float
     */
    public function getWater()
    {
        return $this->water;
    }
    // End getWater
    

    /**
     * Returns the property lighting
     *
     * @return float
     */
    public function getLighting()
    {
        return $this->lighting;
    }
    // End getLighting
    

    /**
     * Returns the property ventilation
     *
     * @return float
     */
    public function getVentilation()
    {
        return $this->ventilation;
    }
    // End getVentilation
    

    /**
     * Returns the property cooling
     *
     * @return float
     */
    public function getCooling()
    {
        return $this->cooling;
    }
    // End getCooling
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - projectFinalEnergyRefModelId
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
                             , ident            = :ident
                             , heating          = :heating
                             , water            = :water
                             , lighting         = :lighting
                             , ventilation      = :ventilation
                             , cooling          = :cooling
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'ident'           => $this->ident,
                                        'heating'         => $this->heating,
                                        'water'           => $this->water,
                                        'lighting'        => $this->lighting,
                                        'ventilation'     => $this->ventilation,
                                        'cooling'         => $this->cooling)
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
        
        $sql = sprintf("INSERT INTO %s (id, project_variant_id, ident, heating, water, lighting, ventilation, cooling)
                               VALUES  (:id, :projectVariantId, :ident, :heating, :water, :lighting, :ventilation, :cooling)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'ident'           => $this->ident,
                                        'heating'         => $this->heating,
                                        'water'           => $this->water,
                                        'lighting'        => $this->lighting,
                                        'ventilation'     => $this->ventilation,
                                        'cooling'         => $this->cooling)
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
        $this->ident            = $DO->ident;
        $this->heating          = $DO->heating;
        $this->water            = $DO->water;
        $this->lighting         = $DO->lighting;
        $this->ventilation      = $DO->ventilation;
        $this->cooling          = $DO->cooling;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectFinalEnergyRefModel