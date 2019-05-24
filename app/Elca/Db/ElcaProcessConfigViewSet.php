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
use Beibob\Blibs\DbObjectSet;

/**
 * Set of ElcaProcesses
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfigViewSet extends DataObjectSet
{
    public static function findProcessConfigsByDbAndLcIdents(int $processDbId, array $lcIdentsIncluded = null, array $lcIdentsExcluded = null) {
        if (!$processDbId || (!$lcIdentsIncluded && !$lcIdentsExcluded)) {
            return new DbObjectSet();
        }

        $sql = sprintf('SELECT * FROM (
       SELECT    pc.id AS process_config_id
            , pc.name
            , c.ref_num AS process_category
            , c.name AS process_category_name
            , array_agg(DISTINCT a.life_cycle_ident) AS life_cycle_idents
            , array_agg(a.name_orig) FILTER ( WHERE life_cycle_phase = \'eol\')  AS process_names
            , array_agg(a.epd_type) FILTER ( WHERE life_cycle_phase = \'eol\')  AS epd_types
    FROM %s pc
             JOIN %s a ON pc.id = a.process_config_id
            JOIN %s c ON c.node_id = pc.process_category_node_id
   WHERE a.life_cycle_phase != \'op\'
     AND a.process_db_id = :processDbId
    GROUP BY pc.id
            , pc.name
            , c.ref_num
            , c.name
    ) x WHERE ',
            ElcaProcessConfig::TABLE_NAME,
            ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS,
            ElcaProcessCategory::TABLE_NAME
            );

        $initValues = [
            'processDbId' => $processDbId,
        ];

        $conditions = [];

        if (null != $lcIdentsIncluded) {
            $conditions[] = ':lcIncluded <@ life_cycle_idents';
            $initValues['lcIncluded'] = '{'.implode(',', $lcIdentsIncluded).'}';
        }

        if (null != $lcIdentsExcluded) {
            foreach ($lcIdentsExcluded as $index => $lc) {
                $conditions[] = 'NOT :lc'. $index .' <@ life_cycle_idents';
                $initValues['lc'.$index] = '{'.$lc.'}';
            }
        }

        $sql .= \implode(' AND ', $conditions);
        $sql .= ' ORDER BY process_category, name';

        return self::_findBySql(get_class(),
            $sql,
            $initValues
        );
    }
}
