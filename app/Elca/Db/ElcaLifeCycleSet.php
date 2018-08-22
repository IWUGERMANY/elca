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

use Beibob\Blibs\DbObjectSet;
/**
 * Handles a set of ElcaLifeCycle
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaLifeCycleSet extends DbObjectSet
{

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds available life cycles by processDbId and phase
     *
     * @param  int    $processDbId
     * @param  string $phase
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  boolean  $force     - Bypass caching
     * @return ElcaLifeCycleSet
     */
    public static function findByProcessDbIdAndPhase($processDbId, $phase, array $orderBy = null, $force = false)
    {
        if(!$processDbId || !$phase)
            return new ElcaLifeCycleSet();

        $initValues = array();
        $initValues['processDbId'] = $processDbId;
        $initValues['phase'] = $phase;

        $sql = sprintf('SELECT DISTINCT
                               life_cycle_ident AS ident
                             , life_cycle_name  AS name
                             , life_cycle_phase  AS phase
                             , life_cycle_p_order AS p_order
                             , life_cycle_description AS description
                          FROM %s
                         WHERE process_db_id = :processDbId
                           AND life_cycle_phase = :phase'
                       , ElcaProcessSet::VIEW_ELCA_PROCESSES
                       );

        if($orderSql = self::buildOrderView($orderBy))
            $sql .= ' '. $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessDbIdAndPhase

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds available life cycles by processDbId and phase
     *
     * @param  int    $processDbId
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  boolean  $force     - Bypass caching
     * @return ElcaLifeCycleSet
     */
    public static function findByProcessDbId($processDbId, array $orderBy = null, $force = false)
    {
        if(!$processDbId)
            return new ElcaLifeCycleSet();

        $initValues = array();
        $initValues['processDbId'] = $processDbId;

        $sql = sprintf('SELECT DISTINCT
                               life_cycle_ident AS ident
                             , life_cycle_name  AS name
                             , life_cycle_phase  AS phase
                             , life_cycle_p_order AS p_order
                             , life_cycle_description AS description
                          FROM %s
                         WHERE process_db_id = :processDbId'
            , ElcaProcessSet::VIEW_ELCA_PROCESSES
        );

        if($orderSql = self::buildOrderView($orderBy))
            $sql .= ' '. $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessDbIdAndPhase

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds only en15804 compliant life cycles
     *
     * @param  -
     * @return -
     */
    public static function findEn15804Compliant(array $orderBy, $force = false)
    {
        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE ident <> phase'
                       , ElcaLifeCycle::TABLE_NAME
                       );

        if($orderSql = self::buildOrderView($orderBy))
            $sql .= ' '. $orderSql;

        return self::_findBySql(get_class(), $sql, null, $force);
    }
    // End findEn15804Compliant

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaLifeCycleSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaLifeCycle::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaLifeCycle::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaLifeCycleSet