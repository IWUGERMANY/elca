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
 * @package    elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaBenchmarkThreshold extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.benchmark_thresholds';

    

    /**
     * benchmarkThresholdId
     */
    private $id;

    /**
     * benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * indicatorId
     */
    private $indicatorId;

    /**
     * score value
     */
    private $score;

    /**
     * threshold value
     */
    private $value;

    /**
     * Extension indicator ident
     */
    private $indicatorIdent;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                 => PDO::PARAM_INT,
                                        'benchmarkVersionId' => PDO::PARAM_INT,
                                        'indicatorId'        => PDO::PARAM_INT,
                                        'score'              => PDO::PARAM_INT,
                                        'value'              => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('indicatorIdent' => PDO::PARAM_STR);

    
    // public
    

    /**
     * Creates the object
     *
     * @param  integer $benchmarkVersionId - benchmarkVersionId
     * @param  integer $indicatorId - indicatorId
     * @param  integer $score - score value
     * @param  number $value - threshold value
     * @return \ElcaBenchmarkThreshold
     */
    public static function create($benchmarkVersionId, $indicatorId, $score, $value)
    {
        $ElcaBenchmarkThreshold = new ElcaBenchmarkThreshold();
        $ElcaBenchmarkThreshold->setBenchmarkVersionId($benchmarkVersionId);
        $ElcaBenchmarkThreshold->setIndicatorId($indicatorId);
        $ElcaBenchmarkThreshold->setScore($score);
        $ElcaBenchmarkThreshold->setValue($value);
        
        if($ElcaBenchmarkThreshold->getValidator()->isValid())
            $ElcaBenchmarkThreshold->insert();
        
        return $ElcaBenchmarkThreshold;
    }
    // End create
    
    

    /**
     * Inits a `ElcaBenchmarkThreshold' by its primary key
     *
     * @param  integer  $id    - benchmarkThresholdId
     * @param  boolean  $force - Bypass caching
     * @return ElcaBenchmarkThreshold
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaBenchmarkThreshold();
        
        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , indicator_id
                             , score
                             , value
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    
    

    /**
     * Inits a `ElcaBenchmarkThreshold' by its unique key (benchmarkVersionId, indicatorId, score)
     *
     * @param  integer  $benchmarkVersionId - benchmarkVersionId
     * @param  integer  $indicatorId       - indicatorId
     * @param  integer  $score             - score value
     * @param  boolean  $force             - Bypass caching
     * @return ElcaBenchmarkThreshold
     */
    public static function findByBenchmarkVersionIdAndIndicatorIdAndScore($benchmarkVersionId, $indicatorId, $score, $force = false)
    {
        if(!$benchmarkVersionId || !$indicatorId || !$score)
            return new ElcaBenchmarkThreshold();

        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , indicator_id
                             , score
                             , value
                          FROM %s
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND indicator_id = :indicatorId
                           AND score = :score"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('benchmarkVersionId' => $benchmarkVersionId, 'indicatorId' => $indicatorId, 'score' => $score), $force);
    }
    // End findByBenchmarkVersionIdAndIndicatorIdAndScore

    

    /**
     * Copies the threshold and assigns it to the given versionId
     *
     * @param $versionId
     * @return ElcaBenchmarkThreshold
     */
    public function copy($versionId)
    {
        return self::create($versionId, $this->indicatorId, $this->score, $this->value);
    }
    // End copy

    

    /**
     * Sets the property benchmarkVersionId
     *
     * @param  integer  $benchmarkVersionId - benchmarkVersionId
     * @return 
     */
    public function setBenchmarkVersionId($benchmarkVersionId)
    {
        if(!$this->getValidator()->assertNotEmpty('benchmarkVersionId', $benchmarkVersionId))
            return;
        
        $this->benchmarkVersionId = (int)$benchmarkVersionId;
    }
    // End setBenchmarkVersionId
    
    

    /**
     * Sets the property indicatorId
     *
     * @param  integer  $indicatorId - indicatorId
     * @return 
     */
    public function setIndicatorId($indicatorId)
    {
        if(!$this->getValidator()->assertNotEmpty('indicatorId', $indicatorId))
            return;
        
        $this->indicatorId = (int)$indicatorId;
    }
    // End setIndicatorId
    
    

    /**
     * Sets the property score
     *
     * @param  integer  $score - score value
     * @return 
     */
    public function setScore($score)
    {
        if(!$this->getValidator()->assertNotEmpty('score', $score))
            return;
        
        $this->score = (int)$score;
    }
    // End setScore
    
    

    /**
     * Sets the property value
     *
     * @param  number  $value - threshold value
     * @return 
     */
    public function setValue($value)
    {
        if(!$this->getValidator()->assertNotEmpty('value', $value))
            return;
        
        $this->value = $value;
    }
    // End setValue
    
    

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
     * Returns the property benchmarkVersionId
     *
     * @return integer
     */
    public function getBenchmarkVersionId()
    {
        return $this->benchmarkVersionId;
    }
    // End getBenchmarkVersionId
    
    

    /**
     * Returns the associated ElcaBenchmarkVersion by property benchmarkVersionId
     *
     * @param  boolean  $force
     * @return ElcaBenchmarkVersion
     */
    public function getBenchmarkVersion($force = false)
    {
        return ElcaBenchmarkVersion::findById($this->benchmarkVersionId, $force);
    }
    // End getBenchmarkVersion
    
    

    /**
     * Returns the property indicatorId
     *
     * @return integer
     */
    public function getIndicatorId()
    {
        return $this->indicatorId;
    }
    // End getIndicatorId
    
    

    /**
     * Returns the associated ElcaIndicator by property indicatorId
     *
     * @param  boolean  $force
     * @return ElcaIndicator
     */
    public function getIndicator($force = false)
    {
        return ElcaIndicator::findById($this->indicatorId, $force);
    }
    // End getIndicator
    

    /**
     * @return mixed
     */
    public function getIndicatorIdent()
    {
        return $this->indicatorIdent? $this->indicatorIdent : $this->getIndicator()->getIdent();
    }
    // End getIndicatorIdent


    /**
     * Returns the property score
     *
     * @return integer
     */
    public function getScore()
    {
        return $this->score;
    }
    // End getScore
    
    

    /**
     * Returns the property value
     *
     * @return number
     */
    public function getValue()
    {
        return $this->value;
    }
    // End getValue
    
    

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - benchmarkThresholdId
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
                           SET benchmark_version_id = :benchmarkVersionId
                             , indicator_id       = :indicatorId
                             , score              = :score
                             , value              = :value
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'indicatorId'       => $this->indicatorId,
                                        'score'             => $this->score,
                                        'value'             => $this->value)
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
        $this->id                 = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, benchmark_version_id, indicator_id, score, value)
                               VALUES  (:id, :benchmarkVersionId, :indicatorId, :score, :value)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'indicatorId'       => $this->indicatorId,
                                        'score'             => $this->score,
                                        'value'             => $this->value)
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
        $this->id                 = (int)$DO->id;
        $this->benchmarkVersionId = (int)$DO->benchmark_version_id;
        $this->indicatorId        = (int)$DO->indicator_id;
        $this->score              = (int)$DO->score;
        $this->value              = $DO->value;

        /**
         * Set extensions
         */
        if(isset($DO->inidicator_ident))
            $this->indicatorIdent = $DO->indicator_ident;
    }
    // End initByDataObject
}
// End class ElcaBenchmarkThreshold