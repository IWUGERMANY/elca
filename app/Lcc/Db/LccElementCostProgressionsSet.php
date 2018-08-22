<?php
/**
 * This file is part of the elca project
 *
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *                    Fabian Möller <fab@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * elca is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * elca is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with elca. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Lcc\Db;

use Beibob\Blibs\DataObjectSet;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;

class LccElementCostProgressionsSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_LCC_ELEMENT_COMPONENT_COST_PROGRESSIONS = 'lcc.element_component_cost_progressions_v';
    const TABLE_LCC_ELEMENT_COMPONENT_COST_PROGRESSIONS = 'lcc.element_component_cost_progressions';
    const TABLE_LCC_ELEMENT_COST_PROGRESSIONS = 'lcc.element_cost_progressions';

    const VIEW_LCC_COMPOSITE_ELEMENT_COST_PROGRESSIONS = 'lcc.composite_element_cost_progressions_v';

    /**
     * @param $elementId
     *
     * @return DataObjectSet|LccElementCostProgressionsSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function findByElementId($elementId)
    {
        if(!$elementId)
            return new LccElementCostProgressionsSet();

        $sql = sprintf('(SELECT \'ec_\' || element_component_id AS key
        , life_time
        , quantity
        , process_config_name
        , is_layer
        , layer_position
                          FROM %s
                         WHERE element_id = :elementId
                      )
                      UNION
                      (SELECT \'e_\' || element_id AS key
        , life_time
        , quantity
        , \'\' AS process_config_name
        , false AS is_layer
        , 1 AS layer_position
        FROM %s WHERE element_id = :elementId)
        ORDER BY is_layer DESC, layer_position ASC NULLS FIRST, life_time ASC'
            , self::VIEW_LCC_ELEMENT_COMPONENT_COST_PROGRESSIONS
            , self::TABLE_LCC_ELEMENT_COST_PROGRESSIONS
        );

        $ret =  self::_findBySql(get_class(),
            $sql,
            array('elementId' => $elementId)
        );

        return $ret;
    }
    // End findByElementId

    /**
     * @param $elementId
     *
     * @return DataObjectSet|LccElementCostProgressionsSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function findByCompositeElementId($elementId)
    {
        if(!$elementId)
            return new LccElementCostProgressionsSet();

        $sql = sprintf('SELECT \'e_\' || element_id AS key
        , life_time
        , quantity
        , name AS process_config_name
        , false AS is_layer
        , position AS layer_position
                          FROM %s
                         WHERE composite_element_id = :elementId
        ORDER BY position, life_time'
            , self::VIEW_LCC_COMPOSITE_ELEMENT_COST_PROGRESSIONS
        );

        $ret =  self::_findBySql(get_class(),
            $sql,
            array('elementId' => $elementId)
        );

        return $ret;
    }
    // End findByElementId


    /**
     *
     */
    public static function findByElementComponentId($elementComponentId)
    {
        if(!$elementComponentId)
            return new LccElementCostProgressionsSet();

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE element_component_id = :elementComponentId
                      ORDER BY life_time ASC'
            , self::TABLE_LCC_ELEMENT_COMPONENT_COST_PROGRESSIONS
        );

        return self::_findBySql(get_class(),
            $sql,
            array('elementComponentId' => $elementComponentId)
        );
    }
    // End findByElementComponentId


    /**
     *
     */
    public static function findProductionCostsByCompositeElementId($compositeElementId)
    {
        $sql = sprintf('SELECT ce.composite_element_id
                             , c.element_id
                             , sum(c.quantity) AS quantity
                          FROM %s c
                          JOIN %s ce ON ce.element_id = c.element_id
                         WHERE composite_element_id = :compositeElementId
                           AND c.life_time = 0
                      GROUP BY ce.composite_element_id
                             , c.element_id',
            self::VIEW_LCC_ELEMENT_COMPONENT_COST_PROGRESSIONS,
            ElcaCompositeElement::TABLE_NAME
        );

        return self::_findBySql(get_class(), $sql, ['compositeElementId' => $compositeElementId]);
    }

}
// End LccElementCostProgressionsSet