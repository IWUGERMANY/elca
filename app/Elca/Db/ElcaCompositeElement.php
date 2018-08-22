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
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaCompositeElement extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.composite_elements';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * compositeElementId
     */
    private $compositeElementId;

    /**
     * element position within composite
     */
    private $position;

    /**
     * old element position (for update)
     */
    private $oldPosition;

    /**
     * element
     */
    private $elementId;

    /**
     * Composite Element
     */
    private $CompositeElement;

    /**
     * Assigned Element
     */
    private $Element;

    /**
     * Primary key
     */
    private static $primaryKey = array('compositeElementId', 'position');

    /**
     * Column types
     */
    private static $columnTypes = array('compositeElementId' => PDO::PARAM_INT,
                                        'elementId'          => PDO::PARAM_INT,
                                        'position'           => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer  $compositeElementId - compositeElementId
     * @param  integer  $elementId         - element
     * @param  integer  $position          - element position within composite
     */
    public static function create($compositeElementId, $position, $elementId)
    {
        $ElcaCompositeElement = new ElcaCompositeElement();
        $ElcaCompositeElement->setCompositeElementId($compositeElementId);
        $ElcaCompositeElement->setElementId($elementId);
        $ElcaCompositeElement->setPosition($position);

        if($ElcaCompositeElement->getValidator()->isValid())
            $ElcaCompositeElement->insert();

        return $ElcaCompositeElement;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaCompositeElement' by its primary key
     *
     * @param  integer  $compositeElementId - compositeElementId
     * @param  integer  $elementId         - element
     * @param  integer  $position          - position
     * @param  boolean  $force             - Bypass caching
     * @return ElcaCompositeElement
     */
    public static function findProjectCompositeByIdAndElementId($compositeElementId, $elementId, $force = false)
    {
        if (!$compositeElementId || !$elementId) {
            return new ElcaCompositeElement();
        }

        $sql = sprintf('SELECT composite_element_id
                             , element_id
                             , position
                          FROM %s ce
                          JOIN %s e ON e.id = ce.composite_element_id 
                         WHERE (composite_element_id, element_id) = (:compositeElementId, :elementId)
                           AND  e.project_variant_id IS NOT NULL'
                       , self::TABLE_NAME
                       , ElcaElement::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['compositeElementId' => $compositeElementId, 'elementId' => $elementId], $force);
    }

    /**
     * Inits a `ElcaCompositeElement' by its primary key
     *
     * @param  integer  $compositeElementId - compositeElementId
     * @param  integer  $elementId         - element
     * @param  integer  $position          - position
     * @param  boolean  $force             - Bypass caching
     * @return ElcaCompositeElement
     */
    public static function findByPk($compositeElementId, $position, $force = false)
    {
        if(!$compositeElementId || !$position)
            return new ElcaCompositeElement();

        $sql = sprintf("SELECT composite_element_id
                             , element_id
                             , position
                          FROM %s
                         WHERE composite_element_id = :compositeElementId
                           AND position = :position"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('compositeElementId' => $compositeElementId, 'position' => $position), $force);
    }
    // End findByPk

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get the max position by composite elementId
     *
     * @return int
     */
    public static function getMaxCompositePosition($compositeElementId)
    {
        $sql = sprintf('SELECT max(position) AS max_position FROM %s WHERE composite_element_id = :elementId'
                       , self::TABLE_NAME);

        $Stmt = self::prepareStatement($sql, array('elementId' => $compositeElementId));
        $Stmt->execute();

        if(!$DataObject = $Stmt->fetchObject())
            return null;

        return $DataObject->max_position;
    }
    // End getMaxCompositePosition

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get the max opaque position by composite elementId
     *
     * @return int
     */
    public static function getMaxOpaquePosition($compositeElementId)
    {
        $sql = sprintf('SELECT max(position) AS max_position
                          FROM %s c
                          JOIN %s e ON e.id = c.element_id
                          JOIN %s t ON t.node_id = e.element_type_node_id
                         WHERE c.composite_element_id = :elementId
                           AND t.is_opaque IS DISTINCT FROM false'
                       , self::TABLE_NAME
                       , ElcaElement::TABLE_NAME
                       , ElcaElementType::TABLE_NAME
                       );

        $Stmt = self::prepareStatement($sql, array('elementId' => $compositeElementId));
        $Stmt->execute();

        if(!$DataObject = $Stmt->fetchObject())
            return null;

        return $DataObject->max_position;
    }
    // End getMaxCompositePosition

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property compositeElementId
     *
     * @param  integer  $compositeElementId - compositeElementId
     * @return
     */
    public function setCompositeElementId($compositeElementId)
    {
        if(!$this->getValidator()->assertNotEmpty('compositeElementId', $compositeElementId))
            return;

        $this->compositeElementId = (int)$compositeElementId;
    }
    // End setCompositeElementId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property elementId
     *
     * @param  integer  $elementId - element
     * @return
     */
    public function setElementId($elementId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementId', $elementId))
            return;

        $this->elementId = (int)$elementId;
    }
    // End setElementId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property position
     *
     * @param  integer  $position - element position within composite
     * @return
     */
    public function setPosition($position)
    {
        if(!$this->getValidator()->assertNotEmpty('position', $position))
            return;

        $this->oldPosition = $this->position;
        $this->position = (int)$position;
    }
    // End setPosition

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property compositeElementId
     *
     * @return integer
     */
    public function getCompositeElementId()
    {
        return $this->compositeElementId;
    }
    // End getCompositeElementId

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }
    // End getPosition

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the assigned element
     *
     * @return ElcaElement
     */
    public function getElement($force = false)
    {
        if(!$force &&
           $this->Element instanceOf ElcaElement &&
           $this->Element->getId() == $this->elementId)
            return $this->Element;

        return $this->Element = ElcaElement::findById($this->elementId);
    }
    // End getElement

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the composite element
     *
     * @return ElcaElement
     */
    public function getCompositeElement($force = false)
    {
        if(!$force &&
           $this->CompositeElement instanceOf ElcaElement &&
           $this->CompositeElement->getId() == $this->compositeElementId)
            return $this->CompositeElement;

        return $this->CompositeElement = ElcaElement::findById($this->compositeElementId);
    }
    // End getCompositeElement

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $compositeElementId - compositeElementId
     * @param  integer  $position          - position
     * @param  boolean  $force             - Bypass caching
     * @return boolean
     */
    public static function exists($compositeElementId, $position, $force = false)
    {
        return self::findByPk($compositeElementId, $position, $force)->isInitialized();
    }
    // End exists

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the relation between two elements exists
     *
     * @param  integer  $compositeElementId - compositeElementId
     * @param  integer  $elementId         - element
     * @param  boolean  $force             - Bypass caching
     * @return boolean
     */
    public static function relationExists($compositeElementId, $elementId, $force = false)
    {
        return (bool)ElcaCompositeElementSet::dbCount(array('composite_element_id' => $compositeElementId,
                                                            'element_id' => $elementId), $force);
    }
    // End exists

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $initValues = array('compositeElementId' => $this->compositeElementId,
                            'elementId'         => $this->elementId,
                            'position'          => $this->position);

        $newPosSql = null;
        if($this->oldPosition != $this->newPosition)
        {
            $newPosSql = ', position = :newPosition';
            $initValues['newPosition'] = $this->position;
            $initValues['position'] = $this->oldPosition;
       }

        $sql = sprintf("UPDATE %s
                           SET element_id = :elementId
                            %s
                         WHERE composite_element_id = :compositeElementId
                           AND position = :position"
                       , self::TABLE_NAME
                       , $newPosSql
                       );

        return $this->updateBySql($sql, $initValues);
    }
    // End update

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE composite_element_id = :compositeElementId
                                AND position = :position"
                       , self::TABLE_NAME
                       );

        return $this->deleteBySql($sql,
                                  array('compositeElementId' => $this->compositeElementId,
                                        'position' => $this->position));
   }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $sql = sprintf("INSERT INTO %s (composite_element_id, element_id, position)
                               VALUES  (:compositeElementId, :elementId, :position)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('compositeElementId' => $this->compositeElementId,
                                        'elementId'         => $this->elementId,
                                        'position'          => $this->position)
                                  );
    }
    // End insert

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return boolean
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->compositeElementId = (int)$DO->composite_element_id;
        $this->elementId          = (int)$DO->element_id;
        $this->position           = (int)$DO->position;

        /**
         * Set extensions
         */
        if(isset($DO->id) && $DO->id == $this->elementId)
            $this->Element = ElcaElement::initByDO($DO);

        elseif(isset($DO->id) && $DO->id == $this->compositeElementId)
            $this->CompositeElement = ElcaElement::initByDO($DO);
    }
    // End initByDataObject
}
// End class ElcaCompositeElement