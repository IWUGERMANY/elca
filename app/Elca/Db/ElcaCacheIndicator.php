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
class ElcaCacheIndicator extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca_cache.indicators';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * itemId
     */
    private $itemId;

    /**
     * life cycle ident
     */
    private $lifeCycleIdent;

    /**
     * indicator_id
     */
    private $indicatorId;

    /**
     * process_id
     */
    private $processId;

    /**
     * value
     */
    private $value;

    /**
     * info about ratio
     */
    private $ratio;

    /**
     * marks the values as part of a series
     */
    private $isPartial;

    /**
     * Primary key
     */
    private static $primaryKey = array('itemId', 'lifeCycleIdent', 'indicatorId', 'processId');

    /**
     * Column types
     */
    private static $columnTypes = array('itemId'         => PDO::PARAM_INT,
                                        'lifeCycleIdent' => PDO::PARAM_STR,
                                        'indicatorId'    => PDO::PARAM_INT,
                                        'processId'      => PDO::PARAM_INT,
                                        'value'          => PDO::PARAM_STR,
                                        'ratio'          => PDO::PARAM_STR,
                                        'isPartial'      => PDO::PARAM_BOOL);

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
     * @param  integer  $itemId        - itemId
     * @param  string   $lifeCycleIdent - life cycle ident
     * @param  integer  $indicatorId   - indicator_id
     * @param  number  $value         - value
     * @param  integer  $processId     - process_id
     * @param  number  $ratio         - info about ratio
     * @param  boolean  $isPartial     - marks the values as part of a series
     */
    public static function create($itemId, $lifeCycleIdent, $indicatorId, $value, $processId = null, $ratio = 1, $isPartial = false)
    {
        $ElcaCacheIndicator = new ElcaCacheIndicator();
        $ElcaCacheIndicator->setItemId($itemId);
        $ElcaCacheIndicator->setLifeCycleIdent($lifeCycleIdent);
        $ElcaCacheIndicator->setIndicatorId($indicatorId);
        $ElcaCacheIndicator->setValue($value);
        $ElcaCacheIndicator->setProcessId($processId);
        $ElcaCacheIndicator->setRatio($ratio);
        $ElcaCacheIndicator->setIsPartial($isPartial);

        if($ElcaCacheIndicator->getValidator()->isValid())
            $ElcaCacheIndicator->insert();

        return $ElcaCacheIndicator;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaCacheIndicator' by its primary key
     *
     * @param  integer  $itemId        - itemId
     * @param  string   $lifeCycleIdent - life cycle ident
     * @param  integer  $indicatorId   - indicator_id
     * @param  integer  $processId     - process_id
     * @param  boolean  $force         - Bypass caching
     * @return ElcaCacheIndicator
     */
    public static function findByPk($itemId, $lifeCycleIdent, $indicatorId, $processId = null, $force = false)
    {
        if(!$itemId || !$lifeCycleIdent || !$indicatorId)
            return new ElcaCacheIndicator();

        $initValues = array('itemId' => $itemId, 'lifeCycleIdent' => $lifeCycleIdent, 'indicatorId' => $indicatorId);

        if(!is_null($processId))
            $initValues['processId'] = $processId;

        $sql = sprintf("SELECT item_id
                             , life_cycle_ident
                             , indicator_id
                             , process_id
                             , value
                             , ratio
                             , is_partial
                          FROM %s
                         WHERE item_id = :itemId
                           AND life_cycle_ident = :lifeCycleIdent
                           AND indicator_id = :indicatorId
                           AND process_id %s"
                       , self::TABLE_NAME
                       , is_null($processId)? 'IS NULL' : '= :processId'
                       );

        return self::findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByPk

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
     * Sets the property lifeCycleIdent
     *
     * @param  string   $lifeCycleIdent - life cycle ident
     * @return
     */
    public function setLifeCycleIdent($lifeCycleIdent)
    {
        if(!$this->getValidator()->assertNotEmpty('lifeCycleIdent', $lifeCycleIdent))
            return;

        if(!$this->getValidator()->assertMaxLength('lifeCycleIdent', 20, $lifeCycleIdent))
            return;

        $this->lifeCycleIdent = (string)$lifeCycleIdent;
    }
    // End setLifeCycleIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property indicatorId
     *
     * @param  integer  $indicatorId - indicator_id
     * @return
     */
    public function setIndicatorId($indicatorId)
    {
        if(!$this->getValidator()->assertNotEmpty('indicatorId', $indicatorId))
            return;

        $this->indicatorId = (int)$indicatorId;
    }
    // End setIndicatorId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processId
     *
     * @param  integer  $processId - process_id
     * @return
     */
    public function setProcessId($processId = null)
    {
        $this->processId = $processId;
    }
    // End setProcessId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property value
     *
     * @param  number  $value - value
     * @return
     */
    public function setValue($value)
    {
        if(!$this->getValidator()->assertNotEmpty('value', $value))
            return;

        $this->value = $value;
    }
    // End setValue

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ratio
     *
     * @param  number  $ratio - info about ratio
     * @return
     */
    public function setRatio($ratio = 1)
    {
        $this->ratio = $ratio;
    }
    // End setRatio

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property isPartial
     *
     * @param  boolean  $isPartial - marks the values as part of a series
     * @return
     */
    public function setIsPartial($isPartial = false)
    {
        $this->isPartial = (bool)$isPartial;
    }
    // End setIsPartial

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
     * Returns the property lifeCycleIdent
     *
     * @return string
     */
    public function getLifeCycleIdent()
    {
        return $this->lifeCycleIdent;
    }
    // End getLifeCycleIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaLifeCycle by property lifeCycleIdent
     *
     * @param  boolean  $force
     * @return ElcaLifeCycle
     */
    public function getLifeCycleIde($force = false)
    {
        return ElcaLifeCycle::findByIdent($this->lifeCycleIdent, $force);
    }
    // End getLifeCycleIde

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property indicatorId
     *
     * @return integer
     */
    public function getIndicatorId()
    {
        return $this->indicatorId;
    }
    // End getIndicatorId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaIndicator by property indicatorId
     *
     * @param  boolean  $force
     * @return ElcaIndicator
     */
    public function getIndicator($force = false)
    {
        return ElcaIndicator::findById($this->indicatorId, $force);
    }
    // End getIndicator

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property processId
     *
     * @return integer
     */
    public function getProcessId()
    {
        return $this->processId;
    }
    // End getProcessId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcess by property processId
     *
     * @param  boolean  $force
     * @return ElcaProcess
     */
    public function getProcess($force = false)
    {
        return ElcaProcess::findById($this->processId, $force);
    }
    // End getProcess

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property value
     *
     * @return number
     */
    public function getValue()
    {
        return $this->value;
    }
    // End getValue

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property ratio
     *
     * @return number
     */
    public function getRatio()
    {
        return $this->ratio;
    }
    // End getRatio

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property isPartial
     *
     * @return boolean
     */
    public function isPartial()
    {
        return $this->isPartial;
    }
    // End isPartial

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $itemId        - itemId
     * @param  string   $lifeCycleIdent - life cycle ident
     * @param  integer  $indicatorId   - indicator_id
     * @param  integer  $processId     - process_id
     * @param  boolean  $force         - Bypass caching
     * @return boolean
     */
    public static function exists($itemId, $lifeCycleIdent, $indicatorId, $processId, $force = false)
    {
        return self::findByPk($itemId, $lifeCycleIdent, $indicatorId, $processId, $force)->isInitialized();
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
        $initValues = array('itemId'        => $this->itemId,
                            'lifeCycleIdent' => $this->lifeCycleIdent,
                            'indicatorId'   => $this->indicatorId,
                            'value'         => $this->value,
                            'ratio'         => $this->ratio,
                            'isPartial'     => $this->isPartial);

        if(!is_null($this->processId))
            $initValues['processId'] = $this->processId;

        $sql = sprintf("UPDATE %s
                           SET value          = :value
                             , ratio          = :ratio
                             , is_partial     = :isPartial
                         WHERE item_id = :itemId
                           AND life_cycle_ident = :lifeCycleIdent
                           AND indicator_id = :indicatorId
                           AND process_id %s"
                       , self::TABLE_NAME
                       , is_null($this->processId)? 'IS NULL' : ' = :processId'
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
                              WHERE item_id = :itemId
                                AND life_cycle_ident = :lifeCycleIdent
                                AND indicator_id = :indicatorId
                                AND process_id = :processId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('itemId' => $this->itemId, 'lifeCycleIdent' => $this->lifeCycleIdent, 'indicatorId' => $this->indicatorId, 'processId' => $this->processId));
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

        $sql = sprintf("INSERT INTO %s (item_id, life_cycle_ident, indicator_id, process_id, value, ratio, is_partial)
                               VALUES  (:itemId, :lifeCycleIdent, :indicatorId, :processId, :value, :ratio, :isPartial)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('itemId'        => $this->itemId,
                                        'lifeCycleIdent' => $this->lifeCycleIdent,
                                        'indicatorId'   => $this->indicatorId,
                                        'processId'     => $this->processId,
                                        'value'         => $this->value,
                                        'ratio'         => $this->ratio,
                                        'isPartial'     => $this->isPartial)
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
        $this->lifeCycleIdent = $DO->life_cycle_ident;
        $this->indicatorId    = (int)$DO->indicator_id;
        $this->processId      = $DO->process_id;
        $this->value          = $DO->value;
        $this->ratio          = $DO->ratio;
        $this->isPartial      = (bool)$DO->is_partial;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaCacheIndicator