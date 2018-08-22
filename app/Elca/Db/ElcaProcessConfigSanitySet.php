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
/**
 * Set of ElcaProcessConfigSanity
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaProcessConfigSanitySet extends DataObjectSet
{
    /**
     * Views
     */
    const TABLE_PROCESS_CONFIG_SANITIES = 'elca.process_config_sanities';
    const VIEW_PROCESS_CONFIG_SANITIES = 'elca.process_config_sanities_v';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all process configs that have unfullfilled requirements
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  boolean  $force     - Bypass caching
     * @return DataObjectSet
     */
    public static function find($includeFalsePositives = false, $force = false)
    {
        $sql = sprintf('SELECT pc.id AS process_config_id
                             , c.ref_num
                             , pc.name
                             , d.name AS process_db_name
                             , s.id
                             , s.status
                             , s.is_false_positive
                             , pc.is_reference
                             , array_agg(DISTINCT p.epd_type) FILTER (WHERE p.epd_type IS NOT NULL AND p.life_cycle_phase = \'prod\')  AS epd_types
                             , array_agg(DISTINCT p.life_cycle_name) AS epd_modules
                          FROM %s s
                          JOIN %s pc ON pc.id = s.process_config_id
                          JOIN %s c  ON c.node_id = pc.process_category_node_id
                     LEFT JOIN %s p  ON p.process_config_id = pc.id AND (s.process_db_id IS NULL OR s.process_db_id = p.process_db_id)
                     LEFT JOIN %s d  ON d.id = s.process_db_id
                            %s
                      GROUP BY pc.id
                             , c.ref_num
                             , pc.name
                             , d.name
                             , s.id
                             , s.status
                             , s.is_false_positive
                      ORDER BY d.name DESC, c.ref_num, pc.name, s.id'
                       , self::TABLE_PROCESS_CONFIG_SANITIES
                       , ElcaProcessConfig::TABLE_NAME
                       , ElcaProcessCategory::TABLE_NAME
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       , ElcaProcessDb::TABLE_NAME
                       , $includeFalsePositives? '' : 'WHERE s.is_false_positive = false'
                      );

        $resultSet = self::_findBySql(get_class(), $sql, null, $force);

        foreach ($resultSet as $result) {
            $result->epd_types = str_getcsv(trim($result->epd_types, '{}'));
            $result->epd_modules = str_getcsv(trim($result->epd_modules, '{}'));
        }

        return $resultSet;
    }
    // End findProcessConfigsSanity

    //////////////////////////////////////////////////////////////////////////////////////
}
// Ebd ProcessConfigSanitySet