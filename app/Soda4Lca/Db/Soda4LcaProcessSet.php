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

namespace Soda4Lca\Db;

use Beibob\Blibs\DbObjectSet;
/**
 * {BLIBSLICENCE}
 *
 * Handles a set of Soda4LcaProcess
 *
 * @package    -
 * @class      Soda4LcaProcessSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 *
 * $Id$
 */
class Soda4LcaProcessSet extends DbObjectSet
{

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return Soda4LcaProcessSet
     */
    public static function findUnassignedAndSkipped($importId, $force = false)
    {
        if(!$importId)
            return new Soda4LcaProcessSet();

        $initValues = array('importId' => $importId,
                            'statusSkipped' => Soda4LcaProcess::STATUS_SKIPPED,
                            'statusUnassigned' => Soda4LcaProcess::STATUS_UNASSIGNED);

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE import_id = :importId
                           AND status IN (:statusSkipped, :statusUnassigned)
                      ORDER BY status'
                       , Soda4LcaProcess::TABLE_NAME
                      );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findUnassignedAndSkipped

    /**
     * Lazy find
     *
     * @param      $importId
     * @param bool $force
     * @return Soda4LcaProcessSet
     */
    public static function findImported($importId, $force = false)
    {
        if(!$importId)
            return new Soda4LcaProcessSet();

        $initValues = array('importId' => $importId,
                            'statusSkipped' => Soda4LcaProcess::STATUS_OK,
                            'statusUnassigned' => Soda4LcaProcess::STATUS_UNASSIGNED);

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE import_id = :importId
                           AND status IN (:statusSkipped, :statusUnassigned)
                      ORDER BY status'
            , Soda4LcaProcess::TABLE_NAME
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findUnassignedAndSkipped
    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return Soda4LcaProcessSet
     */
    public static function findByStatus($importId, $status, $force = false)
    {
        if(!$importId || !$status)
            return new Soda4LcaProcessSet();

        $initValues = array('importId' => $importId,
                            'status' => $status);

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE import_id = :importId
                           AND status = :status
                      ORDER BY status'
                       , Soda4LcaProcess::TABLE_NAME
                      );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByStatus


    /**
     * Returns the number of imported datasets
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCountImported($importId, $force = false)
    {
        if(!$importId)
            return new Soda4LcaProcessSet();

        $initValues = array('importId' => $importId,
                            'statusOK' => Soda4LcaProcess::STATUS_OK,
                            'statusUnassigned' => Soda4LcaProcess::STATUS_UNASSIGNED);

        $sql = sprintf('SELECT count(*) AS counter
                          FROM %s
                         WHERE import_id = :importId
                           AND status IN (:statusOK, :statusUnassigned)'
                       , Soda4LcaProcess::TABLE_NAME
                      );

        return self::_countBySql(get_class(), $sql, $initValues, 'counter', $force);
    }
    // End dbCountImported


    /**
     * @param      $importId
     * @param bool $force
     */
    public static function dbCountUpdateables($importId, $force = false)
    {
        if(!$importId)
            return new Soda4LcaProcessSet();

        $initValues = array('importId' => $importId);
        
        $sql = sprintf('SELECT count(*) AS counter
                          FROM %s
                         WHERE import_id = :importId
                           AND latest_version IS NOT NULL'
            , Soda4LcaProcess::TABLE_NAME
        );

        return self::_countBySql(get_class(), $sql, $initValues, 'counter', $force);
    }
    // End dbCountUpdateables


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return Soda4LcaProcessSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), Soda4LcaProcess::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find


    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), Soda4LcaProcess::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End Soda4LcaProcessSet