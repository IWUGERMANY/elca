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
class ElcaBenchmarkThresholdSet extends DbObjectSet
{
    const VIEW_ELCA_BENCHMARK_THRESHOLDS = 'elca.benchmark_thresholds_v';


    /**
     * @param int   $versionId
     * @param string  $indicatorId
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @return ElcaBenchmarkThresholdSet
     */
    public static function findByVersionIdAndIndicatorId($versionId, $indicatorId = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $initValues = array('benchmark_version_id' => $versionId);

        if($indicatorId)
            $initValues['indicator_id'] = $indicatorId;

        return self::find($initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByVersionId


    /**
     * @param int   $versionId
     * @param string  $indicatorIdent
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @internal param null $indicatorId
     * @return ElcaBenchmarkThresholdSet
     */
    public static function findByVersionIdAndIndicatorIdent($versionId, $indicatorIdent = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $initValues = array('benchmark_version_id' => $versionId);

        if($indicatorIdent)
            $initValues['indicator_ident'] = $indicatorIdent;

        return self::findWithIdent($initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByVersionId


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaBenchmarkThresholdSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaBenchmarkThreshold::TABLE_NAME, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaBenchmarkThresholdSet
     */
    public static function findWithIdent(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), self::VIEW_ELCA_BENCHMARK_THRESHOLDS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findWithIdent


    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaBenchmarkThreshold::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaBenchmarkThresholdSet