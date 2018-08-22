<?php

namespace Elca\Db;

use Beibob\Blibs\DbObjectSet;
/**
 * Handles a set of ElcaBenchmarkGroupThreshold
 *
 * @package    -
 * @class      ElcaBenchmarkGroupThresholdSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class ElcaBenchmarkGroupThresholdSet extends DbObjectSet
{

    public static function findByGroupId($groupId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::find(['group_id' => $groupId], $orderBy, $limit, $offset, $force);
    }

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return ElcaBenchmarkGroupThresholdSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaBenchmarkGroupThreshold::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaBenchmarkGroupThreshold::getTablename(), $initValues, $force);
    }


}
// End class ElcaBenchmarkGroupThresholdSet