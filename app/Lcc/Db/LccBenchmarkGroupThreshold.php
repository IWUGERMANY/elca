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
 * 
 *
 * @package    -
 * @class      LccBenchmarkGroupThreshold
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class LccBenchmarkGroupThreshold extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.benchmark_group_thresholds';

    /**
     * 
     */
    private $id;

    /**
     * 
     */
    private $groupId;

    /**
     * 
     */
    private $score;

    /**
     * 
     */
    private $caption;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'groupId'        => PDO::PARAM_INT,
                                        'score'          => PDO::PARAM_INT,
                                        'caption'        => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $groupId - 
     * @param  int      $score  - 
     * @param  string   $caption - 
     * @return LccBenchmarkGroupThreshold
     */
    public static function create($groupId, $score, $caption)
    {
        $LccBenchmarkGroupThreshold = new LccBenchmarkGroupThreshold();
        $LccBenchmarkGroupThreshold->setGroupId($groupId);
        $LccBenchmarkGroupThreshold->setScore($score);
        $LccBenchmarkGroupThreshold->setCaption($caption);
        
        if($LccBenchmarkGroupThreshold->getValidator()->isValid())
            $LccBenchmarkGroupThreshold->insert();
        
        return $LccBenchmarkGroupThreshold;
    }
    // End create
    

    /**
     * Inits a `LccBenchmarkGroupThreshold' by its primary key
     *
     * @param  int      $id    - 
     * @param  bool     $force - Bypass caching
     * @return LccBenchmarkGroupThreshold
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new LccBenchmarkGroupThreshold();
        
        $sql = sprintf("SELECT id
                             , group_id
                             , score
                             , caption
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById
    

    /**
     * Inits a `LccBenchmarkGroupThreshold' by its unique key (groupId, score)
     *
     * @param  int      $groupId - 
     * @param  int      $score  - 
     * @param  bool     $force  - Bypass caching
     * @return LccBenchmarkGroupThreshold
     */
    public static function findByGroupIdAndScore($groupId, $score, $force = false)
    {
        if(!$groupId || !$score)
            return new LccBenchmarkGroupThreshold();
        
        $sql = sprintf("SELECT id
                             , group_id
                             , score
                             , caption
                          FROM %s
                         WHERE group_id = :groupId
                           AND score = :score"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('groupId' => $groupId, 'score' => $score), $force);
    }
    // End findByGroupIdAndScore

    public function copy($newGroupId)
    {
        return self::create($newGroupId, $this->getScore(), $this->getCaption());
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
     * Sets the property score
     *
     * @param  int      $score - 
     * @return void
     */
    public function setScore($score)
    {
        if(!$this->getValidator()->assertNotEmpty('score', $score))
            return;
        
        $this->score = (int)$score;
    }
    // End setScore
    

    /**
     * Sets the property caption
     *
     * @param  string   $caption - 
     * @return void
     */
    public function setCaption($caption)
    {
        $this->caption = (string)$caption;
    }
    // End setCaption
    

    /**
     * Returns the property id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    // End getId
    

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
     * Returns the associated LccBenchmarkGroup by property groupId
     *
     * @param  bool     $force
     * @return LccBenchmarkGroup
     */
    public function getGroup($force = false)
    {
        return LccBenchmarkGroup::findById($this->groupId, $force);
    }
    // End getGroup
    

    /**
     * Returns the property score
     *
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }
    // End getScore
    

    /**
     * Returns the property caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }
    // End getCaption
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - 
     * @param  bool     $force - Bypass caching
     * @return bool
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
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
                           SET group_id       = :groupId
                             , score          = :score
                             , caption        = :caption
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'groupId'       => $this->groupId,
                                        'score'         => $this->score,
                                        'caption'       => $this->caption)
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
                              WHERE id = :id"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('id' => $this->id));
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
        $this->id             = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, group_id, score, caption)
                               VALUES  (:id, :groupId, :score, :caption)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'groupId'       => $this->groupId,
                                        'score'         => $this->score,
                                        'caption'       => $this->caption)
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
        $this->id             = (int)$DO->id;
        $this->groupId        = (int)$DO->group_id;
        $this->score          = (int)$DO->score;
        $this->caption        = $DO->caption;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccBenchmarkGroupThreshold