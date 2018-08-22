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
 * Handles a set of ElcaElementComponent
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementComponentSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_ELEMENT_COMPONENTS = 'elca.element_components_v';
    const VIEW_ELCA_ELEMENT_LAYERS = 'elca.element_layers_v';
    const VIEW_ELCA_ELEMENT_SINGLE_COMPONENTS = 'elca.element_single_components_v';


    // public


    /**
     * Find by elementId
     *
     * @param  integer  $elementId
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementComponentSet
     */
    public static function findByElementId($elementId, array $initValues = array(), array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$elementId)
            return new ElcaElementComponentSet();

        $initValues['element_id'] = $elementId;
        return self::_find(get_class(), ElcaElementComponent::TABLE_NAME, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByElementId


    /**
     * Find by elementId
     *
     * @param  integer $elementId
     * @param          $attributeIdent
     * @param  array   $initValues - key value array
     * @param array    $orderBy
     * @param null     $limit
     * @param null     $offset
     * @param  boolean $force      - Bypass caching
     * @return ElcaElementComponentSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function findByElementIdAndAttributeIdent($elementId, $attributeIdent, array $initValues = array(), array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$elementId)
            return new ElcaElementComponentSet();

        $initValues['element_id'] = $elementId;
        $initValues['ident'] = $attributeIdent;

        $sql = sprintf('SELECT c.*
                          FROM %s c
                          JOIN %s a ON c.id = a.element_component_id'
            , ElcaElementComponent::TABLE_NAME
            , ElcaElementComponentAttribute::TABLE_NAME
        );

        $sql .= ' WHERE '. self::buildConditions($initValues);

        if ($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByElementId

    /**
     * Find by processConversionId
     *
     * @param          $conversionId
     * @param  array   $initValues - key value array
     * @param array    $orderBy
     * @param null     $limit
     * @param null     $offset
     * @param  boolean $force      - Bypass caching
     * @return ElcaElementComponentSet
     */
    public static function findByProcessConversionId($conversionId, array $initValues = array(), array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$conversionId)
            return new ElcaElementComponentSet();

        $initValues['process_conversion_id'] = $conversionId;

        return self::_find(get_class(), self::VIEW_ELCA_ELEMENT_COMPONENTS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByProcessConversionId



    /**
     * Find layer components
     *
     * @param  integer  $elementId
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementComponentSet
     */
    public static function findLayers($elementId, array $initValues = array(), $force = false)
    {
        if(!$elementId)
            return new ElcaElementComponentSet();

        $initValues['element_id'] = $elementId;

        return self::_find(get_class(), self::VIEW_ELCA_ELEMENT_LAYERS, $initValues, array('layer_position' => 'ASC', 'id' => 'ASC'), null, null, $force);
    }
    // End findLayers



    /**
     * Find single components
     *
     * @param  integer  $elementId
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementComponentSet
     */
    public static function findSingleComponents($elementId, array $initValues = array(), $force = false)
    {
        if(!$elementId)
            return new ElcaElementComponentSet();

        $initValues['element_id'] = $elementId;

        return self::_find(get_class(), self::VIEW_ELCA_ELEMENT_SINGLE_COMPONENTS, $initValues, array('id' => 'ASC'), null, null, $force);
    }
    // End findSingleComponents



    /**
     * Find template element components by processConfigId
     *
     * @param  integer  $processConfigId
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementComponentSet
     */
    public static function findTemplatesByProcessConfigId($processConfigId, $force = false)
    {
        if(!$processConfigId)
            return new ElcaElementComponentSet();

        $initValues = array('processConfigId' => $processConfigId);

        $sql = sprintf('SELECT DISTINCT c.*
                          FROM %s c
                          JOIN %s e ON e.id = c.element_id
                         WHERE c.process_config_id = :processConfigId
                           AND e.is_public = true
                           AND e.project_variant_id IS NULL'
                       , ElcaElementComponent::TABLE_NAME
                       , ElcaElement::TABLE_NAME
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findTemplatesByProcessConfigId


    /**
     * Finds all element components by project variant id
     *
     * @param  integer $projectVariantId
     * @param array    $initValues
     * @param array    $orderBy
     * @param null     $limit
     * @param null     $offset
     * @param  boolean $force - Bypass caching
     * @throws Exception
     * @return ElcaElementComponentSet
     */
    public static function findByProjectVariantId($projectVariantId, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$projectVariantId)
            return new ElcaElementComponentSet();

        $initValues = !is_null($initValues)? $initValues : array();
        $initValues['project_variant_id'] = $projectVariantId;

        $sql = sprintf('SELECT c.*
                          FROM %s c
                          JOIN %s e ON e.id = c.element_id
                         WHERE %s'
            , ElcaElementComponent::TABLE_NAME
            , ElcaElement::TABLE_NAME
            , self::buildConditions($initValues)
        );

        if ($orderBySql = self::buildOrderView($orderBy, $limit, $offset)) {
            $sql .= ' '. $orderBySql;
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProjectVariantId




    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementComponentSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaElementComponent::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find



    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaElementComponent::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaElementComponentSet