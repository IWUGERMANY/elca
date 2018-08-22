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
 *
 * @package    -
 * @class      ElcaBenchmarkGroupIndicator
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class ElcaBenchmarkGroupIndicator extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.benchmark_group_indicators';

    /**
     * 
     */
    private $groupId;

    /**
     * 
     */
    private $indicatorId;

    /**
     * Primary key
     */
    private static $primaryKey = array('groupId', 'indicatorId');

    /**
     * Column types
     */
    private static $columnTypes = array('groupId'        => PDO::PARAM_INT,
                                        'indicatorId'    => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $groupId    - 
     * @param  int      $indicatorId - 
     * @return ElcaBenchmarkGroupIndicator
     */
    public static function create($groupId, $indicatorId)
    {
        $ElcaBenchmarkGroupIndicator = new ElcaBenchmarkGroupIndicator();
        $ElcaBenchmarkGroupIndicator->setGroupId($groupId);
        $ElcaBenchmarkGroupIndicator->setIndicatorId($indicatorId);
        
        if($ElcaBenchmarkGroupIndicator->getValidator()->isValid())
            $ElcaBenchmarkGroupIndicator->insert();
        
        return $ElcaBenchmarkGroupIndicator;
    }
    // End create
    

    /**
     * Inits a `ElcaBenchmarkGroupIndicator' by its primary key
     *
     * @param  int      $groupId    - 
     * @param  int      $indicatorId - 
     * @param  bool     $force      - Bypass caching
     * @return ElcaBenchmarkGroupIndicator
     */
    public static function findByPk($groupId, $indicatorId, $force = false)
    {
        if(!$groupId || !$indicatorId)
            return new ElcaBenchmarkGroupIndicator();
        
        $sql = sprintf("SELECT group_id
                             , indicator_id
                          FROM %s
                         WHERE group_id = :groupId
                           AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('groupId' => $groupId, 'indicatorId' => $indicatorId), $force);
    }

    /**
     * @throws \Beibob\Blibs\Exception
     * @return self
     */
    public static function findByBenchmarkVersionIdAndIndicatorIdent(int $benchmarkVersionId, string $indicatorIdent, bool $force = false)
    {
        $sql = sprintf("SELECT gi.group_id
                                    , gi.indicator_id
                          FROM %s g
                          JOIN %s gi ON g.id = gi.group_id
                          JOIN %s i ON i.id = gi.indicator_id
                         WHERE g.benchmark_version_id = :benchmarkVersionId
                           AND i.ident = :indicatorIdent
                           LIMIT 1"
            , ElcaBenchmarkGroup::TABLE_NAME
            , self::TABLE_NAME
            , ElcaIndicator::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('benchmarkVersionId' => $benchmarkVersionId, 'indicatorIdent' => $indicatorIdent), $force);
    }

    public function copy($groupId)
    {
        return self::create($groupId, $this->getIndicatorId());
    }


    /**
     * Sets the property groupId
     *
     * @param  int      $groupId - 
     * @return void
     */
    public function setGroupId($groupId)
    {
        if(!$this->getValidator()->assertNotEmpty('groupId', $groupId))
            return;
        
        $this->groupId = (int)$groupId;
    }
    // End setGroupId
    

    /**
     * Sets the property indicatorId
     *
     * @param  int      $indicatorId - 
     * @return void
     */
    public function setIndicatorId($indicatorId)
    {
        if(!$this->getValidator()->assertNotEmpty('indicatorId', $indicatorId))
            return;
        
        $this->indicatorId = (int)$indicatorId;
    }
    // End setIndicatorId
    

    /**
     * Returns the property groupId
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }
    // End getGroupId
    

    /**
     * Returns the associated ElcaBenchmarkGroup by property groupId
     *
     * @param  bool     $force
     * @return ElcaBenchmarkGroup
     */
    public function getGroup($force = false)
    {
        return ElcaBenchmarkGroup::findById($this->groupId, $force);
    }
    // End getGroup
    

    /**
     * Returns the property indicatorId
     *
     * @return int
     */
    public function getIndicatorId()
    {
        return $this->indicatorId;
    }
    // End getIndicatorId
    

    /**
     * Returns the associated ElcaIndicator by property indicatorId
     *
     * @param  bool     $force
     * @return ElcaIndicator
     */
    public function getIndicator($force = false)
    {
        return ElcaIndicator::findById($this->indicatorId, $force);
    }
    // End getIndicator
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $groupId    - 
     * @param  int      $indicatorId - 
     * @param  bool     $force      - Bypass caching
     * @return bool
     */
    public static function exists($groupId, $indicatorId, $force = false)
    {
        return self::findByPk($groupId, $indicatorId, $force)->isInitialized();
    }
    // End exists
    

    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET                = :
                         WHERE group_id = :groupId
                           AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('groupId'       => $this->groupId,
                                        'indicatorId'   => $this->indicatorId)
                                  );
    }
    // End update
    

    /**
     * Deletes the object from the table
     *
     * @return bool
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE group_id = :groupId
                                AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('groupId' => $this->groupId, 'indicatorId' => $this->indicatorId));
    }
    // End delete
    

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  bool     $propertiesOnly
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
     * @param  bool     $extColumns
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

    /**
     * Inserts a new object in the table
     *
     * @return bool
     */
    protected function insert()
    {
        
        $sql = sprintf("INSERT INTO %s (group_id, indicator_id)
                               VALUES  (:groupId, :indicatorId)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('groupId'       => $this->groupId,
                                        'indicatorId'   => $this->indicatorId)
                                  );
    }
    // End insert
    

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return bool
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->groupId        = (int)$DO->group_id;
        $this->indicatorId    = (int)$DO->indicator_id;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaBenchmarkGroupIndicator