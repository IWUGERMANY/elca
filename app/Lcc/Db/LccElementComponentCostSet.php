<?php
namespace Lcc\Db;

use Beibob\Blibs\DbObjectSet;

/**
 * Handles a set of LccElementComponentCost
 *
 * @package    -
 * @class      LccElementComponentCostSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2015 BEIBOB Medienfreunde
 */
class LccElementComponentCostSet extends DbObjectSet
{
    const VIEW_ELEMENT_COMPONENT_COSTS = 'lcc.element_component_costs_v';

    /**
     * @param int $elementId
     * @param bool $force
     * @return LccElementComponentCostSet
     */
    public static function findLayersByElementId($elementId, $force = false)
    {
        $initValues = [
          'element_id' => $elementId,
          'is_layer' => true
        ];

        $orderBy = [
            'layer_position' => 'ASC',
            'element_component_id' => 'ASC'
        ];

        return self::_find(get_class(), self::VIEW_ELEMENT_COMPONENT_COSTS, $initValues, $orderBy, null, null, $force);
    }

    /**
     * @param int $elementId
     * @param bool $force
     * @return LccElementComponentCostSet
     */
    public static function findComponentsByElementId($elementId, $force = false)
    {
        $initValues = [
            'element_id' => $elementId,
            'is_layer' => false
        ];

        $orderBy = [
            'element_component_id' => 'ASC'
        ];

        return self::_find(get_class(), self::VIEW_ELEMENT_COMPONENT_COSTS, $initValues, $orderBy, null, null, $force);
    }

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return LccElementComponentCostSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), LccElementComponentCost::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), LccElementComponentCost::getTablename(), $initValues, $force);
    }

}
// End class LccElementComponentCostSet