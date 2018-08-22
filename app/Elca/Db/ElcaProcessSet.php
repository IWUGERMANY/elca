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
 * Handles a set of ElcaProcess
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_PROCESSES = 'elca.processes_v';
    const VIEW_ELCA_PROCESS_ASSIGNMENTS = 'elca.process_assignments_v';
    const VIEW_ELCA_EXPORT_PROCESS_ASSIGNMENTS = 'elca.export_process_assignments_v';

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all processes for the given process config id
     *
     * @param  int      $processConfigId
     * @param  array    $initValues  - filter map
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  boolean  $force     - Bypass caching
     * @return self
     */
    public static function findByProcessConfigId($processConfigId, array $initValues = array(), array $orderBy = null, $force = false)
    {
        if(!$processConfigId)
            return new ElcaProcessSet();

        $initValues['process_config_id'] = $processConfigId;

        return self::_find(get_class(), self::VIEW_ELCA_PROCESS_ASSIGNMENTS, $initValues, $orderBy, null, null, $force);
    }
    // End findByProcessConfigId


    /**
     * Lazy count by processConfigId
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCountByProcessConfigId($processConfigId, array $initValues = null, $force = false)
    {
        $initValues['process_config_id'] = $processConfigId;

        return self::_count(get_class(), self::VIEW_ELCA_PROCESS_ASSIGNMENTS, $initValues, $force);
    }
    // End dbCountByProcessConfigId


    /**
     * Lazy count by processConfigId
     *
     * @param          $processDbId
     * @param          $processConfigId
     * @param array    $lcPhases
     * @param  boolean $force - Bypass caching
     * @return int
     */
    public static function dbCountByProcessDbIdAndProcessConfigIdAndPhases($processDbId, $processConfigId, array $lcPhases, $force = false)
    {
        $initValues['processConfigId'] = $processConfigId;
        $initValues['processDbId'] = $processDbId;

        foreach ($lcPhases as $lcPhase) {
            $initValues[$lcPhase] = $lcPhase;
        }

        $sql = sprintf('SELECT count(*) AS counter
                          FROM %s
                         WHERE process_db_id = :processDbId
                           AND process_config_id = :processConfigId
                           AND life_cycle_phase IN (:%s)'
                        , self::VIEW_ELCA_PROCESS_ASSIGNMENTS
                        , join(', :', $lcPhases)
        );

        return self::_countBySql(get_class(), $sql, $initValues, 'counter', $force);
    }
    // End dbCountByProcessConfigId

    /**
     * Lazy find extended (on elca.processes_v)
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return DbObjectSet
     */
    public static function findExtended(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), self::VIEW_ELCA_PROCESSES, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findExtended

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return DbObjectSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaProcess::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaProcess::getTablename(), $initValues, $force);
    }
    // End dbCount


}
// End class ElcaProcessSet