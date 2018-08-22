<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObjectSet;

/**
 * Handles a set of LccElementCost
 *
 * @package    -
 * @class      LccElementCostSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2015 BEIBOB Medienfreunde
 */
class LccElementCostSet extends DbObjectSet
{
    const VIEW_ELEMENT_COMPOSITE_COSTS = 'lcc.element_composite_costs_v';

    /**
     * @param int $elementId
     * @return self
     */
    public static function findByCompositeElementId($elementId, $force = false)
    {
        $initValues = ['composite_element_id' => $elementId];
        return self::_find(get_class(), self::VIEW_ELEMENT_COMPOSITE_COSTS, $initValues, null, null, null, $force);
    }

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return LccElementCostSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccElementCost::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), LccElementCost::getTablename(), $initValues, $force);
    }
}
// End class LccElementCostSet