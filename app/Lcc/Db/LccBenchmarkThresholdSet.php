<?php

namespace Lcc\Db;

use Beibob\Blibs\DbObjectSet;

/**
 * Handles a set of LccBenchmarkThreshold
 *
 * @package    -
 * @class      LccBenchmarkThresholdSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class LccBenchmarkThresholdSet extends DbObjectSet
{
    public static function findByBenchmarkVersionId($benchmarkVersionId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::find(['benchmark_version_id' => $benchmarkVersionId], $orderBy, $limit, $offset, $force);
    }

    public static function findByBenchmarkVersionIdAndCategory($benchmarkVersionId, $category, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::find(['benchmark_version_id' => $benchmarkVersionId, 'category' => $category], $orderBy, $limit, $offset, $force);
    }

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return LccBenchmarkThresholdSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccBenchmarkThreshold::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }

    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  bool     $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), LccBenchmarkThreshold::getTablename(), $initValues, $force);
    }
}
// End class LccBenchmarkThresholdSet