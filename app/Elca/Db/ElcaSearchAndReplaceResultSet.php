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
 * Builds report views
 *
 * @package elca
 * @author  Fabian Möller <fabian@beibob.de>
 */
class ElcaSearchAndReplaceResultSet extends DataObjectSet
{
    /**
     * @param      $projectVariantId
     * @param      $processConfigId
     * @param bool $force
     *
     * @return DataObjectSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function findByProjectVariantIdAndProcessConfigId($projectVariantId, $processConfigId, $force = false)
    {
        $sql = sprintf("WITH elca_elements AS (
                        SELECT e.id
                             , e.element_type_node_id
                             , e.access_group_id
                             , e.project_variant_id
                             , e.name
                             , e.quantity AS element_quantity
                             , e.ref_unit AS element_ref_unit
                             , array_agg(DISTINCT c.process_config_id) AS process_config_ids
                        FROM %s e
                        JOIN %s c ON e.id = c.element_id
                        GROUP BY e.id
                            , e.element_type_node_id
                            , e.access_group_id
                            , e.project_variant_id
                            , e.name
                            , e.quantity
                            , e.ref_unit
                    )
                    SELECT e.*
                         , c.*
                         , et.name AS element_type_name
                         , et.din_code
                         , p.in_unit AS component_unit
                    FROM elca_elements e
                    JOIN elca.element_components c ON e.id = c.element_id
                    JOIN elca.element_types et ON et.node_id = e.element_type_node_id
                    JOIN elca.process_conversions p ON p.id = c.process_conversion_id
                    WHERE e.project_variant_id = :projectVariantId
                    AND :processConfigId = ANY(e.process_config_ids) ORDER BY e.element_type_node_id ASC, e.id ASC, c.layer_position ASC"
                    , ElcaElement::TABLE_NAME
                    , ElcaElementComponent::TABLE_NAME
        );

        $initValues = ['projectVariantId' => $projectVariantId, 'processConfigId' => $processConfigId];

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
}