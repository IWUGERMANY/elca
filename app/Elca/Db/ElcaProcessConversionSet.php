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
 * Handles a set of ElcaProcessConversion
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConversionSet extends DbObjectSet
{
    const VIEW_PROCESS_CONVERSIONS = 'elca.process_conversions_v';

    public static function findByProcessConfigId($processConfigId, array $orderBy = null, $force = false)
    {
        if (!$processConfigId) {
            return new ElcaProcessConversionSet();
        }

        $initValues = array('process_config_id' => $processConfigId);

        return self::_find(
            get_class(),
            ElcaProcessConversion::getTablename(),
            $initValues,
            $orderBy,
            null,
            null,
            $force
        );
    }


    public static function findByProcessConfigIdAndProcessDbId($processConfigId, $processDbId, array $orderBy = null,
        $force = false)
    {
        if (!$processConfigId || !$processDbId) {
            return new ElcaProcessConversionSet();
        }

        $initValues = [
            'process_config_id' => $processConfigId,
            'process_db_id'     => $processDbId,
        ];

        $sql = sprintf('SELECT DISTINCT c.*
                                 FROM %s c 
                                 JOIN %s cv ON c.id = cv.conversion_id
                                WHERE (c.process_config_id, cv.process_db_id) = (:processConfigId, :processDbId)',
            ElcaProcessConversion::TABLE_NAME,
            ElcaProcessConversionVersion::TABLE_NAME
        );

        if ($orderBy) {
            $sql .= ' ' . self::buildOrderView($orderBy);
        }

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all conversions for the given process config id and inUnit
     *
     * @param int     $processConfigId
     * @param array   $orderBy - array map of columns on to directions array('id' => 'DESC')
     * @param boolean $force   - Bypass caching
     * @return ElcaProcessConversionSet
     */
    public static function findByProcessConfigIdAndInUnit(
        $processConfigId,
        $inUnit,
        array $orderBy = null,
        $limit = null,
        $force = false
    )
    {
        if (!$processConfigId) {
            return new ElcaProcessConversionSet();
        }

        $initValues = array(
            'process_config_id' => $processConfigId,
            'in_unit'           => $inUnit,
        );

        return self::_find(
            get_class(),
            ElcaProcessConversion::getTablename(),
            $initValues,
            $orderBy,
            $limit,
            null,
            $force
        );
    }
    // End findByProcessConfigIdAndInUnit


    /**
     * Find all conversions for the given process config id and inUnit
     *
     * @param int     $processConfigId
     * @param array   $orderBy - array map of columns on to directions array('id' => 'DESC')
     * @param boolean $force   - Bypass caching
     * @return ElcaProcessConversionSet
     */
    public static function findByProcessConfigIdAndUnit(
        $processConfigId,
        $unit,
        $orderBy = null,
        $limit = null,
        $force = false
    )
    {
        if (!$processConfigId) {
            return new ElcaProcessConversionSet();
        }

        $initValues = [
            'processConfigId' => $processConfigId,
            'unit'            => $unit,
        ];

        $sql = sprintf(
            'SELECT * FROM %s WHERE process_config_id = :processConfigId AND :unit IN (in_unit, out_unit)',
            ElcaProcessConversion::TABLE_NAME
        );

        if ($orderSql = self::buildOrderView($orderBy, $limit)) {
            $sql .= ' ' . $orderSql;
        }

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }

    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param array   $initValues - key value array
     * @param array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param integer $limit      - limit on resultset
     * @param integer $offset     - offset on resultset
     * @param boolean $force      - Bypass caching
     * @return ElcaProcessConversionSet
     */
    public static function find(
        array $initValues = null,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        $force = false
    )
    {
        return self::_find(
            get_class(),
            ElcaProcessConversion::getTablename(),
            $initValues,
            $orderBy,
            $limit,
            $offset,
            $force
        );
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy count
     *
     * @param array   $initValues - key value array
     * @param boolean $force      - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaProcessConversion::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaProcessConversionSet