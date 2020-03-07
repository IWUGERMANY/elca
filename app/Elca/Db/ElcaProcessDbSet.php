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
 * Handles a set of ElcaProcessDb
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessDbSet extends DbObjectSet
{
    public static function findActive(bool $force = false)
    {
        return self::_find(
            get_class(),
            ElcaProcessDb::TABLE_NAME,
            [
                'is_active' => true,
            ],
            [
                'name' => 'ASC',
            ],
            null,
            null,
            $force
        );
    }

    /**
     *
     * Note: There is a weired behaviour with the query in
     * ElcaProcessDbSet::findElementCompatibles: on Postgresql 9.4 when the
     * planner decides to use a hashAggregate, the array_intersect_agg will
     * return all dbs if there are none! when groupAggregate is used then null
     * is returned, which is the expected behaviour.
     *
     * In Postgresql 10 this seems to be not a problem.
     *
     * Recent version of array_intersect_agg uses intarray extension. The behaviour
     * described above may not be valid anymore!
     *
     * @param ElcaElement $element
     * @param array|null  $orderBy
     * @param null        $limit
     * @param null        $offset
     * @param bool        $force
     * @return static
     */
    public static function findElementCompatibles(
        ElcaElement $element,
        array $orderBy = null, $limit = null, $offset = null, bool $force = false
    ) {
        if ($element->isComposite()) {
            $innerSql = sprintf(
                <<<SQL
SELECT
                 ec.process_config_id,
                 array_agg(process_db_id ORDER BY process_db_id) AS process_db_ids
             FROM %s ec
                 JOIN %s a ON ec.process_config_id = a.process_config_id
                 JOIN %s ce ON ce.element_id = ec.element_id
             WHERE ce.composite_element_id = :elementId
                   AND a.life_cycle_phase = 'prod'
             GROUP BY ec.process_config_id
SQL
                ,ElcaElementComponent::TABLE_NAME
                ,ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaCompositeElement::TABLE_NAME
            );
        } else {
            $innerSql = sprintf(
                <<<SQL
SELECT
                 ec.process_config_id,
                 array_agg(process_db_id ORDER BY process_db_id) AS process_db_ids
             FROM %s ec
                 JOIN %s a ON ec.process_config_id = a.process_config_id
             WHERE ec.element_id = :elementId
                   AND a.life_cycle_phase = 'prod'
             GROUP BY ec.process_config_id
SQL
                , ElcaElementComponent::TABLE_NAME
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            );
        }

        $sql = sprintf(
            'WITH common_dbs AS (
    SELECT
        public.array_intersect_agg(process_db_ids ORDER BY array_length(process_db_ids, 1) DESC) :: int[] AS ids
    FROM (
             %s
         ) x
)
SELECT db.*
FROM %s db
JOIN common_dbs c ON db.id = ANY (c.ids)
WHERE db.is_active',
            $innerSql,
            ElcaProcessDb::TABLE_NAME
        );

        if ($orderBy) {
            $sql .= ' '.self::buildOrderView($orderBy, $limit, $offset);
        }

        return self::_findBySql(get_class(), $sql, ['elementId' => $element->getId()], $force);
    }

    public static function findForProcessConfigId(int $processConfigId, $force = false): ElcaProcessDbSet
    {
        if (!$processConfigId) {
            return new self();
        }

        $sql = sprintf("SELECT DISTINCT db.* 
        FROM %s db
        JOIN %s pa ON pa.process_db_id = db.id
        WHERE pa.process_config_id = :processConfigId
        ORDER BY db.version, db.id",
        ElcaProcessDb::TABLE_NAME,
        ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
        );

        return self::_findBySql(get_class(), $sql, ['processConfigId' => $processConfigId], $force);
    }

    public static function findRecentForProcessConfigId(int $processConfigId, int $limit = 1, $force = false): ElcaProcessDbSet
    {
        if (!$processConfigId) {
            return new self();
        }

        $sql = sprintf("SELECT DISTINCT db.* 
        FROM %s db
        JOIN %s pa ON pa.process_db_id = db.id
        WHERE pa.process_config_id = :processConfigId ORDER BY db.id DESC LIMIT %s",
            ElcaProcessDb::TABLE_NAME,
            ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS,
            $limit
        );

        return self::_findBySql(get_class(), $sql, ['processConfigId' => $processConfigId], $force);
    }

    /**
     * Lazy find
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     * @return ElcaProcessDbSet
     */
    public static function find(
        array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false
    ) {
        return self::_find(get_class(), ElcaProcessDb::TABLE_NAME, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy count
     *
     * @param  array   $initValues - key value array
     * @param  boolean $force      - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaProcessDb::getTablename(), $initValues, $force);
    }

    public static function findByIds(array $processDbIds, array $orderBy = null, $limit = null, $offset = null)
    {
        if (!$processDbIds) {
            return new self();
        }

        return self::_findBySql(
            get_class(),
            sprintf(
                'SELECT * FROM %s WHERE id IN (%s) %s',
                ElcaProcessDb::TABLE_NAME,
                \implode(',', $processDbIds),
                self::buildOrderView($orderBy, $limit, $offset)
            )
        );
    }

    public static function findEn15804Compatibles(array $initValues = [], array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $initValues['is_en15804_compliant'] = true;
        return self::_find(get_class(), ElcaProcessDb::TABLE_NAME, $initValues, $orderBy, $limit, $offset, $force);

    }
}
