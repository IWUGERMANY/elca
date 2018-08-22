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
 * Set of ElcaProjectElementsSanity
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaProjectProcessConfigSanitySet extends DataObjectSet
{
    const PROJECT_PROCESS_CONFIG_SANITIES = 'elca.project_process_config_sanities';

    const CONTEXT_ELEMENTS = 'elements';
    const CONTEXT_FINAL_ENERGY_DEMANDS = 'final_energy_demands';
    const CONTEXT_FINAL_ENERGY_SUPPLIES = 'final_energy_supplies';

    /**
     * Find all pro
     *
     * @param  ElcaProjectVariant $ProjectVariant
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProjectProcessConfigSanitySet
     */
    public static function findByProjectVariant(ElcaProjectVariant $ProjectVariant, $context = null, $force = false)
    {
        $initValues = array(
            'projectVariantId' => $ProjectVariant->getId(),
            'processDbId' => $ProjectVariant->getProject()->getProcessDbId()
        );

        $sql = sprintf('SELECT * FROM %s(:projectVariantId::int, :processDbId::int) x'
            , self::PROJECT_PROCESS_CONFIG_SANITIES
        );

        if ($context !== null) {
            $sql .= ' WHERE context = :context';
            $initValues['context'] = $context;
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProjectVariantId
}
// Ebd ElcaProjectProcessConfigSanitySet