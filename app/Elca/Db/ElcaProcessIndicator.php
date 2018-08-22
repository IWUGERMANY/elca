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
class ElcaProcessIndicator extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_indicators';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * processIndicatorId
     */
    private $id;

    /**
     * process id
     */
    private $processId;

    /**
     * indicator id
     */
    private $indicatorId;

    /**
     * indicator value
     */
    private $value;

    /**
     * Extensions
     */
    private $indicatorIdent;
    private $indicatorName;
    private $indicatorUnit;
    private $isEn15804Compliant;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'processId'      => PDO::PARAM_INT,
                                        'indicatorId'    => PDO::PARAM_INT,
                                        'value'          => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array(
        'indicator_name'  => PDO::PARAM_STR,
        'indicator_ident' => PDO::PARAM_STR,
        'indicator_unit'  => PDO::PARAM_STR,
        'isEn15804Compliant' => PDO::PARAM_BOOL,
    );

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer  $processId  - process id
     * @param  integer  $indicatorId - indicator id
     * @param  number  $value      - indicator value
     */
    public static function create($processId, $indicatorId, $value)
    {
        $ElcaProcessIndicator = new ElcaProcessIndicator();
        $ElcaProcessIndicator->setProcessId($processId);
        $ElcaProcessIndicator->setIndicatorId($indicatorId);
        $ElcaProcessIndicator->setValue($value);

        if($ElcaProcessIndicator->getValidator()->isValid())
            $ElcaProcessIndicator->insert();

        return $ElcaProcessIndicator;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessIndicator' by its primary key
     *
     * @param  integer  $id    - processIndicatorId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessIndicator
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProcessIndicator();

        $sql = sprintf("SELECT id
                             , process_id
                             , indicator_id
                             , value
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessIndicator' by its unique key (processId, indicatorId)
     *
     * @param  integer  $processId  - process id
     * @param  integer  $indicatorId - indicator id
     * @param  boolean  $force      - Bypass caching
     * @return ElcaProcessIndicator
     */
    public static function findByProcessIdAndIndicatorId($processId, $indicatorId, $force = false)
    {
        if(!$processId || !$indicatorId)
            return new ElcaProcessIndicator();

        $sql = sprintf("SELECT id
                             , process_id
                             , indicator_id
                             , value
                          FROM %s
                         WHERE process_id = :processId
                           AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('processId' => $processId, 'indicatorId' => $indicatorId), $force);
    }
    // End findByProcessIdAndIndicatorId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of the current process indicator
     *
     * @param  int $processId
     * @return ElcaProcessIndicator
     */
    public function copy($processId)
    {
        if(!$this->isInitialized() || !$processId)
            return new ElcaProcesIndicators();

        /**
         * Create copy
         */
        return self::create($processId,
                            $this->indicatorId,
                            $this->value);
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processId
     *
     * @param  integer  $processId - process id
     * @return
     */
    public function setProcessId($processId)
    {
        if(!$this->getValidator()->assertNotEmpty('processId', $processId))
            return;

        $this->processId = (int)$processId;
    }
    // End setProcessId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property indicatorId
     *
     * @param  integer  $indicatorId - indicator id
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
     * Sets the property value
     *
     * @param  number  $value - indicator value
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
     * Returns the property id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    // End getId

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
     * Returns the property value
     *
     * @return number
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getIndicatorIdent()
    {
        return $this->indicatorIdent ?? $this->getIndicator()->getIdent();
    }

    public function getIndicatorName()
    {
        return $this->indicatorName ?? $this->getIndicator()->getName();
    }

    public function getIndicatorUnit()
    {
        return $this->indicatorUnit ?? $this->getIndicator()->getUnit();
    }

    public function getIsEn15804Compliant()
    {
        return $this->isEn15804Compliant ?? $this->getIndicator()->isEn15804Compliant();
    }

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - processIndicatorId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
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
                           SET process_id     = :processId
                             , indicator_id   = :indicatorId
                             , value          = :value
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'processId'     => $this->processId,
                                        'indicatorId'   => $this->indicatorId,
                                        'value'         => $this->value)
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
                              WHERE id = :id"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('id' => $this->id));
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
        $this->id             = $this->getNextSequenceValue();

        $sql = sprintf("INSERT INTO %s (id, process_id, indicator_id, value)
                               VALUES  (:id, :processId, :indicatorId, :value)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'processId'     => $this->processId,
                                        'indicatorId'   => $this->indicatorId,
                                        'value'         => $this->value)
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
        $this->id             = (int)$DO->id;
        $this->processId      = (int)$DO->process_id;
        $this->indicatorId    = (int)$DO->indicator_id;
        $this->value          = $DO->value;

        /**
         * Set extensions
         */
        $this->indicatorName = $DO->indicator_name ?? null;
        $this->indicatorIdent = $DO->indicator_ident ?? null;
        $this->indicatorUnit = $DO->indicator_unit ?? null;
        $this->isEn15804Compliant = $DO->is_en15804_compliant ?? null;
    }
    // End initByDataObject
}
// End class ElcaProcessIndicator