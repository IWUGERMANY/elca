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
 * @package    elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaBenchmarkVersion extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.benchmark_versions';

    const REFERENCE_AREA_NGF = 'netFloorSpace';
    const REFERENCE_AREA_WF = 'livingSpace';

    /**
     * benchmarkVersionId
     */
    private $id;

    /**
     * benchmarkSystemId
     */
    private $benchmarkSystemId;

    /**
     * system name
     */
    private $name;

    /**
     * processDbId
     */
    private $processDbId;

    /**
     * active flag
     */
    private $isActive;

	/**
	 * use reference model
	 */
	private $useReferenceModel;

	private $projectLifeTime;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                => PDO::PARAM_INT,
                                        'benchmarkSystemId' => PDO::PARAM_INT,
                                        'name'              => PDO::PARAM_STR,
                                        'processDbId'       => PDO::PARAM_INT,
                                        'isActive'          => PDO::PARAM_BOOL,
                                        'useReferenceModel' => PDO::PARAM_BOOL,
                                        'projectLifeTime'   => PDO::PARAM_INT,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = ['constrClassIds' => PDO::PARAM_STR];

    private $constrClassIds;

    /**
     * Creates the object
     *
     * @param  integer $benchmarkSystemId - benchmarkSystemId
     * @param  string $name - system name
     * @param  integer $processDbId - processDbId
     * @param  boolean $isActive - active flag
     * @return ElcaBenchmarkVersion
     */
    public static function create($benchmarkSystemId, $name, $processDbId = null, $isActive = false, $useReferenceModel = false, $projectLifeTime = null)
    {
        $benchmarkVersion = new ElcaBenchmarkVersion();
        $benchmarkVersion->setBenchmarkSystemId($benchmarkSystemId);
        $benchmarkVersion->setName($name);
        $benchmarkVersion->setProcessDbId($processDbId);
        $benchmarkVersion->setIsActive($isActive);
	    $benchmarkVersion->setUseReferenceModel($useReferenceModel);
        $benchmarkVersion->setProjectLifeTime($projectLifeTime);

        if($benchmarkVersion->getValidator()->isValid())
            $benchmarkVersion->insert();
        
        return $benchmarkVersion;
    }
    // End create
    
    

    /**
     * Inits a `ElcaBenchmarkVersion' by its primary key
     *
     * @param  integer  $id    - benchmarkVersionId
     * @param  boolean  $force - Bypass caching
     * @return ElcaBenchmarkVersion
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaBenchmarkVersion();
        
        $sql = sprintf("SELECT id
                             , benchmark_system_id
                             , name
                             , process_db_id
                             , is_active
                             , use_reference_model
                             , project_life_time
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    

    /**
     * Sets the property benchmarkSystemId
     *
     * @param  integer $benchmarkSystemId - benchmarkSystemId
     * @return void
     */
    public function setBenchmarkSystemId($benchmarkSystemId)
    {
        if(!$this->getValidator()->assertNotEmpty('benchmarkSystemId', $benchmarkSystemId))
            return;
        
        $this->benchmarkSystemId = (int)$benchmarkSystemId;
    }
    // End setBenchmarkSystemId
    
    

    /**
     * Sets the property name
     *
     * @param  string $name - system name
     * @return void
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;
        
        if(!$this->getValidator()->assertMaxLength('name', 150, $name))
            return;
        
        $this->name = (string)$name;
    }
    // End setName
    
    

    /**
     * Sets the property processDbId
     *
     * @param  integer $processDbId - processDbId
     * @return void
     */
    public function setProcessDbId($processDbId = null)
    {
        $this->processDbId = $processDbId;
    }
    // End setProcessDbId
    
    

    /**
     * Sets the property isActive
     *
     * @param  boolean $isActive - active flag
     * @return void
     */
    public function setIsActive($isActive = false)
    {
        $this->isActive = (bool)$isActive;
    }
    // End setIsActive
    
    

	/**
	 * Sets the property useReferenceModel
	 *
	 * @param  boolean $useReferenceModel
	 * @return void
	 */
	public function setUseReferenceModel($useReferenceModel = false)
	{
		$this->useReferenceModel = (bool)$useReferenceModel;
	}

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
    
    

    /**
     * Returns the property benchmarkSystemId
     *
     * @return integer
     */
    public function getBenchmarkSystemId()
    {
        return $this->benchmarkSystemId;
    }
    // End getBenchmarkSystemId
    
    

    /**
     * Returns the associated ElcaBenchmarkSystem by property benchmarkSystemId
     *
     * @param  boolean  $force
     * @return ElcaBenchmarkSystem
     */
    public function getBenchmarkSystem($force = false)
    {
        return ElcaBenchmarkSystem::findById($this->benchmarkSystemId, $force);
    }
    // End getBenchmarkSystem
    
    

    /**
     * Returns the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    // End getName
    
    

    /**
     * Returns the property processDbId
     *
     * @return integer
     */
    public function getProcessDbId()
    {
        return $this->processDbId;
    }
    // End getProcessDbId
    
    

    /**
     * Returns the associated ElcaProcessDb by property processDbId
     *
     * @param  boolean  $force
     * @return ElcaProcessDb
     */
    public function getProcessDb($force = false)
    {
        return ElcaProcessDb::findById($this->processDbId, $force);
    }
    // End getProcessDb
    
    

    /**
     * Returns the property isActive
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }
    // End isActive
    
    

	/**
	 * Returns the property useReferenceModel
	 *
	 * @return bool
	 */
	public function getUseReferenceModel()
	{
		return $this->useReferenceModel;
	}

    public function getProjectLifeTime()
    {
        return $this->projectLifeTime;
    }

    public function setProjectLifeTime($projectLifeTime)
    {
        $this->projectLifeTime = $projectLifeTime;
    }

    public function getConstrClassIds()
    {
        if (null !== $this->constrClassIds) {
            return $this->constrClassIds;
        }

        return ElcaBenchmarkVersionConstrClassSet::find(['benchmark_version_id' => $this->id])->getArrayBy();
    }

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - benchmarkVersionId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
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
                           SET benchmark_system_id = :benchmarkSystemId
                             , name              = :name
                             , process_db_id     = :processDbId
                             , is_active         = :isActive
                             , use_reference_model = :useReferenceModel
                             , project_life_time = :projectLifeTime
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'               => $this->id,
                                        'benchmarkSystemId' => $this->benchmarkSystemId,
                                        'name'             => $this->name,
                                        'processDbId'      => $this->processDbId,
                                        'isActive'         => $this->isActive,
                                        'useReferenceModel' => $this->useReferenceModel,
                                        'projectLifeTime' => $this->projectLifeTime,
                                  )
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
        $this->id                = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, benchmark_system_id, name, process_db_id, is_active, use_reference_model, project_life_time)
                               VALUES  (:id, :benchmarkSystemId, :name, :processDbId, :isActive, :useReferenceModel, :projectLifeTime)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'               => $this->id,
                                        'benchmarkSystemId' => $this->benchmarkSystemId,
                                        'name'             => $this->name,
                                        'processDbId'      => $this->processDbId,
                                        'isActive'         => $this->isActive,
                                        'useReferenceModel' => $this->useReferenceModel,
                                        'projectLifeTime' => $this->projectLifeTime,
                                  )
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
        $this->id                = (int)$DO->id;
        $this->benchmarkSystemId = (int)$DO->benchmark_system_id;
        $this->name              = $DO->name;
        $this->processDbId       = $DO->process_db_id;
        $this->isActive          = (bool)$DO->is_active;
	    $this->useReferenceModel = (bool)$DO->use_reference_model;
	    $this->projectLifeTime = $DO->project_life_time;

        /**
         * Set extensions
         */
        $this->constrClassIds = isset($DO->constr_class_ids) && null !== $DO->constr_class_ids
            ? array_map(
                function ($constrClassId) {
                    return (int)$constrClassId;
                },
                str_getcsv(trim(($DO->constr_class_ids), '{}'))
            )
            : [];
    }
    // End initByDataObject
}
