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

use Beibob\Blibs\DbObject;
use Beibob\Blibs\DbObjectSet;
use Beibob\Blibs\Log;
use Elca\Service\ElcaElementImageCache;

/**
 * Handles a set of ElcaElement
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELEMENT_EXTENDED_SEARCH = 'elca.element_extended_search_v';
    const VIEW_COMPOSITE_ELEMENT_EXTENDED_SEARCH = 'elca.composite_element_extended_search_v';


    // public


    /**
     * Find all last modified elements
     *
     * @param  int      $projectVariantId
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProcessConfigSet
     */
    public static function findLastModified($projectVariantId = null, $ownerId = null, array $orderBy = null, $limit = 20, $force = false)
    {
        $initValues = array();

        if(is_null($orderBy))
            $orderBy = array('coalesce(modified, created)' => 'DESC');

        $sql = sprintf('SELECT *
                          FROM %s'
                       , ElcaElement::TABLE_NAME);

        if($projectVariantId)
        {
            $initValues['projectVariantId'] = $projectVariantId;
            $sql .= ' WHERE project_variant_id = :projectVariantId';
        }
        else
            $sql .= ' WHERE project_variant_id IS NULL';

        if($ownerId)
        {
            $initValues['ownerId'] = $ownerId;
            $sql .= ' AND (is_public OR owner_id = :ownerId)';
        }

        if($orderSql = self::buildOrderView($orderBy, $limit))
            $sql .= ' '.$orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findLastModified



    /**
     * Find extended by construction catalog and design ids
     *
     * @param  array   $initValues - key value array
     * @param bool     $isAdmin
     * @param null     $accessGroupIds
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     * @return ElcaElementSet
     */
    public static function findExtended(array $initValues = null, $isAdmin = false, array $accessGroupIds = [], array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $sql = sprintf('SELECT DISTINCT e.*
                          FROM %s e
                     LEFT JOIN %s c ON e.id = c.element_id
                     LEFT JOIN %s d ON e.id = d.element_id'
                       , ElcaElement::TABLE_NAME
                       , ElcaElementConstrCatalog::TABLE_NAME
                       , ElcaElementConstrDesign::TABLE_NAME
                       );

        $conditions = array();

        if($filter = self::buildConditions($initValues))
            $conditions[] = $filter;

        if(!$isAdmin && $accessGroupIds) {
            $conditions[] = self::addPermissionFilter($initValues, $accessGroupIds);
        }

        if($conditions)
            $sql .= ' WHERE '. implode(' AND ', $conditions);

        if($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findExtended



    /**
     * Find extended for search terms and construction catalog and design ids
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementSet
     */
    public static function searchExtended(array $keywords, array $initValues = null, $isCompositeLevel = false, $isAdmin = false, array $accessGroupIds = [], $processDbId = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $rankSql = '';
        $conditions = array();

        if($filter = self::buildConditions($initValues))
            $conditions[] = $filter;

        if(!$isAdmin && $accessGroupIds)
        {
            $conditions[] = self::addPermissionFilter($initValues, $accessGroupIds);
        }

        $searchTerms = array();
        foreach ($keywords as $keyword) {
            $keyword = \trim($keyword);

            if ($keyword == '\'')
                continue;

            $keyword = str_replace('\'', '\'\'', $keyword);
            $keyword = str_replace('\\', '\\\\', $keyword);
            $keyword = str_replace('(', '\(', $keyword);
            $keyword = str_replace(')', '\)', $keyword);
            $keyword = str_replace('|', '\|', $keyword);

            if ($keyword == '')
                continue;

            $searchTerms[] = '\'' . $keyword.'\':*';
        }


        if(count($searchTerms))
        {
            $initValues['searchQuery'] = join(' & ', $searchTerms);
            $conditions[] = 'search_vector @@ to_tsquery(\'german\', :searchQuery)';

            $arr = array_reverse($orderBy, true);
            $arr['rank'] = 'DESC';
            $orderBy = array_reverse($arr, true);
            $rankSql = 'ts_rank_cd(search_vector, to_tsquery(\'german\', :searchQuery)) AS rank, ';
        }

        if (null !== $processDbId) {
            $conditions[] = '(:processDbIds = ANY (e.process_db_ids))';
            $initValues['processDbIds'] = $processDbId;
        }

        $sql = sprintf('SELECT DISTINCT %s
                                        e.id
                                      , e.element_type_node_id
                                      , e.name
                                      , e.description
                                      , e.is_reference
                                      , e.is_public
                                      , e.access_group_id
                                      , e.project_variant_id
                                      , e.quantity
                                      , e.ref_unit
                                      , e.copy_of_element_id
                                      , e.owner_id
                                      , e.is_composite
                                      , e.uuid
                                      , e.created
                                      , e.modified
                                      , e.process_db_ids
                          FROM %s e
                     LEFT JOIN %s c ON e.id = c.element_id
                     LEFT JOIN %s d ON e.id = d.element_id'
                       , $rankSql
                       , $isCompositeLevel? self::VIEW_COMPOSITE_ELEMENT_EXTENDED_SEARCH : self::VIEW_ELEMENT_EXTENDED_SEARCH
                       , ElcaElementConstrCatalog::TABLE_NAME
                       , ElcaElementConstrDesign::TABLE_NAME
                       );

        if($conditions)
            $sql .= ' WHERE '. implode(' AND ', $conditions);

        if($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End searchExtended



    /**
     * Find elements by processConfigId
     *
     * @param  int     $processConfigId
     * @param null     $processDbId
     * @param  boolean $force - Bypass caching
     * @return ElcaElementSet
     */
    public static function findProjectElementsByProcessConfigId($processConfigId, $processDbId = null, $force = false)
    {
        if(!$processConfigId)
            return new ElcaElementSet();

        if(is_null($processDbId))
        {
            $initValues = array('processConfigId' => $processConfigId);
            $sql = sprintf('SELECT DISTINCT e.*
                              FROM %s e
                              JOIN %s c ON e.id = c.element_id
                             WHERE c.process_config_id = :processConfigId
                               AND c.calc_lca = true
                               AND e.project_variant_id IS NOT NULL'
                           , ElcaElement::TABLE_NAME
                           , ElcaElementComponent::TABLE_NAME
                           );
        }
        else
        {
            $initValues = array('processConfigId' => $processConfigId,
                                'processDbId' => $processDbId);

            $sql = sprintf('SELECT DISTINCT e.*
                              FROM %s e
                              JOIN %s c ON e.id = c.element_id
                              JOIN %s v ON v.id = e.project_variant_id
                              JOIN %s p ON p.id = v.project_id
                             WHERE c.process_config_id = :processConfigId
                               AND c.calc_lca = true
                               AND e.project_variant_id IS NOT NULL
                               AND p.process_db_id = :processDbId'
                           , ElcaElement::TABLE_NAME
                           , ElcaElementComponent::TABLE_NAME
                           , ElcaProjectVariant::TABLE_NAME
                           , ElcaProject::TABLE_NAME
                          );
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessConfigId

    /**
     * Find elements by processConfigId
     *
     * @param  int     $processConfigId
     * @param  boolean $force - Bypass caching
     * @return ElcaElementSet
     */
    public static function findByProcessConfigId($processConfigId, array $initValues = array(), $force = false)
    {
        if(!$processConfigId)
            return new ElcaElementSet();

            $initValues['process_config_id'] = $processConfigId;
            $sql = sprintf('SELECT DISTINCT e.*
                              FROM %s e
                              JOIN %s c ON e.id = c.element_id
                             WHERE %s'
                , ElcaElement::TABLE_NAME
                , ElcaElementComponent::TABLE_NAME
                , self::buildConditions($initValues)
            );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessConfigId


    /**
     * Find all project elements
     *
     * @return ElcaElementSet
     */
    public static function findProjectElements()
    {
        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE project_variant_id IS NOT NULL'
                           , ElcaElement::TABLE_NAME
                       );

        return self::_findBySql(get_class(), $sql);
    }
    // End findProjectElements


    /**
     * Find all project elements by variant
     *
     * @param  int  $projectVariantId
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @return ElcaElementSet
     */
    public static function findByProjectVariantId($projectVariantId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$projectVariantId)
            return new ElcaElementSet();

        $sql = sprintf('SELECT *
                          FROM %s
                         WHERE project_variant_id = :projectVariantId'
                           , ElcaElement::TABLE_NAME
                       );

        if ($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderView;

        return self::_findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId), $force);
    }
    // End findByProjectVariantId



    /**
     * Find all elements which were not assigned to a composite element within the given element type node
     * respecting the given projectVariantId (or none) and user access permissions.
     *x
     * Specify $projectVariantId to filter project elements of this variant
     *    leave it null, to find template elements
     * Specify $activeElementId to add this element to the list, regardless of its composite_element_id value
     * Specify $includeAssignedElements to ommit the composite_element_id IS NULL filter
     *
     * @param          $elementTypeNodeId
     * @param  int     $projectVariantId
     * @param  boolean $isAdmin        - current user has admin privileges
     * @param  int     $accessGroupIds - the group the current user has to be member of
     * @param  int     $activeElementId
     * @param  boolean $includeAssignedElements
     *
     * @param null     $publicElements
     * @return ElcaElementSet
     */
    public static function findUnassignedByElementTypeNodeId($elementTypeNodeId, $projectVariantId = null, $isAdmin = false, array $accessGroupIds = null, $activeElementId = null, $includeAssignedElements = false, $publicElements = null, $referenceElements = null, $refUnit = null, array $processDbIds = null)
    {
        if(!$elementTypeNodeId)
            return new ElcaElementSet();

        $tableName = ElcaElement::TABLE_NAME;

        $initValues = array('elementTypeNodeId' => $elementTypeNodeId);

        if(!is_null($projectVariantId))
            $initValues['projectVariantId'] = $projectVariantId;

        $permissionFilter = '';
        if(!$isAdmin && $accessGroupIds)
        {
            $permissionFilter = 'AND ' . self::addPermissionFilter($initValues, $accessGroupIds);
        }

        $elementFilter = $includeAssignedElements? '' : 'AND c.composite_element_id IS NULL';
        if(!$includeAssignedElements && $activeElementId)
        {
            $elementFilter = 'AND (c.composite_element_id IS NULL OR e.id = :elementId)';
            $initValues['elementId'] = $activeElementId;
        }

        $publicFilter = '';
        if(null !== $publicElements)
        {
            $publicFilter = 'AND (e.is_public = :isPublic)';
            $initValues['isPublic'] = (bool)$publicElements;
        }

        $referenceFilter = '';
        if (null !== $referenceElements) {
            $referenceFilter           = 'AND (e.is_reference = :isReference)';
            $initValues['isReference'] = (bool)$referenceElements;
        }

        $refUnitFilter = '';
        if ($refUnit) {
            $refUnitFilter = ' AND (e.ref_unit = :refUnit)';
            $initValues['refUnit'] = $refUnit;
        }

        $processDbFilter = '';
        if ($processDbIds) {
            $tableName = self::VIEW_ELEMENT_EXTENDED_SEARCH;
            $processDbFilter = ' AND :processDbIds::int[] && process_db_ids';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }

        $sql = sprintf('SELECT DISTINCT e.*
                          FROM %s e
                     LEFT JOIN %s c  ON e.id = c.element_id
                         WHERE e.element_type_node_id = :elementTypeNodeId
                           AND e.project_variant_id %s
                           %s
                           %s
                           %s
                           %s
                           %s
                           %s
                      ORDER BY e.name'
                       , $tableName
                       , ElcaCompositeElement::TABLE_NAME
                       , is_null($projectVariantId)? 'IS NULL' : ' = :projectVariantId'
                       , $permissionFilter
                       , $elementFilter
                       , $publicFilter
                       , $referenceFilter
                       , $refUnitFilter
                       , $processDbFilter
                       );

        return self::_findBySql(get_class(), $sql, $initValues);
    }
    // End findUnassignedByElementTypeNodeId




    /**
     * Find all composite elements within the given element type node
     * respecting the given projectVariantId (or none) and user access permissions.
     *
     * Specify $projectVariantId to filter project elements of this variant
     *    leave it null, to find template elements
     *
     * @param  int     $elementTypeNodeId
     * @param  int     $projectVariantId
     * @param  boolean $isAdmin        - current user has admin privileges
     * @param  int     $accessGroupIds - the group the current user has to be member of
     * @param  int     $elementId      - exclude composite elements which are assigned to the given elementId
     *
     * @param null     $publicElements
     * @throws Exception
     * @return ElcaElementSet
     */
    public static function findCompositesByElementTypeNodeId($elementTypeNodeId, $projectVariantId = null, $isAdmin = false, array $accessGroupIds = [], $elementId = null, $publicElements = null, $referenceElements = null, array $processDbIds = null)
    {
        if(!$elementTypeNodeId)
            return new ElcaElementSet();

        $tableName = ElcaElement::TABLE_NAME;
        $initValues = array('elementTypeNodeId' => $elementTypeNodeId);

        if (!is_null($projectVariantId))
            $initValues['projectVariantId'] = $projectVariantId;

        $permissionFilter = '';
        if(!$isAdmin && $accessGroupIds)
        {
            $permissionFilter = 'AND ' . self::addPermissionFilter($initValues, $accessGroupIds);
        }

        $elementFilter = '';
        if($elementId)
        {
            $elementFilter = sprintf(' AND id NOT IN (SELECT composite_element_id
                                                        FROM %s
                                                       WHERE element_id = :elementId)',
                                     ElcaCompositeElement::TABLE_NAME);

            $initValues['elementId'] = $elementId;
        }

        $publicFilter = '';
        if (null !== $publicElements) {
            $publicFilter = 'AND (e.is_public = :isPublic)';
            $initValues['isPublic'] = (bool)$publicElements;
        }

        $referenceFilter = '';
        if (null !== $referenceElements) {
            $referenceFilter           = 'AND (e.is_reference = :isReference)';
            $initValues['isReference'] = (bool)$referenceElements;
        }

        $processDbFilter = '';
        if ($processDbIds) {
            $tableName = self::VIEW_COMPOSITE_ELEMENT_EXTENDED_SEARCH;
            $processDbFilter = 'AND (process_db_ids IS NULL OR :processDbIds::int[] && process_db_ids)';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }

        $sql       = sprintf('SELECT DISTINCT e.*
                          FROM %s e 
                         WHERE e.element_type_node_id = :elementTypeNodeId
                           AND e.project_variant_id %s
                           AND e.is_composite = true
                            %s %s %s %s %s
                      ORDER BY e.name'
                       , $tableName
                       , null === $projectVariantId ? 'IS NULL' : ' = :projectVariantId'
                       , $permissionFilter
                       , $elementFilter
                       , $publicFilter
                       , $referenceFilter
                       , $processDbFilter
                       );

        return self::_findBySql(get_class(), $sql, $initValues);
    }
    // End findCompositesByElementTypeNodeId

    public static function findCompositesByElementTypeNodeIdHavingElementsWithSubTypeNodeId($projectVariantId, $elementTypeNodeId, $subElementTypeNodeId)
    {
        if (!$elementTypeNodeId || !$subElementTypeNodeId) {
            return new ElcaElementSet();
        }

        $sql = sprintf('SELECT DISTINCT e.*
                          FROM %s e
                          JOIN %s ce ON e.id = ce.composite_element_id 
                          JOIN %s ee ON ee.id = ce.element_id
                         WHERE (e.element_type_node_id, ee.element_type_node_id) = (:elementTypeNodeId, :subElementTypeNodeId)
                           AND e.project_variant_id = :projectVariantId
                      ORDER BY e.name'
            , ElcaElement::TABLE_NAME
            , ElcaCompositeElement::TABLE_NAME
            , ElcaElement::TABLE_NAME
        );

        return self::_findBySql(get_class(), $sql, [
            'projectVariantId' => $projectVariantId,
            'elementTypeNodeId' => $elementTypeNodeId,
            'subElementTypeNodeId' => $subElementTypeNodeId
        ]);
    }

    /**
     * Reassigns the access group id
     */
    public static function reassignAccessGroupId($oldAccessGroupId, $newAccessGroupId)
    {
        if(!$oldAccessGroupId || !$newAccessGroupId)
            return false;

        $sql = sprintf('UPDATE %s
                           SET access_group_id = :newAccessGroupId
                         WHERE access_group_id = :oldAccessGroupId'
                       , ElcaElement::TABLE_NAME
                       );

        $Stmt = DbObject::prepareStatement($sql, array('oldAccessGroupId' => $oldAccessGroupId,
                                                       'newAccessGroupId' => $newAccessGroupId));

        if(!$Stmt->execute())
            throw new \Exception(DbObject::getSqlErrorMessage($dbObjectName, $sql, $initValues));

        return true;
    }

    /**
     * Reassigns the access group id
     */
    public static function reassignAccessGroupIdForProjectId($projectId, $oldAccessGroupId, $newAccessGroupId)
    {
        if(!$projectId || !$oldAccessGroupId || !$newAccessGroupId)
            return false;

        $sql        = sprintf('UPDATE %s e
                           SET access_group_id = :newAccessGroupId
                           FROM (
                                SELECT v.id AS project_variant_id
                                  FROM %s p 
                                  JOIN %s v ON p.id = v.project_id
                                 WHERE p.id = :projectId
                                 ) v
                         WHERE e.project_variant_id = v.project_variant_id                         
                           AND access_group_id = :oldAccessGroupId'
            , ElcaElement::TABLE_NAME
            , ElcaProject::TABLE_NAME
            , ElcaProjectVariant::TABLE_NAME
        );

        $initValues = [
            'projectId'        => $projectId,
            'oldAccessGroupId' => $oldAccessGroupId,
            'newAccessGroupId' => $newAccessGroupId
        ];

        $statement = DbObject::prepareStatement($sql, $initValues);

        if(!$statement->execute())
            throw new \Exception(DbObject::getSqlErrorMessage(ElcaElement::class, $sql, $initValues));

        return true;
    }
    /**
     * Reassigns the ownerId
     */
    public static function reassignOwnerId($oldOwnerId, $newOwnerId)
    {
        if(!$oldOwnerId || !$newOwnerId)
            return false;

        $sql = sprintf('UPDATE %s
                           SET owner_id = :newOwnerId
                         WHERE owner_id = :oldOwnerId'
            , ElcaElement::TABLE_NAME
        );

        $Stmt = DbObject::prepareStatement($sql, array('oldOwnerId' => $oldOwnerId,
                                                       'newOwnerId' => $newOwnerId));

        if(!$Stmt->execute())
            throw new \Exception(DbObject::getSqlErrorMessage($dbObjectName, $sql, $initValues));

        return true;
    }
    // End reassignOwnerId



    /**
     * Deletes all indicators for the given itemId and lifeCyclePhase
     */
    public static function deleteByAccessGroupId($accessGroupId)
    {
        if(!$accessGroupId)
            return false;

        $sql = sprintf('DELETE FROM %s
                              WHERE access_group_id = :accessGroupId'
                       , ElcaElement::TABLE_NAME
                       );

        $Stmt = DbObject::prepareStatement($sql, array('accessGroupId' => $accessGroupId));

        if(!$Stmt->execute())
            throw new \Exception(DbObject::getSqlErrorMessage($dbObjectName, $sql, $initValues));

        return true;
    }
    // End deleteByAccessGroupId

    /**
     * @param            $patternId
     * @param array      $initValues
     * @param array|null $orderBy
     * @param null       $limit
     * @param null       $offset
     * @param bool|false $force
     *
     * @return DbObjectSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function clearSvgPatternCacheByPatternId($patternId)
    {
        $sql = sprintf("DELETE FROM %s WHERE ident ILIKE '%s' AND element_id IN
                        (
                          WITH elements AS (
                              SELECT e.id
                              FROM %s e JOIN %s c ON e.id = c.element_id
                                JOIN %s co ON c.process_config_id = co.id
                                JOIN %s ca ON co.process_category_node_id = ca.node_id
                              WHERE %d IN (ca.svg_pattern_id, co.svg_pattern_id)
                          )

                          SELECT id
                          FROM elements
                          UNION
                          SELECT c.composite_element_id
                          FROM %s c JOIN elements e ON c.element_id = e.id
                        )"
            , ElcaElementAttribute::TABLE_NAME
            , ElcaElementImageCache::SVG_IMAGE_CACHE_ATTRIBUTE_IDENT . '%'
            , ElcaElement::TABLE_NAME
            , ElcaElementComponent::TABLE_NAME
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessCategory::TABLE_NAME
            , $patternId
            , ElcaCompositeElement::TABLE_NAME
        );

        $Stmt = DbObject::prepareStatement($sql);

        if(!$Stmt->execute())
            throw new \Exception(DbObject::getSqlErrorMessage(get_class(), $sql));

        $rowCount = $Stmt->rowCount();
        Log::getInstance()->debug(__METHOD__ . '() - ' . $rowCount . ' cache items deleted');
        return $rowCount;
    }
    // End findBySvgPatternId


    /**
     * Find elements by processConfigId
     *
     * @param  int     $processConfigId
     * @param  boolean $force - Bypass caching
     * @return ElcaElementSet
     */
    public static function clearSvgPatternCacheByProcessConfigIds(array $processConfigIds)
    {
        if(!count($processConfigIds))
            return false;

        $sql = sprintf("DELETE FROM %s WHERE ident ILIKE '%s' AND element_id IN (SELECT DISTINCT e.id
                              FROM %s e
                              JOIN %s c ON e.id = c.element_id
                             WHERE process_config_id IN (%s))"
            , ElcaElementAttribute::TABLE_NAME
            , ElcaElementImageCache::SVG_IMAGE_CACHE_ATTRIBUTE_IDENT . '%'
            , ElcaElement::TABLE_NAME
            , ElcaElementComponent::TABLE_NAME
            , join(', ', $processConfigIds)
        );

        $Stmt = DbObject::prepareStatement($sql);

        if(!$Stmt->execute())
            throw new \Exception(DbObject::getSqlErrorMessage(get_class(), $sql));

        $rowCount = $Stmt->rowCount();
        Log::getInstance()->debug(__METHOD__ . '() - ' . $rowCount . ' cache items deleted');
        return $rowCount;
    }
    // End findByProcessConfigId

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaElement::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
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
        return self::_count(get_class(), ElcaElement::getTablename(), $initValues, $force);
    }
    // End dbCount


    // private

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
// End class ElcaElementSet