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

use Beibob\Blibs\DbObject;
use PDO;

/**
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectFinalEnergyDemand extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_final_energy_demands';
    const IDENT_PROCESS_ENERGY = 'process-energy-demand';

    //////////////////////////////////////////////////////////////////////////////////////

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
     * ident
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

    private $ratio;
    private $kwkId;

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
                                        'ident'            => PDO::PARAM_STR,
                                        'heating'          => PDO::PARAM_STR,
                                        'water'            => PDO::PARAM_STR,
                                        'lighting'         => PDO::PARAM_STR,
                                        'ventilation'      => PDO::PARAM_STR,
                                        'cooling'          => PDO::PARAM_STR,
                                        'ratio'            => PDO::PARAM_STR,
                                        'kwkId'            => PDO::PARAM_INT,
    );

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
     * @param  integer  $projectVariantId - projectVariantId
     * @param  integer  $processConfigId - process config id
     * @param  number  $heating         - heating in kWh/(m2*a)
     * @param  number  $water           - water in kWh/(m2*a)
     * @param  number  $lighting        - lighting in kWh/(m2*a)
     * @param  number  $ventilation     - ventilation in kWh/(m2*a)
     * @param  number  $cooling         - cooling in kWh/(m2*a)
     */
    public static function create($projectVariantId, $processConfigId, $heating = null, $water = null, $lighting = null, $ventilation = null, $cooling = null, $ident = null, $ratio = 1, $kwkId = null)
    {
        $finalEnergyDemand = new ElcaProjectFinalEnergyDemand();
        $finalEnergyDemand->setProjectVariantId($projectVariantId);
        $finalEnergyDemand->setProcessConfigId($processConfigId);
        $finalEnergyDemand->setIdent($ident);
        $finalEnergyDemand->setHeating($heating);
        $finalEnergyDemand->setWater($water);
        $finalEnergyDemand->setLighting($lighting);
        $finalEnergyDemand->setVentilation($ventilation);
        $finalEnergyDemand->setCooling($cooling);
        $finalEnergyDemand->setRatio($ratio);
        $finalEnergyDemand->setKwkId($kwkId);

        if($finalEnergyDemand->getValidator()->isValid())
            $finalEnergyDemand->insert();

        return $finalEnergyDemand;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProjectFinalEnergyDemand' by its primary key
     *
     * @param  integer  $id    - projectFinalEnergyDemandId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProjectFinalEnergyDemand
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectFinalEnergyDemand();

        $sql = sprintf("SELECT id
                             , project_variant_id
                             , process_config_id
                             , ident
                             , heating
                             , water
                             , lighting
                             , ventilation
                             , cooling
                             , ratio
                             , kwk_id
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById


    /**
     * @param $projectVariantId
     * @param $ident
     * @return bool|ElcaProjectFinalEnergyDemand
     */
    public static function findByProjectVariantIdAndIdent($projectVariantId, $ident, $force = false)
    {
        if (!$projectVariantId || !$ident)
            return new self();

        $sql = sprintf("SELECT id
                             , project_variant_id
                             , process_config_id
                             , ident
                             , heating
                             , water
                             , lighting
                             , ventilation
                             , cooling
                             , ratio
                             , kwk_id
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND ident = :ident"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId, 'ident' => $ident), $force);
    }
    //////////////////////////////////////////////////////////////////////////////////////

     /**
     * Creates a deep copy from this ElcaProjectFinalEnergyDemand
     *
     * @param  int $projectVariantId new project variant id
     * @return ElcaProjectFinalEnergyDemand - the new element copy
     */
    public function copy($projectVariantId, $kwkId = null)
    {
        if (!$this->isInitialized() || !$projectVariantId)
            return new ElcaProjectFinalEnergyDemand();

        $copy = self::create($projectVariantId,
                             $this->processConfigId,
                             $this->heating,
                             $this->water,
                             $this->lighting,
                             $this->ventilation,
                             $this->cooling,
                             $this->ident,
                             $this->ratio,
                             $kwkId ?? $this->kwkId
        );

        return $copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property projectVariantId
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @return
     */
    public function setProjectVariantId($projectVariantId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId))
            return;

        $this->projectVariantId = (int)$projectVariantId;
    }
    // End setProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processConfigId
     *
     * @param  integer  $processConfigId - process config id
     * @return
     */
    public function setProcessConfigId($processConfigId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId))
            return;

        $this->processConfigId = (int)$processConfigId;
    }
    // End setProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ident
     *
     * @param  string   $ident - ref model ident
     * @return void
     */
    public function setIdent($ident = null)
    {
        if(!$this->getValidator()->assertMaxLength('ident', 30, $ident))
            return;

        $this->ident = $ident;
    }
    // End setIdent

    /**
     * Sets the property heating
     *
     * @param  number  $heating - heating in kWh/(m2*a)
     * @return
     */
    public function setHeating($heating = null)
    {
        $this->heating = $heating;
    }
    // End setHeating

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property water
     *
     * @param  number  $water - water in kWh/(m2*a)
     * @return
     */
    public function setWater($water = null)
    {
        $this->water = $water;
    }
    // End setWater

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property lighting
     *
     * @param  number  $lighting - lighting in kWh/(m2*a)
     * @return
     */
    public function setLighting($lighting = null)
    {
        $this->lighting = $lighting;
    }
    // End setLighting

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ventilation
     *
     * @param  number  $ventilation - ventilation in kWh/(m2*a)
     * @return
     */
    public function setVentilation($ventilation = null)
    {
        $this->ventilation = $ventilation;
    }
    // End setVentilation

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property cooling
     *
     * @param  number  $cooling - cooling in kWh/(m2*a)
     * @return
     */
    public function setCooling($cooling = null)
    {
        $this->cooling = $cooling;
    }

    public function setRatio($ratio): void
    {
        $this->ratio = $ratio;
    }

    public function setKwkId($kwkId): void
    {
        $this->kwkId = $kwkId;
    }

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
     * Returns the property projectVariantId
     *
     * @return integer
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property processConfigId
     *
     * @return integer
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcessConfig by property processConfigId
     *
     * @param  boolean  $force
     * @return ElcaProcessConfig
     */
    public function getProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->processConfigId, $force);
    }
    // End getProcessConfig

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

    /**
     * Returns the property heating
     *
     * @return number
     */
    public function getHeating()
    {
        return $this->heating;
    }
    // End getHeating

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property water
     *
     * @return number
     */
    public function getWater()
    {
        return $this->water;
    }
    // End getWater

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property lighting
     *
     * @return number
     */
    public function getLighting()
    {
        return $this->lighting;
    }
    // End getLighting

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property ventilation
     *
     * @return number
     */
    public function getVentilation()
    {
        return $this->ventilation;
    }
    // End getVentilation

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property cooling
     *
     * @return number
     */
    public function getCooling()
    {
        return $this->cooling;
    }

    public function getRatio()
    {
        return $this->ratio;
    }

    public function isKwk()
    {
        return null !== $this->kwkId;
    }

    public function getKwkId()
    {
        return $this->kwkId;
    }

    public static function primaryKey(): array
    {
        return self::$primaryKey;
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - projectFinalEnergyDemandId
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
                           SET project_variant_id = :projectVariantId
                             , process_config_id = :processConfigId
                             , ident            = :ident
                             , heating          = :heating
                             , water            = :water
                             , lighting         = :lighting
                             , ventilation      = :ventilation
                             , cooling          = :cooling
                             , ratio            = :ratio
                             , kwk_id           = :kwkId
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'processConfigId' => $this->processConfigId,
                                        'ident'           => $this->ident,
                                        'heating'         => $this->heating,
                                        'water'           => $this->water,
                                        'lighting'        => $this->lighting,
                                        'ventilation'     => $this->ventilation,
                                        'cooling'         => $this->cooling,
                                        'ratio'           => $this->ratio,
                                        'kwkId'           => $this->kwkId,
                                      )
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
        $this->id               = $this->getNextSequenceValue();

        $sql = sprintf("INSERT INTO %s (id, project_variant_id, process_config_id, ident, heating, water, lighting, ventilation, cooling, ratio, kwk_id)
                               VALUES  (:id, :projectVariantId, :processConfigId, :ident, :heating, :water, :lighting, :ventilation, :cooling, :ratio, :kwkId)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'processConfigId' => $this->processConfigId,
                                        'ident'           => $this->ident,
                                        'heating'         => $this->heating,
                                        'water'           => $this->water,
                                        'lighting'        => $this->lighting,
                                        'ventilation'     => $this->ventilation,
                                        'cooling'         => $this->cooling,
                                        'ratio'           => $this->ratio,
                                        'kwkId'           => $this->kwkId,
                                  )
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
        $this->id               = (int)$DO->id;
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->processConfigId  = (int)$DO->process_config_id;
        $this->ident            = $DO->ident;
        $this->heating          = $DO->heating;
        $this->water            = $DO->water;
        $this->lighting         = $DO->lighting;
        $this->ventilation      = $DO->ventilation;
        $this->cooling          = $DO->cooling;
        $this->ratio            = $DO->ratio;
        $this->kwkId            = $DO->kwk_id;
    }
    // End initByDataObject
}
