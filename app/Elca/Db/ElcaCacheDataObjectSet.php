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
 * Builds report views
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaCacheDataObjectSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_PROJECT_VARIANT_PROCESS_CONFIG_MASS = 'elca_cache.project_variant_process_config_mass_v';


    /**
     * Returns a list of aggregated masses from process configs grouped by project variant
     *
     * @param  int  $projectVariantId
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @return ElcaReportSet
     */
    public static function findProcessConfigMassByProjectVariantId($projectVariantId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $initValues = array('project_variant_id' => $projectVariantId);

        return self::_find(get_class(),
                           self::VIEW_PROJECT_VARIANT_PROCESS_CONFIG_MASS,
                           $initValues,
                           $orderBy, $limit, $offset, $force);
    }
    // End findProcessConfigMassByProjectVariantId
}
// End ElcaCacheDataObjectSet