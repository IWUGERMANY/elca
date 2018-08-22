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
 * @class      LccProjectTotal
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccProjectTotal extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.project_totals';

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
     * costs
     */
    private $costs;

    /**
     * Primary key
     */
    private static $primaryKey = ['projectVariantId', 'calcMethod', 'grouping'];

    /**
     * Column types
     */
    private static $columnTypes = ['projectVariantId' => PDO::PARAM_INT,
                                   'calcMethod'       => PDO::PARAM_INT,
                                   'grouping'         => PDO::PARAM_STR,
                                   'costs'            => PDO::PARAM_STR];

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
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  string  $grouping         - grouping
     * @param  number  $costs            - costs
     * @return LccProjectTotal
     */
    public static function create($projectVariantId, $calcMethod, $grouping, $costs = null)
    {
        $LccProjectTotal = new LccProjectTotal();
        $LccProjectTotal->setProjectVariantId($projectVariantId);
        $LccProjectTotal->setCalcMethod($calcMethod);
        $LccProjectTotal->setGrouping($grouping);
        $LccProjectTotal->setCosts($costs);
        
        if($LccProjectTotal->getValidator()->isValid())
            $LccProjectTotal->insert();
        
        return $LccProjectTotal;
    }
    // End create
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccProjectTotal' by its primary key
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  string   $grouping        - grouping
     * @param  boolean  $force           - Bypass caching
     * @return LccProjectTotal
     */
    public static function findByPk($projectVariantId, $calcMethod, $grouping, $force = false)
    {
        if(!$projectVariantId || !$grouping)
            return new LccProjectTotal();
        
        $sql = sprintf("SELECT project_variant_id
                             , calc_method
                             , grouping
                             , costs
                          FROM %s
                         WHERE (project_variant_id, calc_method, grouping) = (:projectVariantId, :calcMethod, :grouping)"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, [
            'projectVariantId' => $projectVariantId,
            'calcMethod'       => $calcMethod,
            'grouping' => $grouping
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
     * Sets the property costs
     *
     * @param  number  $costs - costs
     * @return 
     */
    public function setCosts($costs = null)
    {
        $this->costs = $costs;
    }
    // End setCosts
    
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
     * Returns the property costs
     *
     * @return number
     */
    public function getCosts()
    {
        return $this->costs;
    }
    // End getCosts

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
     * @param  boolean $force            - Bypass caching
     * @return bool
     */
    public static function exists($projectVariantId, $calcMethod, $grouping, $force = false)
    {
        return self::findByPk($projectVariantId, $calcMethod, $grouping, $force)->isInitialized();
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
                           SET costs            = :costs
                         WHERE (project_variant_id, calc_method, grouping) = (:projectVariantId, :calcMethod, :grouping)"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  ['projectVariantId' => $this->projectVariantId,
                                   'calcMethod'       => $this->calcMethod,
                                   'grouping'        => $this->grouping,
                                   'costs'           => $this->costs]
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
                         WHERE (project_variant_id, calc_method, grouping) = (:projectVariantId, :calcMethod, :grouping)"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql, [
                'projectVariantId' => $this->projectVariantId,
                'calcMethod'       => $this->calcMethod,
                'grouping'        => $this->grouping,
                'costs'           => $this->costs]
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
        
        $sql = sprintf("INSERT INTO %s (project_variant_id, calc_method, grouping, costs)
                               VALUES  (:projectVariantId, :calcMethod, :grouping, :costs)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  ['projectVariantId' => $this->projectVariantId,
                                   'calcMethod'       => $this->calcMethod,
                                   'grouping'        => $this->grouping,
                                   'costs'           => $this->costs]
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
        $this->costs            = $DO->costs;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccProjectTotal