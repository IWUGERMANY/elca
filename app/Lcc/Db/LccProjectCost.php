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
use PDO;

/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccProjectCost
 * @author     Fabian Möller <fab@beibob.de>
 * @author     Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccProjectCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.project_costs';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * projectVariantId
     */
    private $projectVariantId;
    private $calcMethod;

    /**
     * regularCostId
     */
    private $costId;

    /**
     * quantity
     */
    private $quantity;

    /**
     * refValue
     */
    private $refValue;

    private $energySourceCostId;

    /**
     * Primary key
     */
    private static $primaryKey = array('projectVariantId', 'calcMethod', 'costId');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'projectVariantId' => PDO::PARAM_INT,
        'calcMethod'       => PDO::PARAM_INT,
        'costId'           => PDO::PARAM_INT,
        'quantity'         => PDO::PARAM_STR,
        'refValue'         => PDO::PARAM_STR,
        'energySourceCostId' => PDO::PARAM_INT,
    );

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
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  integer $costId           - regularCostId
     * @param  number  $quantity         - quantity
     * @param  number  $refValue         - refValue
     * @return LccProjectCost
     */
    public static function create($projectVariantId, $calcMethod, $costId, $quantity = null, $refValue = null, $energySourceCostId = null)
    {
        $LccProjectCost = new LccProjectCost();
        $LccProjectCost->setProjectVariantId($projectVariantId);
        $LccProjectCost->setCalcMethod($calcMethod);
        $LccProjectCost->setCostId($costId);
        $LccProjectCost->setQuantity($quantity);
        $LccProjectCost->setRefValue($refValue);
        $LccProjectCost->setEnergySourceCostId($energySourceCostId);

        if ($LccProjectCost->getValidator()->isValid()) {
            $LccProjectCost->insert();
        }

        return $LccProjectCost;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccProjectCost' by its primary key
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param  integer $costId           - regularCostId
     * @param  boolean $force            - Bypass caching
     * @return LccProjectCost
     */
    public static function findByPk($projectVariantId, $calcMethod, $costId, $force = false)
    {
        if (!$projectVariantId || !$costId) {
            return new LccProjectCost();
        }

        $sql = sprintf(
            "SELECT project_variant_id
                             , calc_method
                             , cost_id
                             , quantity
                             , ref_value
                             , energy_source_cost_id
                          FROM %s
                         WHERE (project_variant_id, calc_method, cost_id) = (:projectVariantId, :calcMethod, :costId)"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(
            get_class(),
            $sql,
            [
                'projectVariantId' => $projectVariantId,
                'calcMethod'       => $calcMethod,
                'costId'           => $costId
            ],
            $force
        );
    }
    // End findByPk

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of this project costs
     *
     * @param  $newProjectVariantId
     * @return LccProjectCost
     */
    public function copy($projectVariantId)
    {
        if (!$this->isInitialized()) {
            return new LccProjectCost();
        }

        return self::create(
            $projectVariantId,
            $this->calcMethod,
            $this->costId,
            $this->quantity,
            $this->refValue,
            $this->energySourceCostId
        );
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property projectVariantId
     *
     * @param  integer $projectVariantId - projectVariantId
     * @return
     */
    public function setProjectVariantId($projectVariantId)
    {
        if (!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId)) {
            return;
        }

        $this->projectVariantId = (int)$projectVariantId;
    }
    // End setProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property costId
     *
     * @param  integer $costId - regularCostId
     * @return
     */
    public function setCostId($costId)
    {
        if (!$this->getValidator()->assertNotEmpty('costId', $costId)) {
            return;
        }

        $this->costId = (int)$costId;
    }
    // End setCostId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property quantity
     *
     * @param  number $quantity - quantity
     * @return
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;
    }
    // End setQuantity

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property refValue
     *
     * @param  number $refValue - refValue
     * @return
     */
    public function setRefValue($refValue = null)
    {
        $this->refValue = $refValue;
    }
    // End setRefValue

    public function setEnergySourceCostId($energySourceCostId)
    {
        $this->energySourceCostId = $energySourceCostId;
    }
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property projectVariantId
     *
     * @return integer
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId

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
     * @param  boolean $force
     * @return LccCost
     */
    public function getCost($force = false)
    {
        return LccCost::findById($this->costId, $force);
    }
    // End getCost

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
     * Returns the property refValue
     *
     * @return number
     */
    public function getRefValue()
    {
        return $this->refValue;
    }
    // End getRefValue

    /**
     * @return mixed
     */
    public function getCalcMethod()
    {
        return $this->calcMethod;
    }

    /**
     * @param mixed $calcMethod
     */
    public function setCalcMethod($calcMethod)
    {
        $this->calcMethod = $calcMethod;
    }

    public function getEnergySourceCostId()
    {
        return $this->energySourceCostId;
    }
    public function getEnergySourceCost()
    {
        return LccEnergySourceCost::findById($this->energySourceCostId);
    }
    /**
     * Checks, if the object exists
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  integer $costId           - regularCostId
     * @param  boolean $force            - Bypass caching
     * @return bool
     */
    public static function exists($projectVariantId, $calcMethod, $costId, $force = false)
    {
        return self::findByPk($projectVariantId, $calcMethod, $costId, $force)->isInitialized();
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
        $sql = sprintf(
            "UPDATE %s
                           SET quantity         = :quantity
                             , ref_value        = :refValue
                             , energy_source_cost_id = :energySourceCostId
                         WHERE (project_variant_id, calc_method, cost_id) = (:projectVariantId, :calcMethod, :costId)"
            ,
            self::TABLE_NAME
        );

        return $this->updateBySql(
            $sql,
            array(
                'projectVariantId' => $this->projectVariantId,
                'calcMethod'       => $this->calcMethod,
                'costId'           => $this->costId,
                'quantity'         => $this->quantity,
                'refValue'         => $this->refValue,
                'energySourceCostId' => $this->energySourceCostId,
            )
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
        $sql = sprintf(
            "DELETE FROM %s
                         WHERE (project_variant_id, calc_method, cost_id) = (:projectVariantId, :calcMethod, :costId)"
            ,
            self::TABLE_NAME
        );

        return $this->deleteBySql(
            $sql,
            ['projectVariantId' => $this->projectVariantId, 'calcMethod' => $this->calcMethod, 'costId' => $this->costId]
        );
    }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  boolean $propertiesOnly
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if ($propertiesOnly) {
            return self::$primaryKey;
        }

        $primaryKey = array();

        foreach (self::$primaryKey as $key) {
            $primaryKey[$key] = $this->$key;
        }

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
     * @param  boolean $extColumns
     * @param  mixed   $column
     * @return mixed
     */
    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns ? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if ($column) {
            return $columnTypes[$column];
        }

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

        $sql = sprintf(
            "INSERT INTO %s (project_variant_id, calc_method, cost_id, quantity, ref_value, energy_source_cost_id)
                               VALUES  (:projectVariantId, :calcMethod, :costId, :quantity, :refValue, :energySourceCostId)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            array(
                'projectVariantId' => $this->projectVariantId,
                'calcMethod'       => $this->calcMethod,
                'costId'           => $this->costId,
                'quantity'         => $this->quantity,
                'refValue'         => $this->refValue,
                'energySourceCostId' => $this->energySourceCostId,
            )
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
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->calcMethod       = $DO->calc_method;
        $this->costId           = (int)$DO->cost_id;
        $this->quantity         = $DO->quantity;
        $this->refValue         = $DO->ref_value;
        $this->energySourceCostId = $DO->energy_source_cost_id;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}

// End class LccProjectCost