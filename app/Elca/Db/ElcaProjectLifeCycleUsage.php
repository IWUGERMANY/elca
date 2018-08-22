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
 *
 * @package    -
 * @class      ElcaProjectLifeCycleUsage
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2016 BEIBOB Medienfreunde
 */
class ElcaProjectLifeCycleUsage extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_life_cycle_usages';

    /**
     * projectLifeCycleUsageId
     */
    private $id;

    /**
     * projectId
     */
    private $projectId;

    /**
     * lifeCycleIdent
     */
    private $lifeCycleIdent;

    /**
     * useInConstruction
     */
    private $useInConstruction;

    /**
     * useInMaintenance
     */
    private $useInMaintenance;

    /**
     * useInEnergyDemand
     */
    private $useInEnergyDemand;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                => PDO::PARAM_INT,
                                        'projectId'         => PDO::PARAM_INT,
                                        'lifeCycleIdent'    => PDO::PARAM_STR,
                                        'useInConstruction' => PDO::PARAM_BOOL,
                                        'useInMaintenance'  => PDO::PARAM_BOOL,
                                        'useInEnergyDemand' => PDO::PARAM_BOOL);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $projectId        - projectId
     * @param  string   $lifeCycleIdent   - lifeCycleIdent
     * @param  bool     $useInConstruction - useInConstruction
     * @param  bool     $useInMaintenance - useInMaintenance
     * @param  bool     $useInEnergyDemand - useInEnergyDemand
     * @return ElcaProjectLifeCycleUsage
     */
    public static function create($projectId, $lifeCycleIdent, $useInConstruction, $useInMaintenance, $useInEnergyDemand)
    {
        $ElcaProjectLifeCycleUsage = new ElcaProjectLifeCycleUsage();
        $ElcaProjectLifeCycleUsage->setProjectId($projectId);
        $ElcaProjectLifeCycleUsage->setLifeCycleIdent($lifeCycleIdent);
        $ElcaProjectLifeCycleUsage->setUseInConstruction($useInConstruction);
        $ElcaProjectLifeCycleUsage->setUseInMaintenance($useInMaintenance);
        $ElcaProjectLifeCycleUsage->setUseInEnergyDemand($useInEnergyDemand);
        
        if($ElcaProjectLifeCycleUsage->getValidator()->isValid())
            $ElcaProjectLifeCycleUsage->insert();
        
        return $ElcaProjectLifeCycleUsage;
    }
    // End create
    

    /**
     * Inits a `ElcaProjectLifeCycleUsage' by its primary key
     *
     * @param  int      $id    - projectLifeCycleUsageId
     * @param  bool     $force - Bypass caching
     * @return ElcaProjectLifeCycleUsage
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectLifeCycleUsage();
        
        $sql = sprintf("SELECT id
                             , project_id
                             , life_cycle_ident
                             , use_in_construction
                             , use_in_maintenance
                             , use_in_energy_demand
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    /**
     * Inits a `ElcaProjectLifeCycleUsage' by its unique key (projectId, lifeCycleIdent)
     *
     * @param  int      $projectId     - projectId
     * @param  string   $lifeCycleIdent - lifeCycleIdent
     * @param  bool     $force         - Bypass caching
     * @return ElcaProjectLifeCycleUsage
     */
    public static function findByProjectIdAndLifeCycleIdent($projectId, $lifeCycleIdent, $force = false)
    {
        if(!$projectId || !$lifeCycleIdent)
            return new ElcaProjectLifeCycleUsage();

        $sql = sprintf("SELECT id
                             , project_id
                             , life_cycle_ident
                             , use_in_construction
                             , use_in_maintenance
                             , use_in_energy_demand
                          FROM %s
                         WHERE project_id = :projectId
                           AND life_cycle_ident = :lifeCycleIdent"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('projectId' => $projectId, 'lifeCycleIdent' => $lifeCycleIdent), $force);
    }
    // End findByProjectIdAndLifeCycleIdent

    /**
     * Sets the property projectId
     *
     * @param  int      $projectId - projectId
     * @return void
     */
    public function setProjectId($projectId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectId', $projectId))
            return;
        
        $this->projectId = (int)$projectId;
    }
    // End setProjectId
    

    /**
     * Sets the property lifeCycleIdent
     *
     * @param  string   $lifeCycleIdent - lifeCycleIdent
     * @return void
     */
    public function setLifeCycleIdent($lifeCycleIdent)
    {
        if(!$this->getValidator()->assertNotEmpty('lifeCycleIdent', $lifeCycleIdent))
            return;
        
        if(!$this->getValidator()->assertMaxLength('lifeCycleIdent', 20, $lifeCycleIdent))
            return;
        
        $this->lifeCycleIdent = (string)$lifeCycleIdent;
    }
    // End setLifeCycleIdent
    

    /**
     * Sets the property useInConstruction
     *
     * @param  bool     $useInConstruction - useInConstruction
     * @return void
     */
    public function setUseInConstruction($useInConstruction)
    {
        if(!$this->getValidator()->assertNotEmpty('useInConstruction', $useInConstruction))
            return;
        
        $this->useInConstruction = (bool)$useInConstruction;
    }
    // End setUseInConstruction
    

    /**
     * Sets the property useInMaintenance
     *
     * @param  bool     $useInMaintenance - useInMaintenance
     * @return void
     */
    public function setUseInMaintenance($useInMaintenance)
    {
        if(!$this->getValidator()->assertNotEmpty('useInMaintenance', $useInMaintenance))
            return;
        
        $this->useInMaintenance = (bool)$useInMaintenance;
    }
    // End setUseInMaintenance
    

    /**
     * Sets the property useInEnergyDemand
     *
     * @param  bool     $useInEnergyDemand - useInEnergyDemand
     * @return void
     */
    public function setUseInEnergyDemand($useInEnergyDemand)
    {
        if(!$this->getValidator()->assertNotEmpty('useInEnergyDemand', $useInEnergyDemand))
            return;
        
        $this->useInEnergyDemand = (bool)$useInEnergyDemand;
    }
    // End setUseInEnergyDemand
    

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
     * Returns the property projectId
     *
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }
    // End getProjectId
    

    /**
     * Returns the associated ElcaProject by property projectId
     *
     * @param  bool     $force
     * @return ElcaProject
     */
    public function getProject($force = false)
    {
        return ElcaProject::findById($this->projectId, $force);
    }
    // End getProject
    

    /**
     * Returns the property lifeCycleIdent
     *
     * @return string
     */
    public function getLifeCycleIdent()
    {
        return $this->lifeCycleIdent;
    }
    // End getLifeCycleIdent

    /**
     * @return ElcaLifeCycle
     */
    public function getLifeCycle()
    {
        return ElcaLifeCycle::findByIdent($this->lifeCycleIdent);
    }

    /**
     * Returns the property useInConstruction
     *
     * @return bool
     */
    public function getUseInConstruction()
    {
        return $this->useInConstruction;
    }
    // End getUseInConstruction
    

    /**
     * Returns the property useInMaintenance
     *
     * @return bool
     */
    public function getUseInMaintenance()
    {
        return $this->useInMaintenance;
    }
    // End getUseInMaintenance
    

    /**
     * Returns the property useInEnergyDemand
     *
     * @return bool
     */
    public function getUseInEnergyDemand()
    {
        return $this->useInEnergyDemand;
    }
    // End getUseInEnergyDemand

    /**
     * Returns the property useInConstruction
     *
     * @return bool
     */
    public function getUseInTotal()
    {
        return $this->useInConstruction || $this->useInEnergyDemand;
    }
    // End getUseInTotal



    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - projectLifeCycleUsageId
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
                           SET project_id        = :projectId
                             , life_cycle_ident  = :lifeCycleIdent
                             , use_in_construction = :useInConstruction
                             , use_in_maintenance = :useInMaintenance
                             , use_in_energy_demand = :useInEnergyDemand
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'               => $this->id,
                                        'projectId'        => $this->projectId,
                                        'lifeCycleIdent'   => $this->lifeCycleIdent,
                                        'useInConstruction' => $this->useInConstruction,
                                        'useInMaintenance' => $this->useInMaintenance,
                                        'useInEnergyDemand' => $this->useInEnergyDemand)
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
        $this->id                = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, project_id, life_cycle_ident, use_in_construction, use_in_maintenance, use_in_energy_demand)
                               VALUES  (:id, :projectId, :lifeCycleIdent, :useInConstruction, :useInMaintenance, :useInEnergyDemand)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'               => $this->id,
                                        'projectId'        => $this->projectId,
                                        'lifeCycleIdent'   => $this->lifeCycleIdent,
                                        'useInConstruction' => $this->useInConstruction,
                                        'useInMaintenance' => $this->useInMaintenance,
                                        'useInEnergyDemand' => $this->useInEnergyDemand)
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
        $this->id                = (int)$DO->id;
        $this->projectId         = (int)$DO->project_id;
        $this->lifeCycleIdent    = $DO->life_cycle_ident;
        $this->useInConstruction = (bool)$DO->use_in_construction;
        $this->useInMaintenance  = (bool)$DO->use_in_maintenance;
        $this->useInEnergyDemand = (bool)$DO->use_in_energy_demand;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectLifeCycleUsage