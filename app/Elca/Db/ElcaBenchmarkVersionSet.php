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
 * @package    elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaBenchmarkVersionSet extends DbObjectSet
{
    private const VIEW_BENCHMARK_VERSIONS_WITH_CONSTR_CLASS_IDS = 'elca.benchmark_versions_with_constr_classes';


    /**
     * @param int   $benchmarkSystemId
     * @param array $orderBy
     * @param int  $limit
     * @param int  $offset
     * @param bool  $force
     * @return ElcaBenchmarkVersionSet
     */
    public static function findByBenchmarkSystemId($benchmarkSystemId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::find(array('benchmark_system_id' => $benchmarkSystemId), $orderBy, $limit, $offset, $force);
    }

    /**
     * @param int   $benchmarkSystemId
     * @param array $orderBy
     * @param int  $limit
     * @param int  $offset
     * @param bool  $force
     * @return ElcaBenchmarkVersionSet
     */
    public static function findByBenchmarkSystemIdWithConstrClassIds($benchmarkSystemId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), self::VIEW_BENCHMARK_VERSIONS_WITH_CONSTR_CLASS_IDS, ['benchmark_system_id' => $benchmarkSystemId], $orderBy, $limit, $offset, $force);
    }

    /**
     * @param int  $benchmarkSystemId
     * @param bool  $force
     * @return int
     */
    public static function countProjectsByBenchmarkSystemId($benchmarkSystemId, $force = false)
    {
        $sql = sprintf('SELECT count(DISTINCT v.id) AS counter
                          FROM %s v
                          JOIN %s p ON v.id = p.benchmark_version_id
                         WHERE v.benchmark_system_id = :benchmarkSystemId'
                          , ElcaBenchmarkVersion::TABLE_NAME
                          , ElcaProject::TABLE_NAME
                          );

        return self::_countBySql(__CLASS__, $sql, array('benchmarkSystemId' => $benchmarkSystemId), 'counter', $force);
    }

    public static function findByIds(array $ids, $force = false)
    {
        $initValues = [];
        foreach ($ids as $k => $id) {
            $prop = 'id' . $k;
            $initValues[$prop] = $id;
        }

        $sql = sprintf('SELECT * 
FROM %s 
WHERe id IN (:%s)
ORDER BY id'
            , ElcaBenchmarkVersion::TABLE_NAME
        , implode(', :', array_keys($initValues))
        );

        return self::_findBySql(__CLASS__, $sql, $initValues, $force);
    }


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaBenchmarkVersionSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaBenchmarkVersion::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }

    public static function findWithConstrClassIds(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), self::VIEW_BENCHMARK_VERSIONS_WITH_CONSTR_CLASS_IDS, $initValues, $orderBy, $limit, $offset, $force);
    }


    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaBenchmarkVersion::getTablename(), $initValues, $force);
    }
}
// End class ElcaBenchmarkVersionSet