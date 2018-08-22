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

use Beibob\Blibs\DataObjectSet;
/**
 *
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class Soda4LcaReportSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_SODA4LCA_DATABASES = 'soda4lca.databases_v';
    const VIEW_SODA4LCA_PROCESSES = 'soda4lca.processes_v';
    const VIEW_SODA4LCA_PROCESSES_WITH_PROCESS_CONFIGS = 'soda4lca.processes_with_process_configs_v';
    const VIEW_SODA4LCA_PROCESS_CONFIGS_UNRESOLVED = 'soda4lca.process_configs_unresolved_v';


    /**
     * Returns a list of imported processDbs
     *
     * @param  int $project
     * @return Soda4LcaReportSet
     */
    public static function findImportedDatabases($force = false)
    {
        return self::_find(get_class(),
                           self::VIEW_SODA4LCA_DATABASES,
                           null,
                           array('created' => 'DESC'));
    }
    // End findImportedDatabases


    /**
     * Returns a list of imported processDbs
     *
     * @param  int $project
     * @return Soda4LcaReportSet
     */
    public static function findImportedProcesses($importId, array $initValues = array(), $withProcessConfigs = false, $force = false)
    {
        if(!$importId)
            return new Soda4LcaReportSet();

        $initValues['import_id'] = $importId;

        return self::_find(get_class(),
                           $withProcessConfigs? self::VIEW_SODA4LCA_PROCESSES_WITH_PROCESS_CONFIGS : self::VIEW_SODA4LCA_PROCESSES,
                           $initValues,
                           array('name' => 'ASC'));
    }
    // End findImportedProcesses


    /**
     * Find unresolved process configs
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return Soda4LcaReportSet
     */
    public static function findUnresolvedProcessConfigs(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), self::VIEW_SODA4LCA_PROCESS_CONFIGS_UNRESOLVED, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findUnresolved

}
// End Soda4LcaReportSet