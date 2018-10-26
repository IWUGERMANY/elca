<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use PDO;

class LccEnergySourceCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.energy_source_costs';

    /**
     * 
     */
    private $id;

    /**
     * 
     */
    private $versionId;

    /**
     * 
     */
    private $name;

    /**
     * 
     */
    private $costs;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'versionId'      => PDO::PARAM_INT,
                                        'name'           => PDO::PARAM_STR,
                                        'costs'          => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $versionId - 
     * @param  string   $name     - 
     * @param  float    $costs    - 
     * @return LccEnergySourceCost
     */
    public static function create($versionId, $name, $costs)
    {
        $LccEnergySourceCost = new LccEnergySourceCost();
        $LccEnergySourceCost->setVersionId($versionId);
        $LccEnergySourceCost->setName($name);
        $LccEnergySourceCost->setCosts($costs);
        
        if($LccEnergySourceCost->getValidator()->isValid())
            $LccEnergySourceCost->insert();
        
        return $LccEnergySourceCost;
    }
    // End create
    

    /**
     * Inits a `LccEnergySourceCost' by its primary key
     *
     * @param  int      $id    - 
     * @param  bool     $force - Bypass caching
     * @return LccEnergySourceCost
     */
    public static function findById($id, $force = false)
    {
        if (!\is_numeric($id))
            return new LccEnergySourceCost();
        
        $sql = sprintf("SELECT id
                             , version_id
                             , name
                             , costs
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }

    /**
     * Inits a `LccEnergySourceCost' by its primary key
     *
     * @param  int      $id    -
     * @param  bool     $force - Bypass caching
     * @return LccEnergySourceCost
     */
    public static function findByVersionIdAndName($versionId, $name, $force = false)
    {
        if (!\is_numeric($versionId) || !$name)
            return new LccEnergySourceCost();

        $sql = sprintf("SELECT id
                             , version_id
                             , name
                             , costs
                          FROM %s
                         WHERE version_id = :versionId AND name = :name"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['versionId' => $versionId, 'name' => $name], $force);
    }

    public function copy($newVersionId)
    {
        return self::create($newVersionId, $this->getName(), $this->getCosts());
    }


    /**
     * Sets the property versionId
     *
     * @param  int      $versionId - 
     * @return void
     */
    public function setVersionId($versionId)
    {
        if(!$this->getValidator()->assertNotEmpty('versionId', $versionId))
            return;
        
        $this->versionId = (int)$versionId;
    }
    // End setVersionId
    

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
     * Sets the property costs
     *
     * @param  float    $costs - 
     * @return void
     */
    public function setCosts($costs)
    {
        if(!$this->getValidator()->assertNotEmpty('costs', $costs))
            return;
        
        $this->costs = $costs;
    }
    // End setCosts
    

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
     * Returns the property versionId
     *
     * @return int
     */
    public function getVersionId()
    {
        return $this->versionId;
    }
    // End getVersionId
    

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
     * Returns the property costs
     *
     * @return float
     */
    public function getCosts()
    {
        return $this->costs;
    }
    // End getCosts
    

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


    public static function nameExists($versionId, $name, $force = false)
    {
        return self::findByVersionIdAndName($versionId, $name, $force)->isInitialized();
    }

    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET version_id     = :versionId
                             , name           = :name
                             , costs          = :costs
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'versionId'     => $this->versionId,
                                        'name'          => $this->name,
                                        'costs'         => $this->costs)
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
        $this->id             = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, version_id, name, costs)
                               VALUES  (:id, :versionId, :name, :costs)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'versionId'     => $this->versionId,
                                        'name'          => $this->name,
                                        'costs'         => $this->costs)
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
        $this->id             = (int)$DO->id;
        $this->versionId      = (int)$DO->version_id;
        $this->name           = $DO->name;
        $this->costs          = $DO->costs;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccEnergySourceCost