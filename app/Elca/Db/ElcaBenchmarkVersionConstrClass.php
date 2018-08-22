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
 * @class      ElcaBenchmarkVersionConstrClass
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class ElcaBenchmarkVersionConstrClass extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.benchmark_version_constr_classes';

    /**
     * benchmarkLifeCycleUsageSpecificationId
     */
    private $id;

    /**
     * benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * constrClassId
     */
    private $constrClassId;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                 => PDO::PARAM_INT,
                                        'benchmarkVersionId' => PDO::PARAM_INT,
                                        'constrClassId'      => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  int      $constrClassId     - constrClassId
     * @return ElcaBenchmarkVersionConstrClass
     */
    public static function create($benchmarkVersionId, $constrClassId)
    {
        $ElcaBenchmarkVersionConstrClass = new ElcaBenchmarkVersionConstrClass();
        $ElcaBenchmarkVersionConstrClass->setBenchmarkVersionId($benchmarkVersionId);
        $ElcaBenchmarkVersionConstrClass->setConstrClassId($constrClassId);
        
        if($ElcaBenchmarkVersionConstrClass->getValidator()->isValid())
            $ElcaBenchmarkVersionConstrClass->insert();
        
        return $ElcaBenchmarkVersionConstrClass;
    }
    // End create
    

    /**
     * Inits a `ElcaBenchmarkVersionConstrClass' by its primary key
     *
     * @param  int      $id    - benchmarkLifeCycleUsageSpecificationId
     * @param  bool     $force - Bypass caching
     * @return ElcaBenchmarkVersionConstrClass
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaBenchmarkVersionConstrClass();
        
        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , constr_class_id
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    

    /**
     * Inits a `ElcaBenchmarkVersionConstrClass' by its unique key (benchmarkVersionId, constrClassId)
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  int      $constrClassId     - constrClassId
     * @param  bool     $force             - Bypass caching
     * @return ElcaBenchmarkVersionConstrClass
     */
    public static function findByBenchmarkVersionIdAndConstrClassId($benchmarkVersionId, $constrClassId, $force = false)
    {
        if(!$benchmarkVersionId || !$constrClassId)
            return new ElcaBenchmarkVersionConstrClass();
        
        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , constr_class_id
                          FROM %s
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND constr_class_id = :constrClassId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('benchmarkVersionId' => $benchmarkVersionId, 'constrClassId' => $constrClassId), $force);
    }
    // End findByBenchmarkVersionIdAndConstrClassId

    public function copy($benchmarkVersionId)
    {
        return self::create($benchmarkVersionId, $this->constrClassId);
    }

    /**
     * Sets the property benchmarkVersionId
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @return void
     */
    public function setBenchmarkVersionId($benchmarkVersionId)
    {
        if(!$this->getValidator()->assertNotEmpty('benchmarkVersionId', $benchmarkVersionId))
            return;
        
        $this->benchmarkVersionId = (int)$benchmarkVersionId;
    }
    // End setBenchmarkVersionId
    

    /**
     * Sets the property constrClassId
     *
     * @param  int      $constrClassId - constrClassId
     * @return void
     */
    public function setConstrClassId($constrClassId)
    {
        if(!$this->getValidator()->assertNotEmpty('constrClassId', $constrClassId))
            return;
        
        $this->constrClassId = (int)$constrClassId;
    }
    // End setConstrClassId
    

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
     * Returns the property benchmarkVersionId
     *
     * @return int
     */
    public function getBenchmarkVersionId()
    {
        return $this->benchmarkVersionId;
    }
    // End getBenchmarkVersionId
    

    /**
     * Returns the associated ElcaBenchmarkVersion by property benchmarkVersionId
     *
     * @param  bool     $force
     * @return ElcaBenchmarkVersion
     */
    public function getBenchmarkVersion($force = false)
    {
        return ElcaBenchmarkVersion::findById($this->benchmarkVersionId, $force);
    }
    // End getBenchmarkVersion
    

    /**
     * Returns the property constrClassId
     *
     * @return int
     */
    public function getConstrClassId()
    {
        return $this->constrClassId;
    }
    // End getConstrClassId
    

    /**
     * Returns the associated ElcaConstrClass by property constrClassId
     *
     * @param  bool     $force
     * @return ElcaConstrClass
     */
    public function getConstrClass($force = false)
    {
        return ElcaConstrClass::findById($this->constrClassId, $force);
    }
    // End getConstrClass
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - benchmarkLifeCycleUsageSpecificationId
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
                           SET benchmark_version_id = :benchmarkVersionId
                             , constr_class_id    = :constrClassId
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'constrClassId'     => $this->constrClassId)
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
        
        $sql = sprintf("INSERT INTO %s (id, benchmark_version_id, constr_class_id)
                               VALUES  (:id, :benchmarkVersionId, :constrClassId)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'constrClassId'     => $this->constrClassId)
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
        $this->benchmarkVersionId = (int)$DO->benchmark_version_id;
        $this->constrClassId      = (int)$DO->constr_class_id;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaBenchmarkVersionConstrClass