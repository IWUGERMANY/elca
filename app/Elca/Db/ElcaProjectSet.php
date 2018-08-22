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
 * Handles a set of ElcaProject
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectSet extends DbObjectSet
{
    const PROJECTS_VIEW = 'elca.projects_view';

    /**
     * @param       $groupId
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @return ElcaProjectSet
     */
    public static function findByGroupId($groupId, array $orderBy = null, $limit = null, $offset = null)
    {
        $conditions['access_group_id'] = $groupId;

        return self::find($conditions, $orderBy, $limit, $offset);
    }

    /**
     * @param       $userId
     * @param array $initValues
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @return DbObjectSet
     */
    public static function findByOwnerId(
        $userId,
        array $initValues = [],
        array $orderBy = null,
        $limit = null,
        $offset = null,
        $force = false
    ) {
        $sql = sprintf('SELECT * FROM %s WHERE owner_id = :userId', self::PROJECTS_VIEW);

        if ($conditions = self::buildConditions($initValues)) {
            $sql .= ' AND '.$conditions;
        }

        if ($orderBySql = self::buildOrderView($orderBy, $limit, $offset)) {
            $sql .= ' '.$orderBySql;
        }

        return self::_findBySql(get_class(), $sql, array_merge($initValues, ['userId' => $userId]), $force);
    }

    /**
     * @param       $userId
     * @param array $initValues
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @return self
     */
    public static function findSharedByUserId(
        $userId,
        array $initValues = [],
        array $orderBy = null,
        $limit = null,
        $offset = null,
        $force = false
    ) {
        $sql = sprintf('SELECT * FROM %s WHERE owner_id <> :userId AND :userId = ANY (user_ids)', self::PROJECTS_VIEW);

        if ($conditions = self::buildConditions($initValues)) {
            $sql .= ' AND '.$conditions;
        }

        if ($orderBySql = self::buildOrderView($orderBy, $limit, $offset)) {
            $sql .= ' '.$orderBySql;
        }

        return self::_findBySql(get_class(), $sql, array_merge($initValues, ['userId' => $userId]), $force);
    }

    /**
     * @param       $userId
     * @param array $initValues
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @return DbObjectSet
     */
    public static function findByUserId(
        $userId,
        array $initValues = [],
        array $orderBy = null,
        $limit = null,
        $offset = null,
        $force = false
    ) {
        $sql = sprintf('SELECT * FROM %s WHERE :userId = ANY (user_ids)', self::PROJECTS_VIEW);

        if ($conditions = self::buildConditions($initValues)) {
            $sql .= ' AND '.$conditions;
        }

        if ($orderBySql = self::buildOrderView($orderBy, $limit, $offset)) {
            $sql .= ' '.$orderBySql;
        }

        return self::_findBySql(get_class(), $sql, array_merge($initValues, ['userId' => $userId]), $force);
    }

    /**
     * @param      $ownerId
     * @param bool $force
     * @return int
     */
    public static function countByOwnerId($ownerId, $force = false)
    {
        return self::dbCount(['owner_id' => $ownerId], $force);
    }

    /**
     * Reassigns the access group id
     */
    public static function reassignAccessGroupId($oldAccessGroupId, $newAccessGroupId)
    {
        if (!$oldAccessGroupId || !$newAccessGroupId) {
            return false;
        }

        $sql = sprintf(
            'UPDATE %s
                           SET access_group_id = :newAccessGroupId
                         WHERE access_group_id = :oldAccessGroupId'
            ,
            ElcaProject::TABLE_NAME
        );

        $Stmt = DbObject::prepareStatement(
            $sql,
            array(
                'oldAccessGroupId' => $oldAccessGroupId,
                'newAccessGroupId' => $newAccessGroupId,
            )
        );

        if (!$Stmt->execute()) {
            throw new \Exception(DbObject::getSqlErrorMessage($dbObjectName, $sql, $initValues));
        }

        return true;
    }

    /**
     * Reassigns the access group id
     */
    public static function reassignAccessGroupIdForProjectId($projectId, $oldAccessGroupId, $newAccessGroupId)
    {
        if (!$projectId || !$oldAccessGroupId || !$newAccessGroupId) {
            return false;
        }

        $sql        = sprintf(
            'UPDATE %s
                           SET access_group_id = :newAccessGroupId
                         WHERE id = :projectId AND access_group_id = :oldAccessGroupId'
            ,
            ElcaProject::TABLE_NAME
        );

        $initValues = [
            'projectId'        => $projectId,
            'oldAccessGroupId' => $oldAccessGroupId,
            'newAccessGroupId' => $newAccessGroupId,
        ];

        $statement = DbObject::prepareStatement($sql, $initValues);

        if (!$statement->execute()) {
            throw new \Exception(DbObject::getSqlErrorMessage(ElcaProject::class, $sql, $initValues));
        }

        return true;
    }

    /**
     * Reassigns the access group id
     */
    public static function reassignOwnerId($oldOwnerId, $newOwnerId)
    {
        if (!$oldOwnerId || !$newOwnerId) {
            return false;
        }

        $sql = sprintf(
            'UPDATE %s
                           SET owner_id = :newOwnerId
                         WHERE owner_id = :oldOwnerId'
            ,
            ElcaProject::TABLE_NAME
        );

        $initValues = [
            'oldOwnerId' => $oldOwnerId,
            'newOwnerId' => $newOwnerId,
        ];
        $Stmt       = DbObject::prepareStatement($sql, $initValues);

        if (!$Stmt->execute()) {
            throw new \Exception(DbObject::getSqlErrorMessage(get_class(), $sql, $initValues));
        }

        return true;
    }
    // End reassignOwnerId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Delete all projects for the given userId
     */
    public static function deleteByOwnerId($ownerId)
    {
        if (!$ownerId) {
            return false;
        }

        $sql = sprintf(
            'DELETE FROM %s
                              WHERE owner_id = :ownerId'
            ,
            ElcaProject::TABLE_NAME
        );

        $initValues = ['ownerId' => $ownerId];
        $Stmt       = DbObject::prepareStatement($sql, $initValues);

        if (!$Stmt->execute()) {
            throw new \Exception(DbObject::getSqlErrorMessage(get_class(), $sql, $initValues));
        }

        return true;
    }
    // End deleteByAccessGroupId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     * @return ElcaProjectSet
     */
    public static function find(
        array $initValues = null,
        array $orderBy = null,
        $limit = null,
        $offset = null,
        $force = false
    ) {
        return self::_find(get_class(), self::PROJECTS_VIEW, $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaProject::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaProjectSet