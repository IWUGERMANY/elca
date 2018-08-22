<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use Elca\Db\ElcaElementComponent;
use PDO;

/**
 * 
 *
 * @package    -
 * @class      LccElementComponentCost
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2015 BEIBOB Medienfreunde
 */
class LccElementComponentCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.element_component_costs';

    /**
     * elementComponentId
     */
    private $elementComponentId;

    /**
     * quantity
     */
    private $quantity;

    /**
     * extended fields
     */
    private $elementId;
    private $componentQuantity;
    private $processConfigId;
    private $isLayer;
    private $layerPosition;
    private $layerSiblingId;
    private $isExtant;
    private $lifeTime;
    private $lifeTimeDelay;

    /**
     * Primary key
     */
    private static $primaryKey = array('elementComponentId');

    /**
     * Column types
     */
    private static $columnTypes = [
        'elementComponentId' => PDO::PARAM_INT,
        'quantity'           => PDO::PARAM_STR,
        'elementId'          => PDO::PARAM_INT,
        'componentQuantity'  => PDO::PARAM_STR,
        'processConfigId'    => PDO::PARAM_INT,
        'isLayer'            => PDO::PARAM_BOOL,
        'layerPosition'      => PDO::PARAM_INT,
        'layerSiblingId'     => PDO::PARAM_INT,
        'isExtant'           => PDO::PARAM_BOOL,
        'lifeTime'           => PDO::PARAM_INT,
        'lifeTimeDelay'      => PDO::PARAM_INT
    ];

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $elementComponentId - elementComponentId
     * @param  float    $quantity          - quantity
     * @return LccElementComponentCost
     */
    public static function create($elementComponentId, $quantity)
    {
        $LccElementComponentCost = new LccElementComponentCost();
        $LccElementComponentCost->setElementComponentId($elementComponentId);
        $LccElementComponentCost->setQuantity($quantity);
        
        if($LccElementComponentCost->getValidator()->isValid())
            $LccElementComponentCost->insert();
        
        return $LccElementComponentCost;
    }
    // End create
    

    /**
     * Inits a `LccElementComponentCost' by its primary key
     *
     * @param  int      $elementComponentId - elementComponentId
     * @param  bool     $force             - Bypass caching
     * @return LccElementComponentCost
     */
    public static function findByElementComponentId($elementComponentId, $force = false)
    {
        if(!$elementComponentId)
            return new LccElementComponentCost();
        
        $sql = sprintf("SELECT element_component_id
                             , quantity
                          FROM %s
                         WHERE element_component_id = :elementComponentId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('elementComponentId' => $elementComponentId), $force);
    }
    // End findByElementComponentId


    /**
     * @param int $newElementComponentId
     * @return LccElementComponentCost
     */
    public function copy($newElementComponentId)
    {
        return self::create(
            $newElementComponentId,
            $this->quantity
        );
    }


    /**
     * Sets the property elementComponentId
     *
     * @param  int      $elementComponentId - elementComponentId
     * @return void
     */
    public function setElementComponentId($elementComponentId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementComponentId', $elementComponentId))
            return;
        
        $this->elementComponentId = (int)$elementComponentId;
    }
    // End setElementComponentId
    

    /**
     * Sets the property quantity
     *
     * @param  float    $quantity - quantity
     * @return void
     */
    public function setQuantity($quantity)
    {
        if(!$this->getValidator()->assertNotEmpty('quantity', $quantity))
            return;
        
        $this->quantity = $quantity;
    }
    // End setQuantity
    

    /**
     * Returns the property elementComponentId
     *
     * @return int
     */
    public function getElementComponentId()
    {
        return $this->elementComponentId;
    }
    // End getElementComponentId
    

    /**
     * Returns the associated ElcaElementComponent by property elementComponentId
     *
     * @param  bool     $force
     * @return ElcaElementComponent
     */
    public function getElementComponent($force = false)
    {
        return ElcaElementComponent::findById($this->elementComponentId, $force);
    }
    // End getElementComponent
    

    /**
     * Returns the property quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    // End getQuantity


    /**
     * @return mixed
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @return mixed
     */
    public function getComponentQuantity()
    {
        return $this->componentQuantity;
    }

    /**
     * @return mixed
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }

    /**
     * @return mixed
     */
    public function getIsLayer()
    {
        return $this->isLayer;
    }

    /**
     * @return mixed
     */
    public function getLayerPosition()
    {
        return $this->layerPosition;
    }

    /**
     * @return mixed
     */
    public function getLayerSiblingId()
    {
        return $this->layerSiblingId;
    }

    /**
     * @return mixed
     */
    public function getIsExtant()
    {
        return $this->isExtant;
    }

    /**
     * @return mixed
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }

    /**
     * @return mixed
     */
    public function getLifeTimeDelay()
    {
        return $this->lifeTimeDelay;
    }


    /**
     * Checks, if the object exists
     *
     * @param  int      $elementComponentId - elementComponentId
     * @param  bool     $force             - Bypass caching
     * @return bool
     */
    public static function exists($elementComponentId, $force = false)
    {
        return self::findByElementComponentId($elementComponentId, $force)->isInitialized();
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
                           SET quantity           = :quantity
                         WHERE element_component_id = :elementComponentId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('elementComponentId' => $this->elementComponentId,
                                        'quantity'          => $this->quantity)
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
                              WHERE element_component_id = :elementComponentId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('elementComponentId' => $this->elementComponentId));
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
        
        $sql = sprintf("INSERT INTO %s (element_component_id, quantity)
                               VALUES  (:elementComponentId, :quantity)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('elementComponentId' => $this->elementComponentId,
                                        'quantity'          => $this->quantity)
                                  );
    }



    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return bool
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->elementComponentId = (int)$DO->element_component_id;
        $this->quantity           = $DO->quantity;
        
        /**
         * Set extensions
         */
        if (isset($DO->element_id)) $this->elementComponentId = $DO->element_component_id;
        if (isset($DO->component_quantity)) $this->quantity = $DO->quantity;
        if (isset($DO->process_config_id)) $this->elementId = $DO->element_id;
        if (isset($DO->is_layer)) $this->componentQuantity = $DO->component_quantity;
        if (isset($DO->layer_position)) $this->processConfigId = $DO->process_config_id;
        if (isset($DO->layer_sibling_id)) $this->isLayer = (bool)$DO->is_layer;
        if (isset($DO->is_extant)) $this->layerPosition = $DO->layer_position;
        if (isset($DO->life_time)) $this->layerSiblingId = $DO->layer_sibling_id;
        if (isset($DO->life_time_delay)) $this->isExtant = (bool)$DO->is_extant;
    }
    // End initByDataObject
}
// End class LccElementComponentCost