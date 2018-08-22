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

use Beibob\Blibs\DbObjectSet;
/**
 * {BLIBSLICENCE}
 *
 * Handles a set of LccCost
 *
 * @package    -
 * @class      LccCostSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccCostSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_PROJECT_COSTS = 'lcc.project_costs_v';

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find and extends regular costs
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function findRegular($versionId, $grouping = null, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$versionId || !$grouping)
            return new LccCostSet();

        if(is_null($initValues))
            $initValues = array();

        if(is_null($orderBy))
            $orderBy = array('din276_code' => 'ASC', 'id' => 'ASC');

        if(!is_null($grouping))
            $initValues['grouping'] = $grouping;

        $initValues['version_id'] = $versionId;
        return self::_find(get_class(), LccRegularCostSet::VIEW_REGULAR_COSTS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find and extends regular media costs
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function findRegularMedia($versionId, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$versionId)
            return new LccCostSet();

        if(is_null($initValues))
            $initValues = array();

        if(is_null($orderBy))
            $orderBy = array('grouping' => 'DESC', 'din276_code' => 'ASC', 'id' => 'ASC');

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE version_id = :versionId
                           AND grouping IN (:water, :energy)'
                       , LccRegularCostSet::VIEW_REGULAR_COSTS
                       );

        if($conditions = self::buildConditions($initValues))
            $sql .= ' AND '.$conditions;

        if($orderSql = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '.$orderSql;

        $initValues['water'] = LccCost::GROUPING_WATER;
        $initValues['energy'] = LccCost::GROUPING_ENERGY;
        $initValues['versionId'] = $versionId;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findRegularMedia

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find and extends regular cleaning costs
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function findRegularCleaning($versionId, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$versionId)
            return new LccCostSet();

        if(is_null($initValues))
            $initValues = array();

        if(is_null($orderBy))
            $orderBy = array('din276_code' => 'ASC', 'id' => 'ASC');

        $initValues['grouping'] = LccCost::GROUPING_CLEANING;
        $initValues['version_id'] = $versionId;
        return self::_find(get_class(), LccRegularCostSet::VIEW_REGULAR_COSTS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find and extends regular service costs
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function findRegularService($versionId, $din276Group = null, $projectId = null, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$versionId)
            return new LccCostSet();

        if(is_null($initValues))
            $initValues = array();

        if(is_null($orderBy))
            $orderBy = array('version_id' => 'ASC', 'din276_code' => 'ASC', 'id' => 'ASC');

        $conditions = self::buildConditions($initValues);

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE grouping = :grouping'
                       , LccRegularServiceCostSet::VIEW_REGULAR_SERVICE_COSTS
                       );

        if(!is_null($din276Group))
            $initValues['grouping'] = LccCost::GROUPING_KGR . (string)(intval($din276Group / 100) * 100);

        $initValues['versionId'] = $versionId;

        if($projectId)
        {
            $sql .= ' AND ((project_id IS NULL AND version_id = :versionId) OR (version_id IS NULL AND project_id = :projectId))';
            $initValues['projectId'] = $projectId;
        }
        else
            $sql .= ' AND project_id IS NULL AND version_id = :versionId';

        if($conditions)
            $sql .= ' AND '. $conditions;

        if($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find and extends irregular costs
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function findIrregular($versionId, $din276Group = null, $projectId = null, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$versionId)
            return new LccCostSet();

        if(is_null($initValues))
            $initValues = array();

        if(is_null($orderBy))
            $orderBy = array('version_id' => 'ASC', 'din276_code' => 'ASC', 'id' => 'ASC');

        $conditions = self::buildConditions($initValues);

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE grouping = :grouping'
                       , LccIrregularCostSet::VIEW_IRREGULAR_COSTS
                       );

        if(!is_null($din276Group))
            $initValues['grouping'] = LccCost::GROUPING_KGU . (string)(intval($din276Group / 100) * 100);

        $initValues['versionId'] = $versionId;

        if($projectId)
        {
            $sql .= ' AND ((project_id IS NULL and version_id = :versionId) OR (version_id IS NULL AND project_id = :projectId))';
            $initValues['projectId'] = $projectId;
        }
        else
            $sql .= ' AND project_id IS NULL AND version_id = :versionId';

        if($conditions)
            $sql .= ' AND '. $conditions;

        if($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all project specific costs, which are not bound to a lcc version, for the given
     * projectId
     *
     * @param  int      $projectId
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function findByProjectId($versionId, $projectId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$versionId || !$projectId)
            return new LccCostSet();

        $initValues = array();

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE ((project_id IS NULL AND version_id = :versionId) OR (version_id IS NULL AND project_id = :projectId))'
                       , self::VIEW_PROJECT_COSTS
                       );

        $initValues['projectId'] = $projectId;
        $initValues['versionId'] = $versionId;

        if($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProjectId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all project costs for the given projectVariantId
     *
     * @param  int      $projectVariantId
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function findProjectCostsByProjectVariantId($versionId, $projectVariantId, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$versionId || !$projectVariantId)
            return new LccCostSet();

        if(is_null($initValues))
            $initValues = array();

        $initValues['project_variant_id'] = $projectVariantId;

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE (version_id IS NULL OR version_id = :versionId)'
                       , self::VIEW_PROJECT_COSTS
                       );

        $sql .= ' AND '. self::buildConditions($initValues);

        if($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        $initValues['versionId'] = $versionId;
        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findProjectCostsByProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccCostSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccCost::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), LccCost::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class LccCostSet