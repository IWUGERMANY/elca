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
class ElcaCacheElementComponent extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.element_components';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * itemId
     */
    private $itemId;

    /**
     * elementComponentId
     */
    private $elementComponentId;

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
     * numReplacemenents
     */
    private $numReplacements;

    /**
     * Primary key
     */
    private static $primaryKey = ['itemId'];

    /**
     * Column types
     */
    private static $columnTypes = ['itemId'             => PDO::PARAM_INT,
                                        'elementComponentId' => PDO::PARAM_INT,
                                        'mass'               => PDO::PARAM_STR,
                                        'quantity'           => PDO::PARAM_STR,
                                        'refUnit'            => PDO::PARAM_STR,
                                        'numReplacements'    => PDO::PARAM_INT];

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
     * @param  integer  $elementComponentId - elementComponentId
     * @param  number  $mass              - mass of the element
     * @param  number  $quantity          - quantity
     * @param  string   $refUnit           - refUnit
     * @param  integer  $numReplacements   - numReplacemenents
     * @param  integer  $itemId            - itemId
     */
    public static function create($elementComponentId, $mass = null, $quantity = null, $refUnit = null, $numReplacements = null, $itemId = null)
    {
        $Dbh = DbHandle::getInstance();

        try
        {
            $Dbh->begin();

            if(is_null($itemId))
            {
                $Component = ElcaElementComponent::findById($elementComponentId);

                if(!$Component->isInitialized())
                    throw new Exception('elementComponentId is not a valid component: no elementId found');

                $CacheElement = ElcaCacheElement::findByElementId($Component->getElementId());

                if(!$CacheElement->isInitialized())
                    $CacheElement = ElcaCacheElement::create($Component->getElementId());

                $projectId = $Component->getElement()->getProjectVariant()->getProjectId();
                $itemId = ElcaCacheItem::create($projectId, get_class(), $CacheElement->getItemId())->getId();
            }

            $ElcaCacheElementComponent = new ElcaCacheElementComponent();
            $ElcaCacheElementComponent->setItemId($itemId);
            $ElcaCacheElementComponent->setElementComponentId($elementComponentId);
            $ElcaCacheElementComponent->setMass($mass);
            $ElcaCacheElementComponent->setQuantity($quantity);
            $ElcaCacheElementComponent->setRefUnit($refUnit);
            $ElcaCacheElementComponent->setNumReplacements($numReplacements);

            if($ElcaCacheElementComponent->getValidator()->isValid())
                $ElcaCacheElementComponent->insert();

            $Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $Dbh->rollback();
            throw $Exception;
        }

        return $ElcaCacheElementComponent;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaCacheElementComponent' by its primary key
     *
     * @param  integer  $itemId - itemId
     * @param  boolean  $force - Bypass caching
     * @return ElcaCacheElementComponent
     */
    public static function findByItemId($itemId, $force = false)
    {
        if(!$itemId)
            return new ElcaCacheElementComponent();

        $sql = sprintf("SELECT item_id
                             , element_component_id
                             , mass
                             , quantity
                             , ref_unit
                             , num_replacements
                          FROM %s
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['itemId' => $itemId], $force);
    }
    // End findByItemId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaCacheElementComponent' by its unique key (elementComponentId)
     *
     * @param  integer  $elementComponentId - elementComponentId
     * @param  boolean  $force             - Bypass caching
     * @return ElcaCacheElementComponent
     */
    public static function findByElementComponentId($elementComponentId, $force = false)
    {
        if(!$elementComponentId)
            return new ElcaCacheElementComponent();

        $sql = sprintf("SELECT item_id
                             , element_component_id
                             , mass
                             , quantity
                             , ref_unit
                             , num_replacements
                          FROM %s
                         WHERE element_component_id = :elementComponentId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['elementComponentId' => $elementComponentId], $force);
    }
    // End findByElementComponentId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy from this cache element component
     *
     * @param  int $elementComponentId - new elementComponentId
     * @return ElcaElementComponent - the new element component copy
     */
    public function copy($elementComponentId)
    {
        if(!$this->isInitialized() || !$elementComponentId)
            return new ElcaCacheElementComponent();

        $Copy = self::create($elementComponentId,
                             $this->mass,
                             $this->quantity,
                             $this->refUnit,
                             $this->numReplacements);

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
     * Sets the property elementComponentId
     *
     * @param  integer  $elementComponentId - elementComponentId
     * @return
     */
    public function setElementComponentId($elementComponentId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementComponentId', $elementComponentId))
            return;

        $this->elementComponentId = (int)$elementComponentId;
    }
    // End setElementComponentId

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
     * @param  string   $refUnit - refUnit
     * @return
     */
    public function setRefUnit($refUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('refUnit', 10, $refUnit))
            return;

        $this->refUnit = $refUnit;
    }
    // End setRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property numReplacements
     *
     * @param  integer  $numReplacements - numReplacemenents
     * @return
     */
    public function setNumReplacements($numReplacements = null)
    {
        $this->numReplacements = $numReplacements;
    }
    // End setNumReplacements

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
     * Returns the property elementComponentId
     *
     * @return integer
     */
    public function getElementComponentId()
    {
        return $this->elementComponentId;
    }
    // End getElementComponentId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaElementComponent by property elementComponentId
     *
     * @param  boolean  $force
     * @return ElcaElementComponent
     */
    public function getElementComponent($force = false)
    {
        return ElcaElementComponent::findById($this->elementComponentId, $force);
    }
    // End getElementComponent

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
     * Returns the property numReplacements
     *
     * @return integer
     */
    public function getNumReplacements()
    {
        return $this->numReplacements;
    }
    // End getNumReplacements

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
                           SET element_component_id = :elementComponentId
                             , mass               = :mass
                             , quantity           = :quantity
                             , ref_unit           = :refUnit
                             , num_replacements   = :numReplacements
                         WHERE item_id = :itemId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['itemId'            => $this->itemId,
                                        'elementComponentId' => $this->elementComponentId,
                                        'mass'              => $this->mass,
                                        'quantity'          => $this->quantity,
                                        'refUnit'           => $this->refUnit,
                                        'numReplacements'   => $this->numReplacements]
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

        $sql = sprintf("INSERT INTO %s (item_id, element_component_id, mass, quantity, ref_unit, num_replacements)
                               VALUES  (:itemId, :elementComponentId, :mass, :quantity, :refUnit, :numReplacements)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['itemId'            => $this->itemId,
                                        'elementComponentId' => $this->elementComponentId,
                                        'mass'              => $this->mass,
                                        'quantity'          => $this->quantity,
                                        'refUnit'           => $this->refUnit,
                                        'numReplacements'   => $this->numReplacements]
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
        $this->itemId             = (int)$DO->item_id;
        $this->elementComponentId = (int)$DO->element_component_id;
        $this->mass               = $DO->mass;
        $this->quantity           = $DO->quantity;
        $this->refUnit            = $DO->ref_unit;
        $this->numReplacements    = $DO->num_replacements;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheElementComponent