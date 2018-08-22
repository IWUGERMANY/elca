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
use Elca\Db\ElcaProjectVariant;
use PDO;
/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccProjectCostProgression
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccProjectCostProgression extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.project_cost_progressions';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * projectVariantId
     */
    private $projectVariantId;
    private $calcMethod;

    /**
     * grouping
     */
    private $grouping;

    /**
     * lifeTime
     */
    private $lifeTime;

    /**
     * quantity
     */
    private $quantity;

    /**
     * Primary key
     */
    private static $primaryKey = ['projectVariantId', 'calcMethod', 'grouping', 'lifeTime'];

    /**
     * Column types
     */
    private static $columnTypes = ['projectVariantId' => PDO::PARAM_INT,
                                   'calcMethod'       => PDO::PARAM_INT,
                                        'grouping'         => PDO::PARAM_STR,
                                        'lifeTime'         => PDO::PARAM_INT,
                                        'quantity'         => PDO::PARAM_STR];

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
     * @param  integer  $projectVariantId - projectVariantId
     * @param  string   $grouping        - grouping
     * @param  integer  $lifeTime        - lifeTime
     * @param  number  $quantity        - quantity
     */
    public static function create($projectVariantId, $calcMethod, $grouping, $lifeTime, $quantity)
    {
        $LccProjectCostProgression = new LccProjectCostProgression();
        $LccProjectCostProgression->setProjectVariantId($projectVariantId);
        $LccProjectCostProgression->setCalcMethod($calcMethod);
        $LccProjectCostProgression->setGrouping($grouping);
        $LccProjectCostProgression->setLifeTime($lifeTime);
        $LccProjectCostProgression->setQuantity($quantity);
        
        if($LccProjectCostProgression->getValidator()->isValid())
            $LccProjectCostProgression->insert();
        
        return $LccProjectCostProgression;
    }
    // End create
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccProjectCostProgression' by its primary key
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  string  $grouping         - grouping
     * @param  integer $lifeTime         - lifeTime
     * @param  boolean $force            - Bypass caching
     * @return LccProjectCostProgression
     * @throws \Beibob\Blibs\Exception
     */
    public static function findByPk($projectVariantId, $calcMethod, $grouping, $lifeTime, $force = false)
    {
        if(!$projectVariantId || !$grouping || !$lifeTime)
            return new LccProjectCostProgression();
        
        $sql = sprintf("SELECT project_variant_id
                             , calc_method
                             , grouping
                             , life_time
                             , quantity
                          FROM %s
                         WHERE (project_variant_id, calc_method, grouping, life_time) =
                                (:projectVariantId, :calcMethod, :grouping, :lifeTime)"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, [
            'projectVariantId' => $projectVariantId,
            'calcMethod'       => $calcMethod,
            'grouping' => $grouping,
            'lifeTime' => $lifeTime
        ], $force);
    }
    // End findByPk
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property projectVariantId
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @return 
     */
    public function setProjectVariantId($projectVariantId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId))
            return;
        
        $this->projectVariantId = (int)$projectVariantId;
    }
    // End setProjectVariantId
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property grouping
     *
     * @param  string   $grouping - grouping
     * @return 
     */
    public function setGrouping($grouping)
    {
        if(!$this->getValidator()->assertNotEmpty('grouping', $grouping))
            return;
        
        if(!$this->getValidator()->assertMaxLength('grouping', 100, $grouping))
            return;
        
        $this->grouping = (string)$grouping;
    }
    // End setGrouping
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property lifeTime
     *
     * @param  integer  $lifeTime - lifeTime
     * @return 
     */
    public function setLifeTime($lifeTime)
    {
        if(!$this->getValidator()->assertNotEmpty('lifeTime', $lifeTime))
            return;
        
        $this->lifeTime = (int)$lifeTime;
    }
    // End setLifeTime
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property quantity
     *
     * @param  number  $quantity - quantity
     * @return 
     */
    public function setQuantity($quantity)
    {
        if(!$this->getValidator()->assertNotEmpty('quantity', $quantity))
            return;
        
        $this->quantity = $quantity;
    }
    // End setQuantity
    
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
     * Returns the associated ElcaProjectVariant by property projectVariantId
     *
     * @param  boolean  $force
     * @return ElcaProjectVariant
     */
    public function getProjectVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->projectVariantId, $force);
    }
    // End getProjectVariant
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property grouping
     *
     * @return string
     */
    public function getGrouping()
    {
        return $this->grouping;
    }
    // End getGrouping
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property lifeTime
     *
     * @return integer
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }
    // End getLifeTime
    
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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  string  $grouping         - grouping
     * @param  integer $lifeTime         - lifeTime
     * @param  boolean $force            - Bypass caching
     * @return bool
     */
    public static function exists($projectVariantId, $calcMethod, $grouping, $lifeTime, $force = false)
    {
        return self::findByPk($projectVariantId, $calcMethod, $grouping, $lifeTime, $force)->isInitialized();
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
                           SET quantity         = :quantity
                         WHERE (project_variant_id, calc_method, grouping, life_time) =
                                (:projectVariantId, :calcMethod, :grouping, :lifeTime)"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  ['projectVariantId' => $this->projectVariantId,
                                        'calcMethod' => $this->calcMethod,
                                        'grouping'        => $this->grouping,
                                        'lifeTime'        => $this->lifeTime,
                                        'quantity'        => $this->quantity]
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
                              WHERE (project_variant_id, calc_method, grouping, life_time) =
                                (:projectVariantId, :calcMethod, :grouping, :lifeTime)"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  ['projectVariantId' => $this->projectVariantId,
                                   'calcMethod' => $this->calcMethod,
                                   'grouping' => $this->grouping,
                                   'lifeTime' => $this->lifeTime
                                  ]
        );
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
        
        $sql = sprintf("INSERT INTO %s (project_variant_id, calc_method, grouping, life_time, quantity)
                               VALUES  (:projectVariantId, :calcMethod, :grouping, :lifeTime, :quantity)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  ['projectVariantId' => $this->projectVariantId,
                                        'calcMethod' => $this->calcMethod,
                                        'grouping'        => $this->grouping,
                                        'lifeTime'        => $this->lifeTime,
                                        'quantity'        => $this->quantity]
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
        $this->grouping         = $DO->grouping;
        $this->lifeTime         = (int)$DO->life_time;
        $this->quantity         = $DO->quantity;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccProjectCostProgression