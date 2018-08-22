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

use Beibob\Blibs\DbObject;
use Beibob\Blibs\DbObjectSet;
/**
 * Handles a set of ElcaIndicator
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaIndicatorSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_INDICATORS = 'elca.indicators_v';

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    public static function refreshIndicatorsView()
    {
        $sql = sprintf('REFRESH MATERIALIZED VIEW %s', self::VIEW_ELCA_INDICATORS);

        $Stmt = DbObject::prepareStatement($sql);
        $Stmt->execute();
    }


    /**
     * Find indicators by processDbId
     *
     * @param  int     $processDbId  - filter by processDbId
     * @param  boolean $withExcludes - init all indicators
     * @param bool     $includeHidden
     * @param  array   $orderBy      - array map of columns on to directions array('id' => 'DESC')
     * @param  int     $limit
     * @param  boolean $force        - Bypass caching
     * @return ElcaIndicatorSet
     */
    public static function findByProcessDbId($processDbId, $withExcludes = false, $includeHidden = false, array $orderBy = null, $limit = null, $force = false)
    {
        if (!$processDbId)
            return new ElcaIndicatorSet();

        $initValues = array('process_db_id' => $processDbId);

        if (!$withExcludes)
            $initValues['is_excluded'] = false;

        if (!$includeHidden)
            $initValues['is_hidden'] = false;

        if (!$orderBy)
            $orderBy = array('p_order' => 'ASC');

        return self::_find(get_class(), self::VIEW_ELCA_INDICATORS, $initValues, $orderBy, $limit, null, $force);
    }
    // End findByProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find indicators by processDbId and adds PET indicator
     *
     * @param  int     $processDbId  - filter by processDbId
     * @param  boolean $withExcludes - init all indicators
     * @param  array   $orderBy      - array map of columns on to directions array('id' => 'DESC')
     * @param  int     $limit
     * @param  boolean $force        - Bypass caching
     * @return ElcaIndicatorSet
     */
    public static function findWithPetByProcessDbId($processDbId, $withExcludes = false, $includeHidden = false, array $orderBy = null, $limit = null, $force = false)
    {
        if(!$processDbId)
            return new ElcaIndicatorSet();

        $initValues = array('processDbId' => $processDbId,
                            'petIdent' => ElcaIndicator::IDENT_PET);

        $sql = sprintf('SELECT i.*
                          FROM %s i
                         WHERE i.process_db_id = :processDbId %s %s
                        UNION
                         SELECT i.*
                              , null AS process_db_id
                          FROM %s i
                         WHERE i.ident = :petIdent'
                        , self::VIEW_ELCA_INDICATORS
                        , !$withExcludes? ' AND is_excluded = false' : ''
                        , !$includeHidden ? ' AND is_hidden = false' : ''
                        , ElcaIndicator::TABLE_NAME
        );

        if(!$orderBy)
            $orderBy = array('p_order' => 'ASC');

        $sql .= ' '. self::buildOrderView($orderBy, $limit);

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findWithPetByProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaIndicatorSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaIndicator::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaIndicator::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaIndicatorSet