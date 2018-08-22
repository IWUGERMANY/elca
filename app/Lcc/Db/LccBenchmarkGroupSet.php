<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObjectSet;

/**
 * Handles a set of LccBenchmarkGroup
 *
 * @package    -
 * @class      LccBenchmarkGroupSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class LccBenchmarkGroupSet extends DbObjectSet
{
    public static function findByBenchmarkVersionIdAndCategory($benchmarkVersionId, $category, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::find(['benchmark_version_id' => $benchmarkVersionId, 'category' => $category], $orderBy, $limit, $offset, $force);
    }

    public static function findByBenchmarkVersionId($benchmarkVersionId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::find(['benchmark_version_id' => $benchmarkVersionId], $orderBy, $limit, $offset, $force);
    }

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return LccBenchmarkGroupSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccBenchmarkGroup::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find
    

    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  bool     $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), LccBenchmarkGroup::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class LccBenchmarkGroupSet