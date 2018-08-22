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
namespace Stlb\Db;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\DbObjectSet;
use Exception;
use PDO;

class StlbElementSet extends DbObjectSet
{
    /**
     * Finds counts for the given projectId and dinCode
     *
     * @param  int  $projectId
     * @param  int  $dinCode
     * @return boolean
     */
    public static function countByProjectIdAndDinCode($projectId, $dinCode)
    {
        if(!$projectId)
            return ['total' => 0, 'dinCode' => 0, 'visible' => 0];

        $sql = sprintf('SELECT count(*) AS total
                             , sum(CASE WHEN din_code = :dinCode THEN 1 ELSE 0 END) AS "dinCode"
                             , sum(CASE WHEN din_code = :dinCode THEN is_visible::int ELSE 0 END) AS visible
                          FROM %s
                         WHERE project_id = :projectId',
                        StlbElement::TABLE_NAME
                       );
        $initValues = ['projectId' => $projectId, 'dinCode' => $dinCode];
        $Stmt = DbObject::prepareStatement($sql, $initValues);

        if(!$Stmt->execute())
            throw new Exception(DbObject::getSqlErrorMessage(get_class(), $sql, $initValues));

        return $Stmt->fetch(PDO::FETCH_ASSOC);
    }
    // end countByProjectIdAndDinCode


    /**
     * Deletes all elements for project with id and dincode
     *
     * @param  int  $projectId
     * @param  int  $dinCode
     * @return boolean
     */
    public static function deleteByProjectIdAndDinCode($projectId,  $dinCode)
    {
        if (!$projectId || !$dinCode)
            return false;

        $sql = sprintf('DELETE FROM %s WHERE project_id = :projectId AND din_code = :dinCode',
                        StlbElement::TABLE_NAME
                       );
        $initValues = ['projectId' => $projectId, 'dinCode' => $dinCode];
        $Stmt = DbObject::prepareStatement($sql, $initValues);

        if(!$Stmt->execute())
            throw new Exception(DbObject::getSqlErrorMessage(get_class(), $sql, $initValues));
        return true;
    }
    // end deleteByProjectIdAndDinCode


    /**
     * Deletes all elements for a project
     *
     * @param  int  $projectId
     * @return boolean
     */
    public static function deleteByProjectId($projectId)
    {
        if (!$projectId)
            return false;

        $sql = sprintf('DELETE FROM %s WHERE project_id = :projectId',
                        StlbElement::TABLE_NAME
                       );
        $initValues = ['projectId' => $projectId];
        $Stmt = DbObject::prepareStatement($sql, $initValues);

        if(!$Stmt->execute())
            throw new Exception(DbObject::getSqlErrorMessage(get_class(), $sql, $initValues));

        return true;
    }
    // end deleteByProjectIdAndDinCode


    /**
     * Deletes all elements for project with id and dincode
     *
     * @param  int  $projectId
     * @param  int  $dinCode
     * @return boolean
     */
    public static function markAllAsVisible($projectId,  $dinCode)
    {
        if (!$projectId || !$dinCode)
            return false;

        $sql = sprintf('UPDATE %s SET is_visible = true WHERE project_id = :projectId AND din_code = :dinCode',
                        StlbElement::TABLE_NAME
                       );
        $initValues = ['projectId' => $projectId, 'dinCode' => $dinCode];
        $Stmt = DbObject::prepareStatement($sql, $initValues);

        if(!$Stmt->execute())
            throw new Exception(DbObject::getSqlErrorMessage(get_class(), $sql, $initValues));
        return true;
    }
    // end markAllAsVisible


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return StlbElementSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), StlbElement::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), StlbElement::getTablename(), $initValues, $force);
    }
    // End dbCount

}
// End StlbElementSet