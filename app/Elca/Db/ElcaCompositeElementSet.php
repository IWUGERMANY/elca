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
 * Handles a set of ElcaCompositeElement
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaCompositeElementSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_COMPOSITE_ELEMENTS = 'elca.composite_elements_v';

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all by composite element id
     *
     * @param  int      $compositeElementId
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaCompositeElementSet
     */
    public static function findByCompositeElementId($compositeElementId, array $initValues = array(), array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$compositeElementId)
            return new ElcaElementSet();

        $initValues['composite_element_id'] = $compositeElementId;

        if(is_null($orderBy))
            $orderBy = array('position' => 'ASC');

        return self::_find(get_class(), self::VIEW_ELCA_COMPOSITE_ELEMENTS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByCompositeElementId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all non opaque elements by composite element id
     *
     * @param  int      $compositeElementId
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaCompositeElementSet
     */
    public static function findNonOpaqueByCompositeElementId($compositeElementId, array $initValues = array(), array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$compositeElementId)
            return new ElcaElementSet();

        $initValues['composite_element_id'] = $compositeElementId;
        $initValues['is_opaque'] = false;

        if(is_null($orderBy))
            $orderBy = array('position' => 'ASC');

        $sql = sprintf('SELECT c.*
                          FROM %s c
                          JOIN %s e ON e.id = c.element_id
                          JOIN %s t ON t.node_id = e.element_type_node_id'
                       , ElcaCompositeElement::TABLE_NAME
                       , ElcaElement::TABLE_NAME
                       , ElcaElementType::TABLE_NAME
                       );

        if($conditions = self::buildConditions($initValues))
            $sql .= ' WHERE '. $conditions;

        if($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findNonOpaqueByCompositeElementId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all composite elements of the given elementId
     * (only template elements may have more than one composite element)
     *
     * @param  int             $elementId
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaCompositeElementSet
     */
    public static function findByElementId($elementId, array $initValues = array(), array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $initValues['element_id'] = $elementId;
        return self::_find(get_class(), self::VIEW_ELCA_COMPOSITE_ELEMENTS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findCompositeElementsByElementId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaCompositeElementSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaCompositeElement::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaCompositeElement::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaCompositeElementSet