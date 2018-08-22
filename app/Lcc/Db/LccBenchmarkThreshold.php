<?php

namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use PDO;

/**
 * 
 *
 * @package    -
 * @class      LccBenchmarkThreshold
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class LccBenchmarkThreshold extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.benchmark_thresholds';

    /**
     * 
     */
    private $id;

    /**
     * 
     */
    private $benchmarkVersionId;

    /**
     * 
     */
    private $category;

    /**
     * 
     */
    private $score;

    /**
     * 
     */
    private $value;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                 => PDO::PARAM_INT,
                                        'benchmarkVersionId' => PDO::PARAM_INT,
                                        'category'           => PDO::PARAM_INT,
                                        'score'              => PDO::PARAM_INT,
                                        'value'              => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $benchmarkVersionId - 
     * @param  int      $category          - 
     * @param  int      $score             - 
     * @param  float    $value             - 
     * @return LccBenchmarkThreshold
     */
    public static function create($benchmarkVersionId, $category, $score, $value)
    {
        $LccBenchmarkThreshold = new LccBenchmarkThreshold();
        $LccBenchmarkThreshold->setBenchmarkVersionId($benchmarkVersionId);
        $LccBenchmarkThreshold->setCategory($category);
        $LccBenchmarkThreshold->setScore($score);
        $LccBenchmarkThreshold->setValue($value);
        
        if($LccBenchmarkThreshold->getValidator()->isValid())
            $LccBenchmarkThreshold->insert();
        
        return $LccBenchmarkThreshold;
    }
    // End create
    

    /**
     * Inits a `LccBenchmarkThreshold' by its primary key
     *
     * @param  int      $id    - 
     * @param  bool     $force - Bypass caching
     * @return LccBenchmarkThreshold
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new LccBenchmarkThreshold();
        
        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , category
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
     * Inits a `LccBenchmarkThreshold' by its unique key (benchmarkVersionId, category, score)
     *
     * @param  int      $benchmarkVersionId - 
     * @param  int      $category          - 
     * @param  int      $score             - 
     * @param  bool     $force             - Bypass caching
     * @return LccBenchmarkThreshold
     */
    public static function findByBenchmarkVersionIdAndCategoryAndScore($benchmarkVersionId, $category, $score, $force = false)
    {
        if(!$benchmarkVersionId || !$category || !$score)
            return new LccBenchmarkThreshold();
        
        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , category
                             , score
                             , value
                          FROM %s
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND category = :category
                           AND score = :score"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('benchmarkVersionId' => $benchmarkVersionId, 'category' => $category, 'score' => $score), $force);
    }
    // End findByBenchmarkVersionIdAndCategoryAndScore

    public function copy($newBenchmarkVersionId)
    {
        return self::create($newBenchmarkVersionId, $this->getCategory(), $this->getScore(), $this->getValue());
    }

    /**
     * Sets the property benchmarkVersionId
     *
     * @param  int      $benchmarkVersionId - 
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
     * Sets the property category
     *
     * @param  int      $category - 
     * @return void
     */
    public function setCategory($category)
    {
        if(!$this->getValidator()->assertNotEmpty('category', $category))
            return;
        
        $this->category = (int)$category;
    }
    // End setCategory
    

    /**
     * Sets the property score
     *
     * @param  int      $score - 
     * @return void
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
     * @param  float    $value - 
     * @return void
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
     * Returns the property category
     *
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }
    // End getCategory
    

    /**
     * Returns the property score
     *
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }
    // End getScore
    

    /**
     * Returns the property value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }
    // End getValue
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - 
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
                             , category           = :category
                             , score              = :score
                             , value              = :value
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'category'          => $this->category,
                                        'score'             => $this->score,
                                        'value'             => $this->value)
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
        
        $sql = sprintf("INSERT INTO %s (id, benchmark_version_id, category, score, value)
                               VALUES  (:id, :benchmarkVersionId, :category, :score, :value)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'category'          => $this->category,
                                        'score'             => $this->score,
                                        'value'             => $this->value)
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
        $this->category           = (int)$DO->category;
        $this->score              = (int)$DO->score;
        $this->value              = $DO->value;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccBenchmarkThreshold