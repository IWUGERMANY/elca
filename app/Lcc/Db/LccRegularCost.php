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

namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use Exception;
use PDO;
/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccRegularCost
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccRegularCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.regular_costs';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * costId
     */
    private $costId;

    /**
     * refValue
     */
    private $refValue;

    /**
     * refUnit
     */
    private $refUnit;

    /**
     * Primary key
     */
    private static $primaryKey = ['costId'];

    /**
     * Column types
     */
    private static $columnTypes = ['costId'         => PDO::PARAM_INT,
                                        'refValue'       => PDO::PARAM_STR,
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
     * @param  integer  $costId  - costId
     * @param  number  $refValue - refValue
     * @param  string   $refUnit - refUnit
     */
    public static function create($costId, $refValue, $refUnit = null)
    {
        $LccRegularCost = new LccRegularCost();
        $LccRegularCost->setCostId($costId);
        $LccRegularCost->setRefValue($refValue);
        $LccRegularCost->setRefUnit($refUnit);

        if($LccRegularCost->getValidator()->isValid())
            $LccRegularCost->insert();

        return $LccRegularCost;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccRegularCost' by its primary key
     *
     * @param  integer  $costId - costId
     * @param  boolean  $force - Bypass caching
     * @return LccRegularCost
     */
    public static function findByCostId($costId, $force = false)
    {
        if(!$costId)
            return new LccRegularCost();

        $sql = sprintf("SELECT cost_id
                             , ref_value
                             , ref_unit
                          FROM %s
                         WHERE cost_id = :costId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['costId' => $costId], $force);
    }
    // End findByCostId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of this with a new versionId
     *
     * @param  int $versionId
     * @return LccRegularCost
     */
    public function copy($versionId)
    {
        if(!$versionId || !$this->isInitialized())
            return new LccRegularCost();

        try
        {
            $this->Dbh->begin();

            $Cost = $this->getCost();
            $CostCopy = $Cost->copy($versionId);

            $Copy = self::create($CostCopy->getId(),
                                 $this->refValue,
                                 $this->refUnit
                                 );

            $this->Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property costId
     *
     * @param  integer  $costId - costId
     * @return
     */
    public function setCostId($costId)
    {
        if(!$this->getValidator()->assertNotEmpty('costId', $costId))
            return;

        $this->costId = (int)$costId;
    }
    // End setCostId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refValue
     *
     * @param  number  $refValue - refValue
     * @return
     */
    public function setRefValue($refValue)
    {
        $this->refValue = $refValue;
    }
    // End setRefValue

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refUnit
     *
     * @param  string   $refUnit - refUnit
     * @return
     */
    public function setRefUnit($refUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('refUnit', 30, $refUnit))
            return;

        $this->refUnit = $refUnit;
    }
    // End setRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property costId
     *
     * @return integer
     */
    public function getCostId()
    {
        return $this->costId;
    }
    // End getCostId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated LccCost by property costId
     *
     * @param  boolean  $force
     * @return LccCost
     */
    public function getCost($force = false)
    {
        return LccCost::findById($this->costId, $force);
    }
    // End getCost

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property refValue
     *
     * @return number
     */
    public function getRefValue()
    {
        return $this->refValue;
    }
    // End getRefValue

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
     * Checks, if the object exists
     *
     * @param  integer  $costId - costId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($costId, $force = false)
    {
        return self::findByCostId($costId, $force)->isInitialized();
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
                           SET ref_value      = :refValue
                             , ref_unit       = :refUnit
                         WHERE cost_id = :costId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['costId'        => $this->costId,
                                        'refValue'      => $this->refValue,
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
                              WHERE cost_id = :costId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  ['costId' => $this->costId]);
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

        $sql = sprintf("INSERT INTO %s (cost_id, ref_value, ref_unit)
                               VALUES  (:costId, :refValue, :refUnit)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['costId'        => $this->costId,
                                        'refValue'      => $this->refValue,
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
        $this->costId         = (int)$DO->cost_id;
        $this->refValue       = $DO->ref_value;
        $this->refUnit        = $DO->ref_unit;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccRegularCost