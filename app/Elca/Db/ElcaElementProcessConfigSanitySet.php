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
 * ElcaElementProcessConfigSanitySet
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaElementProcessConfigSanitySet extends DataObjectSet
{
    const VIEW_ELEMENT_PROCESS_CONFIG_SANITIES = 'elca.element_process_config_sanities_v';

    /**
     * Find all pro
     *
     * @param null     $accessGroupId
     * @param  boolean $force - Bypass caching
     * @return ElcaProjectProcessConfigSanitySet
     */
    public static function findByAccessGroupId($accessGroupId = null, $force = false)
    {
        $initValues = [];

        $sql = sprintf('SELECT * FROM %s', self::VIEW_ELEMENT_PROCESS_CONFIG_SANITIES);

        if ($accessGroupId !== null) {
            $initValues['accessGroupId'] = $accessGroupId;

            $sql .= ' WHERE access_group_id = :accessGroupId';
        }

        $sql .= ' ORDER BY din_code, element_id, layer_position';

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
}
