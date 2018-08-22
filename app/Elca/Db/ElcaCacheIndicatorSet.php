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
use Elca\Model\Process\Module;
use Exception;

/**
 * Handles a set of ElcaCacheIndicator
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaCacheIndicatorSet extends DbObjectSet
{

    const VIEW_INDICATORS = 'elca_cache.indicators_v';
    // public


    /**
     * Copies all indicator values of the given itemId
     */
    public static function copy($srcItemId, $dstItemId)
    {
        if (!$srcItemId || !$dstItemId) {
            return false;
        }

        $sql = sprintf(
            'INSERT INTO %s (item_id, life_cycle_ident, indicator_id, process_id, value, ratio, is_partial)
                             SELECT %d::int AS item_id
                                  , life_cycle_ident
                                  , indicator_id
                                  , process_id
                                  , value
                                  , ratio
                                  , is_partial
                               FROM %s
                              WHERE item_id = :srcItemId'
            ,
            ElcaCacheIndicator::TABLE_NAME
            ,
            $dstItemId
            ,
            ElcaCacheIndicator::TABLE_NAME
        );

        $Stmt = DbObject::prepareStatement($sql, $initValues = ['srcItemId' => $srcItemId]);

        if (!$Stmt->execute()) {
            throw new Exception(DbObject::getSqlErrorMessage(get_class(), $sql, $initValues));
        }

        return true;
    }
    // End copy


    /**
     * Deletes all indicators for the given itemId and lifeCyclePhase
     */
    public static function deleteByItemIdAndLifeCyclePhase($itemId, $lcPhase)
    {
        if (!$itemId || !$lcPhase) {
            return false;
        }

        $sql = sprintf(
            'DELETE FROM %s
                              WHERE item_id = :itemId
                                AND life_cycle_ident IN (SELECT ident
                                                           FROM %s l
                                                          WHERE l.phase = :lcPhase)'
            ,
            ElcaCacheIndicator::TABLE_NAME
            ,
            ElcaLifeCycle::TABLE_NAME
        );

        $Stmt = DbObject::prepareStatement(
            $sql,
            $initValues = [
                'itemId' => $itemId,
                'lcPhase' => $lcPhase,
            ]
        );

        if (!$Stmt->execute()) {
            throw new Exception(DbObject::getSqlErrorMessage(null, $sql, $initValues));
        }

        return true;
    }
    // End deleteByItemIdAndLifeCyclePhase


    /**
     * Deletes all indicators for the given itemId and lifeCycleIdent
     */
    public static function deleteByItemIdAndLifeCycleIdent($itemId, $lcIdent)
    {
        if (!$itemId || !$lcIdent) {
            return false;
        }

        $sql = sprintf(
            'DELETE FROM %s
                              WHERE item_id = :itemId
                                AND life_cycle_ident = :lcIdent'
            ,
            ElcaCacheIndicator::TABLE_NAME
        );

        $Stmt = DbObject::prepareStatement(
            $sql,
            $initValues = [
                'itemId' => $itemId,
                'lcIdent' => $lcIdent,
            ]
        );

        if (!$Stmt->execute()) {
            throw new Exception(DbObject::getSqlErrorMessage(null, $sql, $initValues));
        }

        return true;
    }
    // End deleteByItemIdAndLifeCyclePhase


    /**
     * Deletes all indicators for element types in all project variants of a project
     *
     * @param int $projectId
     * @throws Exception
     * @return boolean
     */
    public static function deleteElementTypeIndicatorsByProjectId($projectId)
    {
        if (!$projectId) {
            return false;
        }

        $sql = sprintf(
            'DELETE FROM %s
                              WHERE item_id IN (SELECT t.item_id
                                                  FROM %s t
                                                  JOIN %s v ON v.id = t.project_variant_id
                                                 WHERE v.project_id = :projectId
                                               )'
            ,
            ElcaCacheIndicator::TABLE_NAME
            ,
            ElcaCacheElementType::TABLE_NAME
            ,
            ElcaProjectVariant::TABLE_NAME
        );

        $Stmt = DbObject::prepareStatement($sql, $initValues = ['projectId' => $projectId]);

        if (!$Stmt->execute()) {
            throw new Exception(DbObject::getSqlErrorMessage(null, $sql, $initValues));
        }

        return true;
    }

    // End deleteElementTypeIndicatorsByProjectId


    /**
     * @param int $projectId
     * @return int
     */
    public static function countDuplicateTotals($projectId)
    {
        if (!$projectId) {
            return 0;
        }

        $sql = sprintf(
            'SELECT count(*) AS counter
FROM %s ci
    JOIN %s i ON i.id = ci.indicator_id
WHERE life_cycle_ident = :lcIdent
    AND i.ident = :indicatorIdent
    AND project_id = :projectId
GROUP BY item_id, life_cycle_ident
HAVING count(*) > 1
LIMIT 1',
            self::VIEW_INDICATORS,
            ElcaIndicator::TABLE_NAME
        );

        return self::_countBySql(
            get_class(),
            $sql,
            [
                'lcIdent' => ElcaLifeCycle::PHASE_TOTAL,
                'indicatorIdent' => ElcaIndicator::IDENT_GWP,
                'projectId' => $projectId,
            ]
        );
    }

    /**
     * @param int $projectId
     * @return int
     */
    public static function countA1A2OrA3Totals($projectId)
    {
        if (!$projectId) {
            return 0;
        }

        $sql = sprintf(
            'SELECT count(*) AS counter
FROM %s ci
    JOIN %s i ON i.id = ci.indicator_id
WHERE life_cycle_ident IN (:a1, :a2, :a3)
    AND type = :type
    AND i.ident = :indicatorIdent
    AND is_partial = false
    AND project_id = :projectId',
            self::VIEW_INDICATORS,
            ElcaIndicator::TABLE_NAME
        );

        return self::_countBySql(
            get_class(),
            $sql,
            [
                'a1' => Module::A1,
                'a2' => Module::A2,
                'a3' => Module::A3,
                'type' => ElcaCacheProjectVariant::class,
                'indicatorIdent' => ElcaIndicator::IDENT_GWP,
                'projectId' => $projectId,
            ]
        );
    }


    /**
     * Lazy find
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     * @return ElcaCacheIndicatorSet
     */
    public static function find(
        array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false
    ) {
        return self::_find(
            get_class(),
            ElcaCacheIndicator::getTablename(),
            $initValues,
            $orderBy,
            $limit,
            $offset,
            $force
        );
    }
    // End find


    /**
     * Lazy count
     *
     * @param  array   $initValues - key value array
     * @param  boolean $force      - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaCacheIndicator::getTablename(), $initValues, $force);
    }
    // End dbCount
}

// End class ElcaCacheIndicatorSet