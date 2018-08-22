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
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementAttribute extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.element_attributes';



    /**
     * elementAttributeId
     */
    private $id;

    /**
     * elementId
     */
    private $elementId;

    /**
     * attribute identifier
     */
    private $ident;

    /**
     * attribute caption
     */
    private $caption;

    /**
     * number value
     */
    private $numericValue;

    /**
     * text value
     */
    private $textValue;

    /**
     * ext: element name
     */
    private $elementName;

    /**
     * ext: element type node name
     */
    private $elementTypeNodeName;

    /**
     * ext: element type node din code
     */
    private $elementTypeNodeDinCode;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'elementId'      => PDO::PARAM_INT,
                                        'ident'          => PDO::PARAM_STR,
                                        'caption'        => PDO::PARAM_STR,
                                        'numericValue'   => PDO::PARAM_STR,
                                        'textValue'      => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('elementName' => PDO::PARAM_STR,
                                           'elementTypeNodeName' => PDO::PARAM_STR,
                                           'elementTypeNodeDinCode' => PDO::PARAM_STR);


    // public


    /**
     * Creates the object
     *
     * @param  integer $elementId    - elementId
     * @param  string  $ident        - attribute identifier
     * @param  string  $caption      - attribute caption
     * @param  number $numericValue - number value
     * @param  string  $textValue    - text value
     * @return ElcaElementAttribute
     */
    public static function create($elementId, $ident, $caption, $numericValue = null, $textValue = null)
    {
        $ElcaElementAttribute = new ElcaElementAttribute();
        $ElcaElementAttribute->setElementId($elementId);
        $ElcaElementAttribute->setIdent($ident);
        $ElcaElementAttribute->setCaption($caption);
        $ElcaElementAttribute->setNumericValue($numericValue);
        $ElcaElementAttribute->setTextValue($textValue);

        if($ElcaElementAttribute->getValidator()->isValid())
            $ElcaElementAttribute->insert();

        return $ElcaElementAttribute;
    }
    // End create



    /**
     * Inits a `ElcaElementAttribute' by its primary key
     *
     * @param  integer  $id    - elementAttributeId
     * @param  boolean  $force - Bypass caching
     * @return ElcaElementAttribute
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaElementAttribute();

        $sql = sprintf("SELECT id
                             , element_id
                             , ident
                             , caption
                             , numeric_value
                             , text_value
                             , text_value
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById



    /**
     * Inits a `ElcaElementAttribute' by its unique key (elementId, ident)
     *
     * @param  integer  $elementId - elementId
     * @param  string   $ident    - attribute identifier
     * @param  boolean  $force    - Bypass caching
     * @return ElcaElementAttribute
     */
    public static function findByElementIdAndIdent($elementId, $ident, $force = false)
    {
        if(!$elementId || !$ident)
            return new ElcaElementAttribute();

        $sql = sprintf("SELECT id
                             , element_id
                             , ident
                             , caption
                             , numeric_value
                             , text_value
                          FROM %s
                         WHERE element_id = :elementId
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('elementId' => $elementId, 'ident' => $ident), $force);
    }
    // End findByElementIdAndIdent


    /**
     * Inits a `ElcaElementAttribute' by its unique key (elementId, ident)
     *
     * @param  integer  $elementId - elementId
     * @param  string   $ident    - attribute identifier
     * @param  boolean  $force    - Bypass caching
     * @return ElcaElementAttribute
     */
    public static function findWithinProjectVariantByElementIdAndIdent($elementId, $ident, $projectVariantId = null, $force = false)
    {
        if(!$elementId || !$ident)
            return new ElcaElementAttribute();

        $sql = sprintf("SELECT a.id
                             , a.element_id
                             , a.ident
                             , a.caption
                             , a.numeric_value
                             , a.text_value
                          FROM %s a
                          JOIN %s e ON e.id = a.element_id
                         WHERE e.project_variant_id %s
                           AND a.element_id = :elementId
                           AND a.ident = :ident"
            , self::TABLE_NAME
            , ElcaElement::TABLE_NAME
            , $projectVariantId === null ? 'IS NULL' : '= '. $projectVariantId .'::int'
        );

        return self::findBySql(get_class(), $sql, array('elementId' => $elementId, 'ident' => $ident), $force);
    }
    // End findByElementIdAndIdent


    /**
     * Sets the property elementId
     *
     * @param  integer  $elementId - elementId
     * @return
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
     * @param  string   $ident - attribute identifier
     * @return
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;

        if(!$this->getValidator()->assertMaxLength('ident', 150, $ident))
            return;

        $this->ident = (string)$ident;
    }
    // End setIdent



    /**
     * Sets the property caption
     *
     * @param  string   $caption - attribute caption
     * @return
     */
    public function setCaption($caption)
    {
        if(!$this->getValidator()->assertNotEmpty('caption', $caption))
            return;

        if(!$this->getValidator()->assertMaxLength('caption', 150, $caption))
            return;

        $this->caption = (string)$caption;
    }
    // End setCaption



    /**
     * Sets the property numericValue
     *
     * @param  number  $numericValue - number value
     * @return
     */
    public function setNumericValue($numericValue = null)
    {
        $this->numericValue = $numericValue;
    }
    // End setNumericValue



    /**
     * Sets the property textValue
     *
     * @param  string   $textValue - text value
     * @return
     */
    public function setTextValue($textValue = null)
    {
        $this->textValue = $textValue;
    }
    // End setTextValue



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
     * Returns the property elementId
     *
     * @return integer
     */
    public function getElementId()
    {
        return $this->elementId;
    }
    // End getElementId



    /**
     * Returns the associated ElcaElement by property elementId
     *
     * @param  boolean  $force
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
     * Returns the property caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }
    // End getCaption



    /**
     * Returns the property numericValue
     *
     * @return number
     */
    public function getNumericValue()
    {
        return $this->numericValue;
    }
    // End getNumericValue



    /**
     * Returns the property textValue
     *
     * @return string
     */
    public function getTextValue()
    {
        return $this->textValue;
    }
    // End getTextValue


    /**
     * Returns the extension property elementName
     *
     * @return string
     */
    public function getElementName()
    {
        return isset($this->elementName)? $this->elementName : $this->getElement()->getName();
    }
    // End getElementName


    /**
     * Returns the extension property elementTypeNodeName
     *
     * @return string
     */
    public function getElementTypeNodeName()
    {
        return isset($this->elementTypeNodeName)? $this->elementTypeNodeName : $this->getElement()->getElementTypeNode()->getName();
    }
    // End getElementTypeNodeName

    /**
     * Returns the extension property elementTypeNodeRefNum
     *
     * @return string
     */
    public function getElementTypeNodeDinCode()
    {
        return isset($this->elementTypeNodeDinCode)? $this->elementTypeNodeDinCode : $this->getElement()->getElementTypeNode()->getDinCode();
    }
    // End getElementTypeNodeRefNum

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - elementAttributeId
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
                           SET element_id     = :elementId
                             , ident          = :ident
                             , caption        = :caption
                             , numeric_value  = :numericValue
                             , text_value     = :textValue
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'elementId'     => $this->elementId,
                                        'ident'         => $this->ident,
                                        'caption'       => $this->caption,
                                        'numericValue'  => $this->numericValue,
                                        'textValue'     => $this->textValue)
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
        $this->id             = $this->getNextSequenceValue();

        $sql = sprintf("INSERT INTO %s (id, element_id, ident, caption, numeric_value, text_value)
                               VALUES  (:id, :elementId, :ident, :caption, :numericValue, :textValue)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'elementId'     => $this->elementId,
                                        'ident'         => $this->ident,
                                        'caption'       => $this->caption,
                                        'numericValue'  => $this->numericValue,
                                        'textValue'     => $this->textValue)
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
        $this->id             = (int)$DO->id;
        $this->elementId      = (int)$DO->element_id;
        $this->ident          = $DO->ident;
        $this->caption        = $DO->caption;
        $this->numericValue   = $DO->numeric_value;
        $this->textValue      = $DO->text_value;

        /**
         * Set extensions
         */
        if (isset($DO->element_name)) $this->elementName = $DO->element_name;
        if (isset($DO->element_type_node_name)) $this->elementTypeNodeName = $DO->element_type_node_name;
        if (isset($DO->element_type_node_din_code)) $this->elementTypeNodeDinCode = $DO->element_type_node_din_code;
    }
    // End initByDataObject
}
// End class ElcaElementAttribute