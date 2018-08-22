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
 * @package    -
 * @class      ElcaProjectTransportMean
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaProjectTransportMean extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_transport_means';

    /**
     * projectTransportMeanId
     */
    private $id;

    /**
     * projectTransportId
     */
    private $projectTransportId;

    /**
     * processConfigId
     */
    private $processConfigId;

    /**
     * distance in m
     */
    private $distance;

    /**
     * transport efficiency
     */
    private $efficiency;

    /**
     * ext: processConfigName
     */
    private $processConfigName;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                 => PDO::PARAM_INT,
                                        'projectTransportId' => PDO::PARAM_INT,
                                        'processConfigId'    => PDO::PARAM_INT,
                                        'distance'           => PDO::PARAM_STR,
                                        'efficiency'         => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('processConfigName' => PDO::PARAM_STR);

    /**
     * Creates the object
     *
     * @param  int      $projectTransportId - projectTransportId
     * @param  int      $processConfigId   - processConfigId
     * @param  float    $distance          - distance in m
     * @param  float    $efficiency        - transport efficiency
     * @return ElcaProjectTransportMean
     */
    public static function create($projectTransportId, $processConfigId, $distance, $efficiency = 1)
    {
        $ElcaProjectTransportMean = new ElcaProjectTransportMean();
        $ElcaProjectTransportMean->setProjectTransportId($projectTransportId);
        $ElcaProjectTransportMean->setProcessConfigId($processConfigId);
        $ElcaProjectTransportMean->setDistance($distance);
        $ElcaProjectTransportMean->setEfficiency($efficiency);
        
        if($ElcaProjectTransportMean->getValidator()->isValid())
            $ElcaProjectTransportMean->insert();
        
        return $ElcaProjectTransportMean;
    }
    // End create
    

    /**
     * Inits a `ElcaProjectTransportMean' by its primary key
     *
     * @param  int      $id    - projectTransportMeanId
     * @param  bool     $force - Bypass caching
     * @return ElcaProjectTransportMean
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectTransportMean();
        
        $sql = sprintf("SELECT id
                             , project_transport_id
                             , process_config_id
                             , distance
                             , efficiency
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById


    /**
     * Creates a deep copy from this transport mean
     *
     * @param  int $projectTransportId new transportId
     * @return ElcaProjectTransportMean - the new element copy
     */
    public function copy($projectTransportId)
    {
        if(!$this->isInitialized() || !$projectTransportId)
            return new ElcaProjectTransportMean();

        $Copy = self::create($projectTransportId,
                             $this->processConfigId,
                             $this->distance,
                             $this->efficiency
        );

        return $Copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////


    /**
     * Sets the property projectTransportId
     *
     * @param  int      $projectTransportId - projectTransportId
     * @return void
     */
    public function setProjectTransportId($projectTransportId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectTransportId', $projectTransportId))
            return;
        
        $this->projectTransportId = (int)$projectTransportId;
    }
    // End setProjectTransportId
    

    /**
     * Sets the property processConfigId
     *
     * @param  int      $processConfigId - processConfigId
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
     * Sets the property distance
     *
     * @param  float    $distance - distance in m
     * @return void
     */
    public function setDistance($distance)
    {
        if(!$this->getValidator()->assertNotEmpty('distance', $distance))
            return;
        
        $this->distance = $distance;
    }
    // End setDistance
    

    /**
     * Sets the property efficiency
     *
     * @param  float    $efficiency - transport efficiency
     * @return void
     */
    public function setEfficiency($efficiency = 1)
    {
        $this->efficiency = $efficiency;
    }
    // End setEfficiency
    

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
     * Returns the property projectTransportId
     *
     * @return int
     */
    public function getProjectTransportId()
    {
        return $this->projectTransportId;
    }
    // End getProjectTransportId
    

    /**
     * Returns the associated ElcaProjectTransport by property projectTransportId
     *
     * @param  bool     $force
     * @return ElcaProjectTransport
     */
    public function getProjectTransport($force = false)
    {
        return ElcaProjectTransport::findById($this->projectTransportId, $force);
    }
    // End getProjectTransport
    

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
     * Returns the property distance
     *
     * @return float
     */
    public function getDistance()
    {
        return $this->distance;
    }
    // End getDistance


    /**
     * Returns the total distance
     *
     * @return float
     */
    public function getTotalDistance()
    {
        if (!$this->isInitialized())
            return null;

        return $this->distance * $this->getRounds();
    }
    // End getTotalDistance


    /**
     * Returns the rounds
     *
     * @return float
     */
    public function getRounds()
    {
        if (!$this->isInitialized())
            return null;

        $quantity = $this->getProjectTransport()->getQuantity();

        $payLoad = ElcaProcessConfigAttribute::findValue($this->processConfigId, ElcaProcessConfigAttribute::IDENT_TRANSPORT_PAYLOAD);
        $rounds = $payLoad? round(max(1, $quantity / $payLoad), 2) : 1;

        return $rounds;
    }
    // End getRounds

    /**
     * Returns the property efficiency
     *
     * @return float
     */
    public function getEfficiency()
    {
        return $this->efficiency;
    }
    // End getEfficiency


    /**
     * Returns the property processConfigNmae
     *
     * @return string
     */
    public function getProcessConfigName()
    {
        return isset($this->processConfigName)? $this->processConfigName : $this->getProcessConfig()->getName();
    }
    // End getProcessConfigName


    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - projectTransportMeanId
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
                           SET project_transport_id = :projectTransportId
                             , process_config_id  = :processConfigId
                             , distance           = :distance
                             , efficiency         = :efficiency
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'                => $this->id,
                                        'projectTransportId' => $this->projectTransportId,
                                        'processConfigId'   => $this->processConfigId,
                                        'distance'          => $this->distance,
                                        'efficiency'        => $this->efficiency)
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
        $this->id                 = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, project_transport_id, process_config_id, distance, efficiency)
                               VALUES  (:id, :projectTransportId, :processConfigId, :distance, :efficiency)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'                => $this->id,
                                        'projectTransportId' => $this->projectTransportId,
                                        'processConfigId'   => $this->processConfigId,
                                        'distance'          => $this->distance,
                                        'efficiency'        => $this->efficiency)
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
        $this->id                 = (int)$DO->id;
        $this->projectTransportId = (int)$DO->project_transport_id;
        $this->processConfigId    = (int)$DO->process_config_id;
        $this->distance           = $DO->distance;
        $this->efficiency         = $DO->efficiency;
        
        /**
         * Set extensions
         */
        if (isset($DO->process_config_name)) $this->processConfigName = $DO->process_config_name;
    }
    // End initByDataObject
}
// End class ElcaProjectTransportMean