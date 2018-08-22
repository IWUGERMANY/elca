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

use Beibob\Blibs\DbHandle;
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
class ElcaCacheElement extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.elements';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * itemId
     */
    private $itemId;

    /**
     * elementId
     */
    private $elementId;

    /**
     * compositeItemId
     */
    private $compositeItemId;

    /**
     * mass of the element
     */
    private $mass;

    /**
     * quantity
     */
    private $quantity;

    /**
     * refUnit
     */
    private $refUnit;

    /**
     * Primary key
     */
    private static $primaryKey = ['itemId'];

    /**
     * Column types
     */
    private static $columnTypes = ['itemId'         => PDO::PARAM_INT,
                                        'elementId'      => PDO::PARAM_INT,
                                        'compositeItemId' => PDO::PARAM_INT,
                                        'mass'           => PDO::PARAM_STR,
                                        'quantity'       => PDO::PARAM_STR,
                                        'refUnit'        => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer  $elementId - elementId
     * @param  number  $mass     - mass of the element
     * @param  number  $quantity - quantity
     * @param  number  $refUnit  - refUnit
     * @param  integer  $itemId   - itemId
     */
    public static function create($elementId, $mass = null, $quantity = null, $refUnit = null, $compositeItemId = null, $itemId = null)
    {
        $Dbh = DbHandle::getInstance();

        try
        {
            $Dbh->begin();

            if(is_null($itemId))
            {
                $Element = ElcaElement::findById($elementId);
                $CElementType = ElcaCacheElementType::findByProjectVariantIdAndElementTypeNodeId($Element->getProjectVariantId(), $Element->getElementTypeNodeId());

                if(!$CElementType->isInitialized())
                    $CElementType = ElcaCacheElementType::create($Element->getProjectVariantId(), $Element->getElementTypeNodeId());

                /**
                 * Composite elements must set the virtual flag on the item to avoid
                 * aggregating their results up the element type cache tree
                 * (prevents double counting)
                 */
                $isVirtual = $Element->isComposite() || $Element->getElementTypeNode()->isCompositeLevel();
                $projectVariant = $Element->getProjectVariant();

                $itemId = ElcaCacheItem::create($projectVariant->getProjectId(), get_class(), $CElementType->getItemId(), $isVirtual)->getId();
            }

            $ElcaCacheElement = new ElcaCacheElement();
            $ElcaCacheElement->setItemId($itemId);
            $ElcaCacheElement->setElementId($elementId);
            $ElcaCacheElement->setCompositeItemId($compositeItemId);
            $ElcaCacheElement->setMass($mass);
            $ElcaCacheElement->setQuantity($quantity);
            $ElcaCacheElement->setRefUnit($refUnit);

            if($ElcaCacheElement->getValidator()->isValid())
                $ElcaCacheElement->insert();

            $Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $Dbh->rollback();
            throw $Exception;
        }

        return $ElcaCacheElement;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaCacheElement' by its primary key
     *
     * @param  integer  $itemId - itemId
     * @param  boolean  $force - Bypass caching
     * @return ElcaCacheElement
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheElement();

        $sql = sprintf("SELECT item_id
                             , element_id
                             , composite_item_id
                             , mass
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['itemId' => $itemId], $force);
    }
    // End findByItemId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaCacheElement' by its unique key (elementId)
     *
     * @param  integer  $elementId - elementId
     * @param  boolean  $force    - Bypass caching
     * @return ElcaCacheElement
     */
    public static function findByElementId($elementId, $force = false)
    {
        if(!$elementId)
            return new ElcaCacheElement();

        $sql = sprintf("SELECT item_id
                             , element_id
                             , composite_item_id
                             , mass
                             , quantity
                             , ref_unit
                          FROM %s
                         WHERE element_id = :elementId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['elementId' => $elementId], $force);
    }
    // End findByElementId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy from this cache element
     *
     * @param  int $elementId - new elementId
     * @return ElcaCacheElement - the new element copy
     */
    public function copy($elementId, $compositeItemId = null)
    {
        if(!$this->isInitialized() || !$elementId)
            return new ElcaCacheElement();

        $Copy = self::create($elementId,
                             $this->mass,
                             $this->quantity,
                             $this->refUnit,
                             $compositeItemId
                             );
        /**
         * Copy indicator values
         */
        ElcaCacheIndicatorSet::copy($this->getItemId(), $Copy->getItemId());

        return $Copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property itemId
     *
     * @param  integer  $itemId - itemId
     * @return
     */
    public function setItemId($itemId)
    {
        if(!$this->getValidator()->assertNotEmpty('itemId', $itemId))
            return;

        $this->itemId = (int)$itemId;
    }
    // End setItemId

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property compositeItemId
     *
     * @param  integer  $compositeItemId - compositeItemId
     * @return
     */
    public function setCompositeItemId($elementId = null)
    {
        $this->compositeItemId = $elementId;
    }
    // End setCompositeItemId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property mass
     *
     * @param  number  $mass  - mass of the element
     * @return
     */
    public function setMass($mass = null)
    {
        $this->mass = $mass;
    }
    // End setMass

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property quantity
     *
     * @param  number  $quantity - quantity
     * @return
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;
    }
    // End setQuantity

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refUnit
     *
     * @param  string  $refUnit - refUnit
     * @return
     */
    public function setRefUnit($refUnit = null)
    {
        $this->refUnit = $refUnit;
    }
    // End setRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }
    // End getItemId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaCacheItem by property itemId
     *
     * @param  boolean  $force
     * @return ElcaCacheItem
     */
    public function getItem($force = false)
    {
        return ElcaCacheItem::findById($this->itemId, $force);
    }
    // End getItem

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property compositeItemId
     *
     * @return integer
     */
    public function getCompositeItemId()
    {
        return $this->compositeItemId;
    }
    // End getCompositeItemId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaElement by property compositeItemId
     *
     * @param  boolean  $force
     * @return ElcaElement
     */
    public function getCompositeElement($force = false)
    {
        return ElcaElement::findById($this->compositeItemId, $force);
    }
    // End getCompositeElement

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property mass
     *
     * @return number
     */
    public function getMass()
    {
        return $this->mass;
    }
    // End getMass

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property quantity
     *
     * @return number
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    // End getQuantity

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property refUnit
     *
     * @return string
     */
    public function getRefUnit()
    {
        return $this->refUnit;
    }
    // End getRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets this outdated
     *
     * @param  boolean  $isOutdated - if it is outdated, it needs updating
     * @return
     */
    public function setIsOutdated($isOutdated = true)
    {
        $Item = $this->getItem();
        $Item->setIsOutdated($isOutdated);
        $Item->update();
    }
    // End setIsOutdated

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $itemId - itemId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($itemId, $force = false)
    {
        return self::findByItemId($itemId, $force)->isInitialized();
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
        $sql = sprintf("UPDATE %s
                           SET element_id     = :elementId
                             , composite_item_id = :compositeItemId
                             , mass           = :mass
                             , quantity       = :quantity
                             , ref_unit       = :refUnit
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['itemId'        => $this->itemId,
                                        'elementId'     => $this->elementId,
                                        'compositeItemId' => $this->compositeItemId,
                                        'mass'          => $this->mass,
                                        'quantity'      => $this->quantity,
                                        'refUnit'       => $this->refUnit]
                                  );
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
                              WHERE item_id = :itemId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  ['itemId' => $this->itemId]);
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

        $primaryKey = [];

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
        $sql = sprintf("INSERT INTO %s (item_id, element_id, composite_item_id, mass, quantity, ref_unit)
                               VALUES  (:itemId, :elementId, :compositeItemId, :mass, :quantity, :refUnit)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['itemId'        => $this->itemId,
                                        'elementId'     => $this->elementId,
                                        'compositeItemId' => $this->compositeItemId,
                                        'mass'          => $this->mass,
                                        'quantity'      => $this->quantity,
                                        'refUnit'       => $this->refUnit]
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
        $this->itemId         = (int)$DO->item_id;
        $this->elementId      = (int)$DO->element_id;
        $this->compositeItemId = $DO->composite_item_id;
        $this->mass           = $DO->mass;
        $this->quantity       = $DO->quantity;
        $this->refUnit        = $DO->ref_unit;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheElement