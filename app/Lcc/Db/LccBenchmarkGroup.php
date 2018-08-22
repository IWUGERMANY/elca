<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use PDO;

/**
 * 
 *
 * @package    -
 * @class      LccBenchmarkGroup
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class LccBenchmarkGroup extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.benchmark_groups';

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
    private $name;

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
                                        'name'               => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $benchmarkVersionId - 
     * @param  int      $category          - 
     * @param  string   $name              - 
     * @return LccBenchmarkGroup
     */
    public static function create($benchmarkVersionId, $category, $name)
    {
        $LccBenchmarkGroup = new LccBenchmarkGroup();
        $LccBenchmarkGroup->setBenchmarkVersionId($benchmarkVersionId);
        $LccBenchmarkGroup->setCategory($category);
        $LccBenchmarkGroup->setName($name);
        
        if($LccBenchmarkGroup->getValidator()->isValid())
            $LccBenchmarkGroup->insert();
        
        return $LccBenchmarkGroup;
    }
    // End create
    

    /**
     * Inits a `LccBenchmarkGroup' by its primary key
     *
     * @param  int      $id    - 
     * @param  bool     $force - Bypass caching
     * @return LccBenchmarkGroup
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new LccBenchmarkGroup();
        
        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , category
                             , name
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    

    /**
     * Inits a `LccBenchmarkGroup' by its unique key (benchmarkVersionId, category, name)
     *
     * @param  int      $benchmarkVersionId - 
     * @param  int      $category          - 
     * @param  string   $name              - 
     * @param  bool     $force             - Bypass caching
     * @return LccBenchmarkGroup
     */
    public static function findByBenchmarkVersionIdAndCategoryAndName($benchmarkVersionId, $category, $name, $force = false)
    {
        if(!$benchmarkVersionId || !$category || !$name)
            return new LccBenchmarkGroup();
        
        $sql = sprintf("SELECT id
                             , benchmark_version_id
                             , category
                             , name
                          FROM %s
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND category = :category
                           AND name = :name"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('benchmarkVersionId' => $benchmarkVersionId, 'category' => $category, 'name' => $name), $force);
    }
    // End findByBenchmarkVersionIdAndCategoryAndName

    public function copy($newBenchmarkVersionId)
    {
        $copy = self::create($newBenchmarkVersionId, $this->getCategory(), $this->getName());

        foreach (LccBenchmarkGroupThresholdSet::findByGroupId($this->getId()) as $threshold) {
            $threshold->copy($copy->getId());
        }

        return $copy;
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
     * Sets the property name
     *
     * @param  string   $name  - 
     * @return void
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;
        
        if(!$this->getValidator()->assertMaxLength('name', 200, $name))
            return;
        
        $this->name = (string)$name;
    }
    // End setName
    

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
                             , name               = :name
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'category'          => $this->category,
                                        'name'              => $this->name)
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
        
        $sql = sprintf("INSERT INTO %s (id, benchmark_version_id, category, name)
                               VALUES  (:id, :benchmarkVersionId, :category, :name)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'                => $this->id,
                                        'benchmarkVersionId' => $this->benchmarkVersionId,
                                        'category'          => $this->category,
                                        'name'              => $this->name)
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
        $this->name               = $DO->name;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccBenchmarkGroup