<?php
namespace Elca\Db;

use Beibob\Blibs\DbObject;
use PDO;

class ElcaProjectKwk extends DbObject
{
    const TABLE_NAME = 'elca.project_kwks';

    private $id;

    private $projectVariantId;

    private $name;

    /**
     * heating in kWh/(m2*a)
     */
    private $heating;

    /**
     * water in kWh/(m2*a)
     */
    private $water;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                 => PDO::PARAM_INT,
                                        'projectVariantId'   => PDO::PARAM_INT,
                                        'name'               => PDO::PARAM_STR,
                                        'heating'          => PDO::PARAM_STR,
                                        'water'            => PDO::PARAM_STR,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  string   $name              -
     * @return ElcaProjectKwk
     */
    public static function create($projectVariantId, $name, $heating = null, $water = null)
    {
        $projectKwk = new ElcaProjectKwk();
        $projectKwk->setProjectVariantId($projectVariantId);
        $projectKwk->setName($name);
        $projectKwk->setHeating($heating);
        $projectKwk->setWater($water);

        if($projectKwk->getValidator()->isValid())
            $projectKwk->insert();
        
        return $projectKwk;
    }
    // End create
    

    /**
     * Inits a `ElcaProjectKwk' by its primary key
     *
     * @param  int      $id    - 
     * @param  bool     $force - Bypass caching
     * @return ElcaProjectKwk
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectKwk();
        
        $sql = sprintf("SELECT id
                             , project_variant_id
                             , name
                             , heating
                             , water
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }

    public static function findByProjectVariantId($projectVariantId, $force = false)
    {
        if (!$projectVariantId)
            return new ElcaProjectKwk();

        $sql = sprintf("SELECT id
                             , project_variant_id
                             , name
                             , heating
                             , water
                          FROM %s
                         WHERE project_variant_id = :projectVariantId 
                         LIMIT 1"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId), $force);
    }


    public function copy($newProjectVariantId)
    {
        $copy = self::create($newProjectVariantId, $this->getName(), $this->heating, $this->water);

        /**
         * @var ElcaProjectFinalEnergyDemand $finalEnergyDemand
         */
        foreach (ElcaProjectFinalEnergyDemandSet::findByKwkId($this->getId(), ['id' => 'ASC']) as $finalEnergyDemand) {
            $finalEnergyDemand->copy($newProjectVariantId, $copy->getId());
        }

        return $copy;
    }

    /**
     * Sets the property projectVariantId
     *
     * @param  int      $kwkId -
     * @return void
     */
    public function setProjectVariantId($kwkId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectVariantId', $kwkId))
            return;
        
        $this->projectVariantId = (int)$kwkId;
    }

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
        
        if(!$this->getValidator()->assertMaxLength('name', 250, $name))
            return;
        
        $this->name = (string)$name;
    }

    public function setHeating($heating = null): void
    {
        $this->heating = $heating;
    }

    public function setWater($water = null): void
    {
        $this->water = $water;
    }

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


    /**
     * Returns the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getHeating()
    {
        return $this->heating;
    }

    public function getWater()
    {
        return $this->water;
    }


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
                           SET project_variant_id = :projectVariantId
                             , name               = :name
                             , heating            = :heating
                             , water              = :water
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'                => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'name'              => $this->name,
                                        'heating' => $this->heating,
                                        'water' => $this->water,
                                  )
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
        
        $sql = sprintf("INSERT INTO %s (id, project_variant_id, name, heating, water)
                               VALUES  (:id, :projectVariantId, :name, :heating, :water)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'                => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'name'              => $this->name,
                                        'heating' => $this->heating,
                                        'water' => $this->water,
                                  )
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
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->name               = $DO->name;
        $this->heating = null !== $DO->heating ? (int)$DO->heating : null;
        $this->water = null !== $DO->water ? (int)$DO->water : null;

        /**
         * Set extensions
         */
    }
}
