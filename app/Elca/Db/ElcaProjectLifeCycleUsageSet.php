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
 * Handles a set of ElcaProjectLifeCycleUsage
 *
 * @package    -
 * @class      ElcaProjectLifeCycleUsageSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2016 BEIBOB Medienfreunde
 */
class ElcaProjectLifeCycleUsageSet extends DbObjectSet
{

    /**
     * @param       $projectId
     * @param array $initValues
     * @return ElcaProjectLifeCycleUsageSet
     */
    public static function findByProjectId($projectId, array $initValues = [], array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if (!$projectId) {
            return new self();
        }

        $initValues['project_id'] = $projectId;

        $sql = sprintf('SELECT lu.*
                          FROM %s lu
                          JOIN %s p ON lu.project_id = p.id
                          JOIN %s d ON d.id = p.process_db_id
                          JOIN %s l ON l.ident = lu.life_cycle_ident
                         WHERE d.is_en15804_compliant = (l.ident <> l.phase)
                           AND %s',
                ElcaProjectLifeCycleUsage::TABLE_NAME,
                ElcaProject::TABLE_NAME,
                ElcaProcessDb::TABLE_NAME,
                ElcaLifeCycle::TABLE_NAME,
                parent::buildConditions($initValues)
        );

        if ($orderBy) {
            $sql .= ' '. parent::buildOrderView($orderBy, $limit, $offset);
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return ElcaProjectLifeCycleUsageSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaProjectLifeCycleUsage::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaProjectLifeCycleUsage::getTablename(), $initValues, $force);
    }
}
// End class ElcaProjectLifeCycleUsageSet