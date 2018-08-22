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
use Beibob\Blibs\NestedNode;

/**
 * Handles a set of ElcaElementType
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementTypeSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_ELEMENT_TYPES = 'elca.element_types_v';


    // public


    /**
     * Finds all direct child types of the given parent type
     *
     * @param  -
     * @return ElcaElementTypeSet
     */
    public static function findNavigationByParentType(ElcaElementType $ElementType, $projectVariantId = null, $isAdmin = false, array $accessGroupIds = [], $force = false)
    {
        if(!$ElementType->isInitialized())
            return new ElcaElementTypeSet();

        $initValues = ['nodeId' => $ElementType->getNodeId()];

        if(null !== $projectVariantId)
            $initValues['projectVariantId'] = $projectVariantId;

        $permissionFilter = '';
        if(!$isAdmin && $accessGroupIds)
        {
           $permissionFilter = 'AND '. self::addPermissionFilter($initValues, $accessGroupIds);
        }

        $sql = sprintf('SELECT t.id
                             , t.root_id
                             , t.lft
                             , t.rgt
                             , t.level
                             , t.ident
                             , t.node_id
                             , t.name
                             , t.description
                             , t.din_code
                             , t.is_constructional
                             , t.is_opaque
                             , t.pref_ref_unit
                             , t.pref_inclination
                             , t.pref_has_element_image
                             , count(e.id) AS element_count
                         FROM %s t
                         JOIN %s n ON n.root_id = t.root_id AND t.lft BETWEEN n.lft AND n.rgt AND t.level = n.level + 1
                    LEFT JOIN elca.elements e ON t.id = e.element_type_node_id AND e.project_variant_id %s %s
                        WHERE n.id = :nodeId
                     GROUP BY t.id
                            , t.root_id
                            , t.lft
                            , t.rgt
                            , t.level
                            , t.ident
                            , t.node_id
                            , t.name
                            , t.description
                            , t.din_code
                            , t.is_constructional
                            , t.is_opaque
                            , t.pref_ref_unit
                            , t.pref_inclination
                            , t.pref_has_element_image
                     ORDER BY t.lft'
                       , self::VIEW_ELCA_ELEMENT_TYPES
                       , NestedNode::getTablename()
                       , is_null($projectVariantId)? 'IS NULL' : ' = :projectVariantId'
                       , $permissionFilter
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByParentType


    
    /**
     * Finds all direct child types of the given parent type
     *
     * @param  -
     * @return ElcaElementTypeSet
     */
    public static function findByParentType(ElcaElementType $ElementType, $force = false)
    {
        if(!$ElementType->isInitialized())
            return new ElcaElementTypeSet();

        $initValues = ['nodeId' => $ElementType->getNodeId()];

        $sql = sprintf('SELECT t.*
                          FROM %s n
                          JOIN %s t ON n.root_id = t.root_id AND t.lft BETWEEN n.lft AND n.rgt AND t.level = n.level + 1
                         WHERE n.id = :nodeId
                         ORDER BY t.lft'
                       , NestedNode::getTablename()
                       , self::VIEW_ELCA_ELEMENT_TYPES
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByParentType



    /**
     * Finds all element types within the parent which have elements assigned,
     * respecting the given projectVariantId (or none) and user access permissions.
     *
     * Specify $projectVariantId to filter element types which were associated to project elements of this variant
     *    leave it null, to find element types in context of template elements
     * Specify $includeSubordinates to include all subordinates instead of only the next higher level
     * Specify $activeElementId to add the element type of this element to the list
     * Specify $includeAssignedElements to ommit the composite_element_id IS NULL filter
     *
     * @param  ElcaElementType $ElementType
     * @param  int             $projectVariantId
     * @param  boolean $isAdmin            - current user has admin privileges
     * @param  int     $accessGroupIds     - the group the current user has to be member of
     * @param  boolean $includeSubordinates
     * @param  int     $activeElementId
     * @param  boolean $includeAssignedElements
     * @param  int     $compositeElementId - exclude element types containing elements which are assigned to the given composite elementId
     *
     * @param null     $publicElements
     * @param null     $limit
     * @param bool     $force
     * @return ElcaElementTypeSet
     */
    public static function findWithElementsByParentType(
        ElcaElementType $ElementType, $projectVariantId = null, $isAdmin = false, array $accessGroupIds = null,
        $includeSubordinates = false, $activeElementId = null, $includeAssignedElements = false,
        $compositeElementId = null, $publicElements = null, $referenceElements = null, $refUnit = null, array $processDbIds = null,
        array $orderBy = null, $limit = null, $force = false
    ) {
        if (!$ElementType->isInitialized()) {
            return new ElcaElementTypeSet();
        }

        $initValues = ['elementTypeNodeId' => $ElementType->getNodeId()];

        if (!is_null($projectVariantId)) {
            $initValues['projectVariantId'] = $projectVariantId;
        }

        $permissionFilter = '';
        if (!$isAdmin && $accessGroupIds) {
            $permissionFilter = 'AND (e.id IS NULL OR '.self::addPermissionFilter($initValues, $accessGroupIds).')';
        }

        $elementFilter = '';
        if ($includeAssignedElements) {
            $parts = [];
            if ($compositeElementId) {
                $parts[]                          = sprintf(
                    'e.id NOT IN (SELECT element_id
                                                   FROM %s
                                                  WHERE composite_element_id = :compositeElementId)',
                    ElcaCompositeElement::TABLE_NAME
                );
                $initValues['compositeElementId'] = $compositeElementId;

                if ($activeElementId) {
                    $parts[]                 = 'e.id = :elementId';
                    $initValues['elementId'] = $activeElementId;
                }
            }

            if ($parts) {
                $elementFilter = ' AND '.implode(' OR ', $parts);
            }
        } else {
            if ($activeElementId) {
                $elementFilter           .= ' AND (c.composite_element_id IS NULL OR e.id = :elementId)';
                $initValues['elementId'] = $activeElementId;
            } else {
                $elementFilter = 'AND c.composite_element_id IS NULL';
            }
        }

        $publicFilter = '';
        if (null !== $publicElements) {
            $publicFilter           = 'AND (e.is_public = :isPublic)';
            $initValues['isPublic'] = (bool)$publicElements;
        }

        $referenceFilter = '';
        if (null !== $referenceElements) {
            $referenceFilter           = 'AND (e.is_reference = :isReference)';
            $initValues['isReference'] = (bool)$referenceElements;
        }

        $subordinateFilter = $includeSubordinates ? 'n1.level > n2.level' : 'n1.level = n2.level + 1';

        $refUnitFilter = '';
        if ($refUnit) {
            $refUnitFilter         = ' AND (e.ref_unit = :refUnit)';
            $initValues['refUnit'] = $refUnit;
        }

        $processDbFilter = '';
        if ($processDbIds) {
            $processDbFilter            = 'WHERE :processDbIds::int[] && x.process_db_ids';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }

        $sql = sprintf(
            <<<SQL
SELECT DISTINCT t.*
FROM %s t
JOIN (
        SELECT
            e.id,
            e.element_type_node_id,
            array_intersect_agg(pc.process_db_ids) AS process_db_ids
        FROM %s e
            LEFT JOIN %s ec ON e.id = ec.element_id
            LEFT JOIN %s pc ON pc.id = ec.process_config_id
            LEFT JOIN %s c  ON e.id = c.element_id
        WHERE
         e.project_variant_id %s
            AND element_type_node_id IN (
            SELECT
                n1.id
            FROM %s n1
                JOIN %s n2
                    ON n1.root_id = n2.root_id AND n1.lft BETWEEN n2.lft AND n2.rgt AND %s
            WHERE n2.id = :elementTypeNodeId
            )
            %s %s %s %s %s
        GROUP BY e.id, e.element_type_node_id
        ) x ON x.element_type_node_id = t.node_id
        %s
SQL
            ,
            self::VIEW_ELCA_ELEMENT_TYPES,
            ElcaElement::TABLE_NAME,
            ElcaElementComponent::TABLE_NAME,
            ElcaProcessConfigSet::VIEW_PROCESS_CONFIG_PROCESS_DBS,
            ElcaCompositeElement::TABLE_NAME,
            is_null($projectVariantId) ? 'IS NULL' : ' = :projectVariantId',
            NestedNode::TABLE_NAME,
            NestedNode::TABLE_NAME,
            $subordinateFilter,
            $publicFilter,
            $referenceFilter,
            $refUnitFilter,
            $permissionFilter,
            $elementFilter,
            $processDbFilter
        );

        if (null !== $orderBy) {
            $sql .= ' '. self::buildOrderView($orderBy);
        }

        if (is_numeric($limit)) {
            $sql .= ' LIMIT '.$limit;
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }

    /**
     * Finds all element types within the parent which have elements assigned,
     * respecting the given projectVariantId (or none) and user access permissions.
     *
     * Specify $projectVariantId to filter element types which were associated to project elements of this variant
     *    leave it null, to find element types in context of template elements
     * Specify $includeSubordinates to include all subordinates instead of only the next higher level
     * Specify $activeElementId to add the element type of this element to the list
     * Specify $includeAssignedElements to ommit the composite_element_id IS NULL filter
     *
     * @param  ElcaElementType $ElementType
     * @param  int             $projectVariantId
     * @param  boolean $isAdmin            - current user has admin privileges
     * @param  int     $accessGroupIds     - the group the current user has to be member of
     * @param  boolean $includeSubordinates
     * @param  int     $activeElementId
     * @param  boolean $includeAssignedElements
     * @param  int     $compositeElementId - exclude element types containing elements which are assigned to the given composite elementId
     *
     * @param null     $publicElements
     * @param null     $limit
     * @param bool     $force
     * @return ElcaElementTypeSet
     */
    public static function old_findWithElementsByParentType(ElcaElementType $ElementType, $projectVariantId = null, $isAdmin = false, array $accessGroupIds = null, $includeSubordinates = false, $activeElementId = null, $includeAssignedElements = false, $compositeElementId = null, $publicElements = null, $refUnit = null, array $processDbIds = null, $limit = null, $force = false)
    {
        if(!$ElementType->isInitialized())
            return new ElcaElementTypeSet();

        $elementTableName = ElcaElement::TABLE_NAME;

        $initValues = ['nodeId' => $ElementType->getNodeId()];

        if(!is_null($projectVariantId))
            $initValues['projectVariantId'] = $projectVariantId;

        $permissionFilter = '';
        if(!$isAdmin && $accessGroupIds)
        {
            $permissionFilter = 'AND (e.id IS NULL OR '. self::addPermissionFilter($initValues, $accessGroupIds).')';
        }

        $elementFilter = '';
        if($includeAssignedElements)
        {
            $parts = [];
            if($compositeElementId)
            {
                $parts[] = sprintf('e.id NOT IN (SELECT element_id
                                                   FROM %s
                                                  WHERE composite_element_id = :compositeElementId)',
                    ElcaCompositeElement::TABLE_NAME);
                $initValues['compositeElementId'] = $compositeElementId;

                if($activeElementId)
                {
                    $parts[] = 'e.id = :elementId';
                    $initValues['elementId'] = $activeElementId;
                }
            }

            if($parts)
                $elementFilter = ' AND '. join(' OR ', $parts);
        }
        else
        {
            if($activeElementId)
            {
                $elementFilter .= ' AND (c.composite_element_id IS NULL OR e.id = :elementId)';
                $initValues['elementId'] = $activeElementId;
            }
            else
                $elementFilter = 'AND c.composite_element_id IS NULL';
        }

        $publicFilter = '';
        if(null !== $publicElements)
        {
            $publicFilter = 'AND (e.is_public = :isPublic)';
            $initValues['isPublic'] = (bool)$publicElements;
        }


        if($includeSubordinates)
            $subordinateFilter = 't.level > n.level';
        else
            $subordinateFilter = 't.level = n.level + 1';


        $refUnitFilter = '';
        if ($refUnit) {
            $refUnitFilter = ' AND (e.ref_unit = :refUnit)';
            $initValues['refUnit'] = $refUnit;
        }

        $processDbFilter = '';
        if ($processDbIds) {
            $elementTableName = ElcaElementSet::VIEW_ELEMENT_EXTENDED_SEARCH;
            $processDbFilter = ' AND :processDbIds::int[] && e.process_db_ids';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }

        $sql              = sprintf('SELECT DISTINCT t.*
                          FROM %s n
                          JOIN %s t ON n.root_id = t.root_id AND t.lft BETWEEN n.lft AND n.rgt AND %s
                          JOIN %s e ON t.node_id = e.element_type_node_id
                     LEFT JOIN %s c  ON e.id = c.element_id
                         WHERE n.id = :nodeId
                           AND e.project_variant_id %s
                           %s
                           %s %s %s %s
                         ORDER BY t.lft'
            , NestedNode::getTablename()
            , self::VIEW_ELCA_ELEMENT_TYPES
            , $subordinateFilter
            , $elementTableName
            , ElcaCompositeElement::TABLE_NAME
            , is_null($projectVariantId)? 'IS NULL' : ' = :projectVariantId'
            , $permissionFilter
            , $elementFilter
            , $publicFilter
            , $refUnitFilter
            , $processDbFilter
        );

        if(is_numeric($limit))
            $sql .= ' LIMIT '.$limit;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }


    /**
     * Finds all parents of the given elementTypeId
     *
     * @param  -
     * @return ElcaElementTypeSet
     */
    public static function findParentsById($elementTypeId, $includeRoot = false, $force = false)
    {
        if(!$elementTypeId)
            return new ElcaElementTypeSet();

        $sql = sprintf("SELECT t.*
                          FROM %s n
                          JOIN %s t ON n.root_id = t.root_id AND n.lft BETWEEN t.lft AND t.rgt AND t.level <= n.level
                         WHERE n.id = :nodeId %s
                         ORDER BY t.lft"
                       , NestedNode::getTablename()
                       , self::VIEW_ELCA_ELEMENT_TYPES
                       , !$includeRoot? 'AND t.id <> t.root_id' : ''
                       );

        return self::_findBySql(get_class(), $sql, ['nodeId' => $elementTypeId], $force);
    }
    // End findParentsById



    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementTypeSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaElementType::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaElementType::getTablename(), $initValues, $force);
    }
    // End dbCount

    /**
     * @param array $initValues
     * @param array $accessGroupIds
     * @return string
     */
    private static function addPermissionFilter(array &$initValues, array $accessGroupIds)
    {
        $accessGroupPlaceholders = [];
        foreach ($accessGroupIds as $index => $accessGroupId) {
            $accessGroupPlaceholders[]    = $placeholderName = ':accessGroupId_'.($index + 1);
            $initValues[$placeholderName] = $accessGroupId;
        }
        $permissionFilter = sprintf(
            '(e.is_public OR e.access_group_id IN (%s))',
            implode(', ', $accessGroupPlaceholders)
        );

        return $permissionFilter;
    }
}
// End class ElcaElementTypeSet