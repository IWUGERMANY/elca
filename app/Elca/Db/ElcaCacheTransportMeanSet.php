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
 * Handles a set of ElcaCacheTransportMean
 *
 * @package    -
 * @class      ElcaCacheTransportMeanSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaCacheTransportMeanSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_CACHE_TRANSPORT_MEANS = 'elca_cache.transport_means_v';


    /**
     * Find by parentItemId
     *
     * @param          $parentItemId
     * @param  array   $orderBy - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit   - limit on resultset
     * @param  integer $offset  - offset on resultset
     * @param  boolean $force   - Bypass caching
     * @return ElcaProjectFinalEnergyDemandSet
     */
    public static function findByParentItemId($parentItemId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$parentItemId)
            return new ElcaCacheTransportMeanSet();

        $initValues = array('parent_id' => $parentItemId);
        return self::_find(get_class(), self::VIEW_ELCA_CACHE_TRANSPORT_MEANS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return ElcaCacheTransportMeanSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaCacheTransportMean::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaCacheTransportMean::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaCacheTransportMeanSet