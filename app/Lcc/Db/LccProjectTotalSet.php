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
use Lcc\LccModule;

/**
 * {BLIBSLICENCE}
 *
 * Handles a set of LccProjectTotal
 *
 * @package    -
 * @class      LccProjectTotalSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccProjectTotalSet extends DbObjectSet
{

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param $projectVariantId
     * @param $calcMethod
     * @return LccProjectTotalSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function findProductionTotals($projectVariantId, $calcMethod)
    {
        if (!$projectVariantId)
            return new LccProjectTotalSet();

        $initValues = [
            'projectVariantId' => $projectVariantId,
            'calcMethod' => $calcMethod
        ];

        $sql = sprintf('SELECT project_variant_id
                             , grouping
                             , calc_method
                             , sum(quantity) AS costs
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND calc_method = :calcMethod
                           AND life_time = 0
                           AND grouping ilike \'%s%%\'
                      GROUP BY project_variant_id
                             , grouping
                             , calc_method',
            LccProjectCostProgression::TABLE_NAME,
            LccCost::GROUPING_KGU
        );

        return self::_findBySql(get_class(), $sql, $initValues);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return LccProjectTotalSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccProjectTotal::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), LccProjectTotal::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class LccProjectTotalSet