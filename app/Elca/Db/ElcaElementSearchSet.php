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
use Beibob\Blibs\NestedNode;

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
 * Builds the element search sql view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementSearchSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_ELEMENT_SEARCH = 'elca.element_search_v';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a list of matching elements
     *
     * @param  array   $keywords
     * @param  int     $compositeElementTypeNodeId
     * @param  int     $projectVariantId
     * @param  boolean $isAdmin - current user has admin privileges
     * @param  int     $accessGroupId - the group the current user has to be member of
     * @param  int     $compositeElementId - exclude elements which are assigned to the given composite elementId
     * ElcaProcessConfigSearchSet
     */
    public static function findByKeywordsAndCompositeElementTypeNodeId(array $keywords, $compositeElementTypeNodeId, $projectVariantId = null, $isAdmin = false, $accessGroupId = null, $includeAssignedElements = false, $compositeElementId = null, $publicElements = null, $referenceElements = null, $refUnit = null, array $processDbIds = null, $force = false)
    {
        if (!$compositeElementTypeNodeId) {
            return new ElcaElementSearchSet();
        }

        $initValues = array('elementTypeNodeId' => $compositeElementTypeNodeId);

        if (!$conditions = self::getSearchConditions($keywords, 'name', $initValues)) {
            return new ElcaElementSearchSet();
        }

        if (null !== $projectVariantId) {
            $initValues['projectVariantId'] = $projectVariantId;
        }

        $permissionFilter = '';
        if (!$isAdmin && $accessGroupId) {
            $permissionFilter = 'AND (e.is_public OR e.access_group_id = :accessGroupId)';
            $initValues['accessGroupId'] = $accessGroupId;
        }

        $elementFilter = '';
        if ($includeAssignedElements) {
            if ($compositeElementId) {
                $elementFilter = sprintf(' AND e.id NOT IN (SELECT element_id
                                                            FROM %s
                                                           WHERE composite_element_id = :elementId)',
                                         ElcaCompositeElement::TABLE_NAME);
                $initValues['elementId'] = $compositeElementId;
            }
        }
        else {
            $elementFilter = 'AND c.composite_element_id IS NULL';
        }

        $isPublicFilter = '';
        if (null !== $publicElements) {
            $isPublicFilter = 'AND (e.is_public = :isPublic)';
            $initValues['isPublic'] = (bool)$publicElements;
        }

        $isReferenceFilter = '';
        if (null !== $referenceElements) {
            $isReferenceFilter           = 'AND (e.is_reference = :isReference)';
            $initValues['isReference'] = (bool)$referenceElements;
        }


        $refUnitFilter = '';
        if ($refUnit) {
            $refUnitFilter = ' AND (e.ref_unit = :refUnit)';
            $initValues['refUnit'] = $refUnit;
        }

        $processDbFilter = '';
        if ($processDbIds) {
            $processDbFilter = ' AND :processDbIds::int[] && e.process_db_ids';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }


        $sql = sprintf("SELECT DISTINCT e.*
                          FROM %s e
                          JOIN %s n  ON n.id = e.element_type_node_id
                          JOIN %s nn ON n.lft BETWEEN nn.lft AND nn.rgt AND n.root_id = nn.root_id AND n.level = nn.level + 1
                     LEFT JOIN %s c  ON e.id = c.element_id
                         WHERE nn.id = :elementTypeNodeId
                           AND e.project_variant_id %s
                           %s
                           %s %s %s %s %s
                           AND %s
                      ORDER BY e.element_type_node_name
                             , e.name"
                       , self::VIEW_ELEMENT_SEARCH
                       , NestedNode::TABLE_NAME
                       , NestedNode::TABLE_NAME
                       , ElcaCompositeElement::TABLE_NAME
                       , null === $projectVariantId ? 'IS NULL' : ' = :projectVariantId'
                       , $permissionFilter
                       , $elementFilter
                       , $isPublicFilter
                       , $isReferenceFilter
                       , $refUnitFilter
                       , $processDbFilter
                       , $conditions
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }


    public static function findByKeywordsAndElementTypeNodeId(array $keywords, $elementTypeNodeId, $projectVariantId = null, $isAdmin = false, $accessGroupId = null, $includeAssignedElements = false, $compositeElementId = null, $referenceElements = null, $publicElements = null, $refUnit = null, array $processDbIds = null, $force = false)
    {
        if (!$elementTypeNodeId) {
            return new ElcaElementSearchSet();
        }

        $initValues = ['elementTypeNodeId' => $elementTypeNodeId];

        if (!$conditions = self::getSearchConditions($keywords, 'name', $initValues)) {
            return new ElcaElementSearchSet();
        }

        if (null !== $projectVariantId) {
            $initValues['projectVariantId'] = $projectVariantId;
        }

        $permissionFilter = '';
        if (!$isAdmin && $accessGroupId) {
            $permissionFilter = 'AND (e.is_reference OR e.access_group_id = :accessGroupId)';
            $initValues['accessGroupId'] = $accessGroupId;
        }

        $elementFilter = '';
        if ($includeAssignedElements) {
            if ($compositeElementId) {
                $elementFilter = sprintf(' AND e.id NOT IN (SELECT element_id
                                                            FROM %s
                                                           WHERE composite_element_id = :elementId)',
                    ElcaCompositeElement::TABLE_NAME);
                $initValues['elementId'] = $compositeElementId;
            }
        }
        else {
            $elementFilter = 'AND c.composite_element_id IS NULL';
        }

        $publicFilter = '';
        if (null !== $publicElements) {
            $publicFilter = 'AND (e.is_public = :isPublic)';
            $initValues['isPublic'] = (bool)$publicElements;
        }

        $referenceFilter = '';
        if (null !== $referenceElements) {
            $referenceFilter = 'AND (e.is_reference = :isReference)';
            $initValues['isReference'] = (bool)$referenceElements;
        }

        $refUnitFilter = '';
        if ($refUnit) {
            $refUnitFilter = ' AND (e.ref_unit = :refUnit)';
            $initValues['refUnit'] = $refUnit;
        }

        $processDbFilter = '';
        if ($processDbIds) {
            $processDbFilter = ' AND :processDbIds::int[] && e.process_db_ids';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }


        $sql = sprintf("SELECT DISTINCT e.*
                          FROM %s e
                     LEFT JOIN %s c  ON e.id = c.element_id
                         WHERE e.element_type_node_id = :elementTypeNodeId
                           AND e.project_variant_id %s
                           %s
                           %s %s %s %s %s
                           AND %s
                      ORDER BY e.element_type_node_name
                             , e.name"
            , self::VIEW_ELEMENT_SEARCH
            , ElcaCompositeElement::TABLE_NAME
            , null === $projectVariantId ? 'IS NULL' : ' = :projectVariantId'
            , $permissionFilter
            , $elementFilter
            , $publicFilter
            , $referenceFilter
            , $refUnitFilter
            , $processDbFilter
            , $conditions
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }

    /**
     * Returns the search conditions
     *
     * @param  SearchQuery $Query
     * @param  string      $searchField
     * @return string
     */
    private static function getSearchConditions(array $keywords, $searchField, array &$initValues)
    {
        $lftBoundary = $rgtBoundary = '%';

        $conditions = false;
        $queries = array();
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
// End ElcaElementSearchSet
