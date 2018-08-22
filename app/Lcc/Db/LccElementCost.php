<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\Elca;
use Exception;
use Lcc\LccModule;
use PDO;

/**
 * 
 *
 * @package    lcc
 * @class      LccElementCost
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2015 BEIBOB Medienfreunde
 */
class LccElementCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.element_costs';
    const UPDATE_ELEMENT_COSTS = 'lcc.update_element_costs';

    /**
     * elementId
     */
    private $elementId;

    /**
     * quantity
     */
    private $quantity;

    /**
     * life time
     */
    private $lifeTime;

    /**
     * calculated quantity
     */
    private $calculatedQuantity;

    /**
     * Primary key
     */
    private static $primaryKey = array('elementId');

    /**
     * Column types
     */
    private static $columnTypes = array('elementId'          => PDO::PARAM_INT,
                                        'lifeTime'           => PDO::PARAM_INT,
                                        'quantity'           => PDO::PARAM_STR,
                                        'calculatedQuantity' => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $elementId         - elementId
     * @param  float    $quantity          - quantity
     * @return LccElementCost
     */
    public static function create($elementId, $quantity = null, $lifeTime = null)
    {
        $LccElementCost = new LccElementCost();
        $LccElementCost->setElementId($elementId);
        $LccElementCost->setQuantity($quantity);
        $LccElementCost->setLifeTime($lifeTime);

        if($LccElementCost->getValidator()->isValid())
            $LccElementCost->insert();
        
        return $LccElementCost;
    }
    // End create
    

    /**
     * Inits a `LccElementCost' by its primary key
     *
     * @param  int      $elementId - elementId
     * @param  bool     $force    - Bypass caching
     * @return LccElementCost
     */
    public static function findByElementId($elementId, $force = false)
    {
        if(!$elementId)
            return new LccElementCost();
        
        $sql = sprintf("SELECT element_id
                             , quantity
                             , life_time
                             , calculated_quantity
                          FROM %s
                         WHERE element_id = :elementId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('elementId' => $elementId), $force);
    }
    // End findByElementId

    /**
     * @param int $newElementId
     * @return LccElementCost
     */
    public function copy($newElementId)
    {
        if (!$this->isInitialized()) {
            return new self();
        }

        $copy = self::create(
            $newElementId,
            $this->quantity,
            $this->lifeTime
        );

        $newElement = ElcaElement::findById($newElementId);

        if ($newElement->isComposite()) {
            $originalElement = ElcaElement::findById($this->elementId);

            $originalElements = $originalElement->getCompositeElements(['id' => 'ASC']);
            $newElements = $newElement->getCompositeElements(['id' => 'ASC']);

            foreach ($originalElements as $index => $assignment) {
                $elementCost = LccElementCost::findByElementId($assignment->getElementId());

                if ($elementCost->isInitialized() && isset($newElements[$index])) {
                    $elementCost->copy($newElements[$index]->getElementId());
                }
            }

        } else {

            $originalComponents = ElcaElementComponentSet::findByElementId($this->elementId, [], ['id' => 'ASC']);
            $copiedComponents = ElcaElementComponentSet::findByElementId($newElementId, [], ['id' => 'ASC']);

            foreach ($originalComponents as $index => $elementComponent) {
                $elementComponentCost = LccElementComponentCost::findByElementComponentId($elementComponent->getId());

                if ($elementComponentCost->isInitialized() && isset($copiedComponents[$index])) {
                    $elementComponentCost->copy($copiedComponents[$index]->getId());
                }
            }
        }

        return $copy;
    }


    /**
     * Sets the property elementId
     *
     * @param  int      $elementId - elementId
     * @return void
     */
    public function setElementId($elementId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementId', $elementId))
            return;
        
        $this->elementId = (int)$elementId;
    }
    // End setElementId
    

    /**
     * Sets the property quantity
     *
     * @param  float    $quantity - quantity
     * @return void
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;
    }

    /**
     * @param mixed $lifeTime
     */
    public function setLifeTime($lifeTime)
    {
        $this->lifeTime = $lifeTime;
    }
    // End setQuantity
    

    /**
     * Sets the property calculatedQuantity
     *
     * @param  float    $calculatedQuantity - calculated quantity
     * @return void
     */
    public function setCalculatedQuantity($calculatedQuantity = null)
    {
        $this->calculatedQuantity = $calculatedQuantity;
    }
    // End setCalculatedQuantity
    

    /**
     * Returns the property elementId
     *
     * @return int
     */
    public function getElementId()
    {
        return $this->elementId;
    }
    // End getElementId
    

    /**
     * Returns the associated ElcaElement by property elementId
     *
     * @param  bool     $force
     * @return ElcaElement
     */
    public function getElement($force = false)
    {
        return ElcaElement::findById($this->elementId, $force);
    }
    // End getElement
    

    /**
     * Returns the property quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return bool
     */
    public function hasLifeTime()
    {
        return $this->lifeTime !== null;
    }

    /**
     * @return mixed
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }

    /**
     * Returns the property calculatedQuantity
     *
     * @return float
     */
    public function getCalculatedQuantity()
    {
        return $this->calculatedQuantity;
    }
    // End getCalculatedQuantity


    /**
     * Returns the default life time for the elements element type
     */
    public static function getDefaultLifeTime(ElcaElement $element)
    {
        $projectVersion = LccProjectVersion::findByPK($element->getProjectVariantId(), LccModule::CALC_METHOD_DETAILED);
        $irregularCost = LccIrregularCost::findKGUByVersionIdGroupingAndDin276Code($projectVersion->getVersionId(), $element->getElementTypeNode()->getDinCode());

        return $irregularCost->isInitialized()? $irregularCost->getLifeTime() : Elca::DEFAULT_LIFE_TIME;
    }


    /**
     * Checks, if the object exists
     *
     * @param  int      $elementId - elementId
     * @param  bool     $force    - Bypass caching
     * @return bool
     */
    public static function exists($elementId, $force = false)
    {
        return self::findByElementId($elementId, $force)->isInitialized();
    }
    // End exists
    

    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        try {
            $this->Dbh->begin();

            $sql = sprintf(
                "UPDATE %s
                               SET quantity           = :quantity
                                 , life_time          = :lifeTime
                             WHERE element_id = :elementId"
                ,
                self::TABLE_NAME
            );

            $result = $this->updateBySql(
                $sql,
                array(
                    'elementId' => $this->elementId,
                    'quantity'  => $this->quantity,
                    'lifeTime'  => $this->lifeTime
                )
            );

            $this->updateCalculatedQuantity();
            $this->Dbh->commit();
        } catch (Exception $e) {
            $this->Dbh->rollback();
            throw $e;
        }

        return $result;
    }
    // End update

    /**
     * @throws \Beibob\Blibs\Exception
     */
    public function updateCalculatedQuantity()
    {
        return;
        if (!$this->isInitialized()) {
            return;
        }

        $this->calculatedQuantity = $this->queryExpression(
            sprintf('%s(:elementId)', self::UPDATE_ELEMENT_COSTS),
            ['elementId' => $this->elementId]
        );
    }

    /**
     * Deletes the object from the table
     *
     * @return bool
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE element_id = :elementId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('elementId' => $this->elementId));
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
        try {
            $this->Dbh->begin();

            $sql = sprintf(
                "INSERT INTO %s (element_id, quantity, life_time)
                               VALUES  (:elementId, :quantity, :lifeTime)"
                , self::TABLE_NAME
            );

            $result = $this->insertBySql(
                $sql,
                array(
                    'elementId' => $this->elementId,
                    'quantity'  => $this->quantity,
                    'lifeTime'  => $this->lifeTime
                )
            );

            $this->updateCalculatedQuantity();

            $this->Dbh->commit();
        } catch (Exception $e) {
            $this->Dbh->rollback();
            throw $e;
        }

        return $result;
    }

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return bool
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->elementId          = (int)$DO->element_id;
        $this->quantity           = $DO->quantity;
        $this->lifeTime           = $DO->life_time;
        $this->calculatedQuantity = $DO->calculated_quantity;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
