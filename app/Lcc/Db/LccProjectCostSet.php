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
 * Handles a set of LccProjectCost
 *
 * @package    -
 * @class      LccProjectCostSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccProjectCostSet extends DbObjectSet
{

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all project specific cost values which are not null
     *
     * @param          $costId
     * @param          $calcMethod
     * @param null     $excludeVariantId
     * @param  boolean $force - Bypass caching
     * @return int
     * @throws \Beibob\Blibs\Exception
     */
    public static function findCostsNotNull($costId, $calcMethod, $excludeVariantId = null, $force = false)
    {
        if(!$costId)
            return new LccProjectCostSet();

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE calc_method = :calcMethod AND cost_id = :costId
                           AND (quantity IS NOT NULL OR quantity <> 0)'
                       , LccProjectCost::TABLE_NAME);

        $initValues = [
            'calcMethod' => $calcMethod,
            'costId' => $costId
        ];

        if($excludeVariantId)
        {
            $sql .= ' AND project_variant_id <> :projectVariantId';
            $initValues['projectVariantId'] = $excludeVariantId;
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findCostsNotNull

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Counts all project specific cost values which are not null
     *
     * @param          $costId
     * @param          $calcMethod
     * @param null     $excludeVariantId
     * @param  boolean $force - Bypass caching
     * @return int
     */
    public static function countCostsNotNull($costId, $calcMethod, $excludeVariantId = null, $force = false)
    {
        if(!$costId)
            return new LccProjectCostSet();

        $sql = sprintf('SELECT count(*) as counter
                          FROM %s
                         WHERE calc_method = :calcMethod AND cost_id = :costId
                           AND (quantity IS NOT NULL OR quantity <> 0)'
                       , LccProjectCost::TABLE_NAME);

        $initValues = [
            'calcMethod' => $calcMethod,
            'costId' => $costId
        ];

        if($excludeVariantId)
        {
            $sql .= ' AND project_variant_id <> :projectVariantId';
            $initValues['projectVariantId'] = $excludeVariantId;
        }

        return self::_countBySql(get_class(), $sql, $initValues, 'counter', $force);
    }
    // End countCostsNotNull

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccProjectCostSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccProjectCost::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), LccProjectCost::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class LccProjectCostSet