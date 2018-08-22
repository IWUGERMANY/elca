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

use Beibob\Blibs\DataObjectSet;
use Exception;

/**
 * This file is part of the eLCA project
 *
 * eLCA
 *
 * Copyright (c) 2010-2011 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 * Licensed under Creative Commons license CC BY-NC 3.0
 * http://creativecommons.org/licenses/by-nc/3.0/de/
 */

/**
 * Builds the process search view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessSearchSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_PROCESS_SEARCH = 'elca.process_search_v';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a list of construction assets
     *
     * @param       $processDbId
     * @param array $keywords
     * @param       $lifeCycleIdent
     * @param bool  $force
     * @throws Exception
     * @internal param int $projectId
     * @return ElcaProcessSearchSet
     */
    public static function findByKeywords($processDbId, array $keywords, $lifeCycleIdent, $force = false)
    {
        $initValues = [];
        $initValues['lifeCycleIdent'] = $lifeCycleIdent;
        $initValues['processDbId'] = $processDbId;

        if(!$conditions = self::getSearchConditions($keywords, 'name', $initValues))
            return new ElcaProcessSearchSet();

        $sql = sprintf("SELECT *
                          FROM %s
                         WHERE process_db_id = :processDbId
                           AND life_cycle_ident = :lifeCycleIdent
                           AND %s
                      ORDER BY process_category_node_name
                             , name"
                       , self::VIEW_PROCESS_SEARCH
                       , $conditions
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByKeywords

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the search conditions
     *
     * @param array $keywords
     * @param       $searchField
     * @param array $initValues
     * @return string
     */
    private static function getSearchConditions(array $keywords, $searchField, array &$initValues)
    {
        $lftBoundary = $rgtBoundary = '%';

        $conditions = false;
        $queries = [];
        foreach($keywords as $index => $token)
        {
            $queries[] = sprintf("%s ilike :%s", $searchField, $varName = 'token'.$index);
            $initValues[$varName] = $lftBoundary . $token . $rgtBoundary;
        }

        $conditions = false;
        if(count($queries))
            $conditions = '('.join(' AND ', $queries).')';

        return $conditions;
    }
    // End getSearchConditions
}
// End EcoProcessSearchSet
