<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObjectSet;

class LccEnergySourceCostSet extends DbObjectSet
{
    public static function findByVersionId(int $versionId, array $orderBy = null, $force = false)
    {
        return self::find(['version_id' => $versionId], $orderBy, null, null, $force);
    }

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return LccEnergySourceCostSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccEnergySourceCost::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), LccEnergySourceCost::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class LccEnergySourceCostSet