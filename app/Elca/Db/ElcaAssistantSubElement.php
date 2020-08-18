<?php
namespace Elca\Db;

use Beibob\Blibs\DbObject;
use PDO;

/**
 * 
 *
 * @package    -
 * @class      ElcaAssistantSubElement
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2020 BEIBOB Medienfreunde
 */
class ElcaAssistantSubElement extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.assistant_sub_elements';

    /**
     * 
     */
    private $assistantElementId;

    /**
     * 
     */
    private $elementId;

    /**
     * 
     */
    private $ident;

    /**
     * Primary key
     */
    private static $primaryKey = array('assistantElementId', 'elementId');

    /**
     * Column types
     */
    private static $columnTypes = array('assistantElementId' => PDO::PARAM_INT,
                                        'elementId'          => PDO::PARAM_INT,
                                        'ident'              => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $assistantElementId - 
     * @param  int      $elementId         - 
     * @param  string   $ident             - 
     * @return ElcaAssistantSubElement
     */
    public static function create($assistantElementId, $elementId, $ident)
    {
        $ElcaAssistantSubElement = new ElcaAssistantSubElement();
        $ElcaAssistantSubElement->setAssistantElementId($assistantElementId);
        $ElcaAssistantSubElement->setElementId($elementId);
        $ElcaAssistantSubElement->setIdent($ident);
        
        if($ElcaAssistantSubElement->getValidator()->isValid())
            $ElcaAssistantSubElement->insert();
        
        return $ElcaAssistantSubElement;
    }
    // End create
    

    /**
     * Inits a `ElcaAssistantSubElement' by its primary key
     *
     * @param  int      $assistantElementId - 
     * @param  int      $elementId         - 
     * @param  bool     $force             - Bypass caching
     * @return ElcaAssistantSubElement
     */
    public static function findByPk($assistantElementId, $elementId, $force = false)
    {
        if(!$assistantElementId || !$elementId)
            return new ElcaAssistantSubElement();
        
        $sql = sprintf("SELECT assistant_element_id
                             , element_id
                             , ident
                          FROM %s
                         WHERE assistant_element_id = :assistantElementId
                           AND element_id = :elementId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, ['assistantElementId' => $assistantElementId, 'elementId' => $elementId], $force);
    }
    // End findByPk

    public static function findByAssistantElementIdAndIdent($assistantElementId, $ident, $force = false) : ElcaAssistantSubElement
    {
        if(!$assistantElementId || !$ident)
            return new ElcaAssistantSubElement();

        $sql = sprintf("SELECT assistant_element_id
                             , element_id
                             , ident
                          FROM %s
                         WHERE assistant_element_id = :assistantElementId
                           AND ident = :ident"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['assistantElementId' => $assistantElementId, 'ident' => $ident], $force);
    }

    public static function findByElementId($elementId, $force = false) : ElcaAssistantSubElement
    {
        if(!$elementId)
            return new ElcaAssistantSubElement();

        $sql = sprintf("SELECT assistant_element_id
                             , element_id
                             , ident
                          FROM %s
                         WHERE element_id = :elementId"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['elementId' => $elementId], $force);
    }

    public static function isSubElement($elementId)
    {
        return self::findByElementId($elementId)->isInitialized();
    }

    public function copy($newAssistantElementId, $newElementId)
    {
        return self::create($newAssistantElementId, $newElementId, $this->ident);
    }

    /**
     * Sets the property assistantElementId
     *
     * @param  int      $assistantElementId - 
     * @return void
     */
    public function setAssistantElementId($assistantElementId)
    {
        if(!$this->getValidator()->assertNotEmpty('assistantElementId', $assistantElementId))
            return;
        
        $this->assistantElementId = (int)$assistantElementId;
    }
    // End setAssistantElementId
    

    /**
     * Sets the property elementId
     *
     * @param  int      $elementId - 
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
     * Sets the property ident
     *
     * @param  string   $ident - 
     * @return void
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;
        
        if(!$this->getValidator()->assertMaxLength('ident', 200, $ident))
            return;
        
        $this->ident = (string)$ident;
    }
    // End setIdent
    

    /**
     * Returns the property assistantElementId
     *
     * @return int
     */
    public function getAssistantElementId()
    {
        return $this->assistantElementId;
    }
    // End getAssistantElementId
    

    /**
     * Returns the associated ElcaAssistantElement by property assistantElementId
     *
     * @param  bool     $force
     * @return ElcaAssistantElement
     */
    public function getAssistantElement($force = false)
    {
        return ElcaAssistantElement::findById($this->assistantElementId, $force);
    }
    // End getAssistantElement
    

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
     * Returns the property ident
     *
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getIdent
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $assistantElementId - 
     * @param  int      $elementId         - 
     * @param  bool     $force             - Bypass caching
     * @return bool
     */
    public static function exists($assistantElementId, $elementId, $force = false)
    {
        return self::findByPk($assistantElementId, $elementId, $force)->isInitialized();
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
                           SET ident              = :ident
                         WHERE assistant_element_id = :assistantElementId
                           AND element_id = :elementId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('assistantElementId' => $this->assistantElementId,
                                        'elementId'         => $this->elementId,
                                        'ident'             => $this->ident)
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
                              WHERE assistant_element_id = :assistantElementId
                                AND element_id = :elementId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('assistantElementId' => $this->assistantElementId, 'elementId' => $this->elementId));
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
        
        $sql = sprintf("INSERT INTO %s (assistant_element_id, element_id, ident)
                               VALUES  (:assistantElementId, :elementId, :ident)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('assistantElementId' => $this->assistantElementId,
                                        'elementId'         => $this->elementId,
                                        'ident'             => $this->ident)
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
        $this->assistantElementId = (int)$DO->assistant_element_id;
        $this->elementId          = (int)$DO->element_id;
        $this->ident              = $DO->ident;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaAssistantSubElement