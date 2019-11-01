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
use Elca\Model\Process\Stage;

/**
 * Handles a set of ElcaProcessConversionVersions
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConversionVersionSet extends DbObjectSet
{
    public static function findByProcessDbId(int $processDbId, array $orderBy = null, $limit = null, $offset = null,
        $force = false)
    {
        $initValues = ['process_db_id' => $processDbId];

        return self::_find(\get_class(), ElcaProcessConversionVersion::TABLE_NAME, $initValues, $orderBy, $limit,
            $offset, $force);
    }

    public static function findByConversionId(int $conversionId, array $orderBy = null, $limit = null, $offset = null,
        $force = false)
    {
        $initValues = ['conversion_id' => $conversionId];

        return self::_find(\get_class(), ElcaProcessConversionVersion::TABLE_NAME, $initValues, $orderBy, $limit,
            $offset, $force);
    }

    public static function findExtendedByProcessConfigIdAndProcessDbId($processConfigId, $processDbId,
        array $orderBy = null, $force = false)
    {
        if (!$processConfigId || !$processDbId) {
            return new ElcaProcessConversionVersionSet();
        }

        $initValues = [
            'processConfigId' => $processConfigId,
            'processDbId'     => $processDbId,
        ];

        $sql = sprintf(
            'SELECT cv.*
                                    , c.process_config_id
                                    , c.in_unit
                                    , c.out_unit
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




    public static function findIntersectConversionsForMultipleProcessDbs(int $processConfigId, array $processDbIds, bool $force = false)
    {
        $initValues = [
            'processConfigId' => $processConfigId,
            'lcOp' => Stage::USE,

        ];

        $processDbIdsInClause = [];
        foreach ($processDbIds as $processDbId) {
            $processDbIdsInClause[] = $name = ':processDbId' . $processDbId;
            $initValues[$name] = $processDbId;
        }

        $sql = sprintf('WITH ref_units AS (
    SELECT c.process_db_id, array_agg(DISTINCT ref_unit) AS units
    FROM %1$s c
             JOIN %2$s a
    ON a.process_db_id = c.process_db_id and a.process_config_id = c.process_config_id
    WHERE c.process_config_id = :processConfigId AND a.life_cycle_phase <> :lcOp
      AND c.process_db_id IN (%3$s)
    GROUP BY c.process_db_id
),
conversions AS (
        SELECT c.process_db_id,
            array_agg(DISTINCT c.id) AS conversion_ids
        FROM %1$s c
                 JOIN ref_units u
        ON c.process_db_id = u.process_db_id AND (c.in_unit = ANY (u.units) OR c.out_unit = ANY (u.units))
        WHERE process_config_id = :processConfigId
        GROUP BY c.process_db_id
),
compat_conversions AS (
     SELECT public.array_intersect_agg(conversion_ids ORDER BY array_length(conversion_ids, 1) DESC) :: int[] AS ids
     FROM conversions
)
SELECT c.id AS conversion_id
     , process_db_id
     , process_config_id
     , factor
     , ident
     , in_unit
     , out_unit
     , created
     , modified
  FROM %1$s c JOIN compat_conversions compat ON c.id = ANY (compat.ids)
 WHERE process_db_id IN (%3$s)',
            ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS,
            ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS,
            implode(',', $processDbIdsInClause)
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }

    /**
     * Lazy find
     *
     * @param array   $initValues - key value array
     * @param array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param integer $limit      - limit on resultset
     * @param integer $offset     - offset on resultset
     * @param boolean $force      - Bypass caching
     * @return ElcaProcessConversionVersionSet
     */
    public static function find(
        array $initValues = null,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        $force = false
    )
    {
        return self::_find(get_class(), ElcaProcessConversionVersion::TABLE_NAME, $initValues, $orderBy, $limit,
            $offset, $force);
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

    public static function countByConversionId(int $conversionId, array $orderBy = null, $limit = null, $offset = null,
        $force = false)
    {
        $initValues = ['conversion_id' => $conversionId];

        return self::_count(\get_class(), ElcaProcessConversionVersion::TABLE_NAME, $initValues, $force);
    }

}
