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

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\DbObject;
use Exception;
use PDO;
/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccIrregularCost
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccIrregularCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.irregular_costs';

    const PROC_UPDATE_DETAILED_IRREGULAR_PROJECT_COSTS = 'lcc.update_detailed_irregular_project_costs';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * costId
     */
    private $costId;

    /**
     * life_time
     */
    private $lifeTime;

    /**
     * Primary key
     */
    private static $primaryKey = ['costId'];

    /**
     * Column types
     */
    private static $columnTypes = ['costId'         => PDO::PARAM_INT,
                                        'lifeTime'       => PDO::PARAM_INT];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param $projectVariantId
     */
    public static function updateDetailedIrregularProjectCosts($projectVariantId)
    {
        if (!$projectVariantId)
            return;

        DbHandle::getInstance()
                ->exec(
                    sprintf(
                        'SELECT %s(%d)',
                        self::PROC_UPDATE_DETAILED_IRREGULAR_PROJECT_COSTS,
                        $projectVariantId
                    )
                );
    }

    /**
     * Creates the object
     *
     * @param  integer  $costId  - costId
     * @param  number  $lifeTime - life_time
     */
    public static function create($costId, $lifeTime)
    {
        $LccIrregularCost = new LccIrregularCost();
        $LccIrregularCost->setCostId($costId);
        $LccIrregularCost->setLifeTime($lifeTime);

        if($LccIrregularCost->getValidator()->isValid())
            $LccIrregularCost->insert();

        return $LccIrregularCost;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccIrregularCost' by its primary key
     *
     * @param  integer  $costId - costId
     * @param  boolean  $force - Bypass caching
     * @return LccIrregularCost
     */
    public static function findByCostId($costId, $force = false)
    {
        if(!$costId)
            return new LccIrregularCost();

        $sql = sprintf("SELECT cost_id
                             , life_time
                          FROM %s
                         WHERE cost_id = :costId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['costId' => $costId], $force);
    }
    // End findByCostId

    /**
     * Inits a KGR `LccIrregularCost' by versionId and din276 code
     *
     * @param          $versionId
     * @param          $din276Code
     * @param  boolean $force - Bypass caching
     * @return LccIrregularCost
     * @throws \Beibob\Blibs\Exception
     */
    public static function findKGRByVersionIdGroupingAndDin276Code($versionId, $din276Code, $force = false)
    {
        if(!$versionId || !$din276Code)
            return new LccIrregularCost();

        $grouping = LccCost::GROUPING_KGR . (string)(intval($din276Code / 100) * 100);

        $sql = sprintf("SELECT cost_id
                             , life_time
                          FROM %s
                         WHERE (version_id, grouping) = (:versionId, :grouping)
                           AND din276_code IN ((:din276Code / 10)::int * 10, :din276Code)
                      ORDER BY din276_code DESC
                         LIMIT 1"
            , LccIrregularCostSet::VIEW_IRREGULAR_COSTS
        );

        return self::findBySql(get_class(), $sql, [
            'versionId' => $versionId,
            'grouping' => $grouping,
            'din276Code' => $din276Code
        ], $force);
    }

    /**
     * Inits a KGU `LccIrregularCost' by versionId and din276 code
     *
     * @param          $versionId
     * @param          $din276Code
     * @param  boolean $force - Bypass caching
     * @return LccIrregularCost
     * @throws \Beibob\Blibs\Exception
     */
    public static function findKGUByVersionIdGroupingAndDin276Code($versionId, $din276Code, $force = false)
    {
        if(!$versionId || !$din276Code)
            return new LccIrregularCost();

        $grouping = LccCost::GROUPING_KGU . (string)(intval($din276Code / 100) * 100);

        $sql = sprintf("SELECT cost_id
                             , life_time
                          FROM %s
                         WHERE (version_id, grouping) = (:versionId, :grouping)
                           AND din276_code IN ((:din276Code / 10)::int * 10, :din276Code)
                      ORDER BY din276_code DESC
                         LIMIT 1"
            , LccIrregularCostSet::VIEW_IRREGULAR_COSTS
        );

        return self::findBySql(get_class(), $sql, [
            'versionId' => $versionId,
            'grouping' => $grouping,
            'din276Code' => $din276Code
        ], $force);
    }

    /**
     * Creates a copy of this with a new versionId
     *
     * @param  int $versionId
     * @return LccIrregularCost
     */
    public function copy($versionId)
    {
        if(!$versionId || !$this->isInitialized())
            return new LccIrregularCost();

        try
        {
            $this->Dbh->begin();

            $Cost = $this->getCost();
            $CostCopy = $Cost->copy($versionId);

            $Copy = self::create($CostCopy->getId(),
                                 $this->lifeTime
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
     * Sets the property lifeTime
     *
     * @param  number  $lifeTime - life_time
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
     * Returns the property lifeTime
     *
     * @return number
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }
    // End getLifeTime

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
                           SET life_time      = :lifeTime
                         WHERE cost_id = :costId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['costId'        => $this->costId,
                                        'lifeTime'      => $this->lifeTime]
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

        $sql = sprintf("INSERT INTO %s (cost_id, life_time)
                               VALUES  (:costId, :lifeTime)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['costId'        => $this->costId,
                                        'lifeTime'      => $this->lifeTime]
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
        $this->lifeTime       = (int)$DO->life_time;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccIrregularCost