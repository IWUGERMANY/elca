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
 * Handles a set of ElcaProcessCategory
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessCategorySet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_PROCESS_CATEGORIES = 'elca.process_categories_v';

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all categories of a given life cycle
     *
     * @param  string  $lifeCycle
     * @param  boolean $force - Bypass caching
     *
     * @return ElcaProcessCategorySet
     */
    public static function findByLifeCycleIdent($lifeCycleIdent, $force = false)
    {
        if (!$lifeCycleIdent)
            return;

        $sql = sprintf("SELECT DISTINCT c.*
                             , c2.ref_num::int AS parent_node_ref_num
                             , c2.name AS parent_node_name
                          FROM %s c
                          JOIN %s p ON c.node_id = p.process_category_node_id AND p.life_cycle_ident = :lifeCycleIdent
                          JOIN %s c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1
                      ORDER BY c2.ref_num::int, c.ref_num"
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , ElcaProcess::TABLE_NAME
            , self::VIEW_ELCA_PROCESS_CATEGORIES
        );

        return self::_findBySql(get_class(), $sql, ['lifeCycleIdent' => $lifeCycleIdent], $force);
    }
    // End findByLifeCycleIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all categories extended by parentNode which are associated with a process config
     *
     * If a inUnit is given, only categories will be returned, which
     * associated process config has at least a conversion for that inUnit
     *
     * @param  string  $inUnit
     * @param  boolean $force - Bypass caching
     *
     * @return ElcaProcessCategorySet
     */
    public static function findExtended($inUnit = null, $referenceProcessConfigsOnly = false, array $processDbIds = null, $filterByProjectVariantId = null, $epdSubType = null, $force = false)
    {
        $initValues = [];

        $sql = sprintf("SELECT DISTINCT c.*
                             , replace(c.ref_num,  '.','')::int AS p_order1
                             , replace(c2.ref_num, '.','')::int AS p_order2
                             , c2.ref_num::numeric AS parent_ref_num
                             , c2.ref_num ||' '|| c2.name AS parent_node_name
                          FROM %s c
                          JOIN %s p ON c.node_id = p.process_category_node_id
                          JOIN %s c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1"
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , ElcaProcessConfig::TABLE_NAME
            , self::VIEW_ELCA_PROCESS_CATEGORIES
        );

        if ($inUnit) {

            if (\utf8_strpos($inUnit, ',') !== false)
                $inUnit = explode(',', $inUnit);

            $procDbSql = '';
            if (count($processDbIds)) {
                $procDbSql = sprintf("AND pc.process_db_id IN (%s)", \implode(',', $processDbIds));
            }

            if (is_array($inUnit))
            {
                foreach ($inUnit as $i => $unit)
                {
                    $initValues['unit' . $i] = $unit;
                    $parts[] = ':unit' . $i;
                }

                $sql .= sprintf(' JOIN %s pc ON p.id = pc.process_config_id %s
                             WHERE (pc.in_unit IN (%s) OR pc.out_unit IN (%s))'
                , $procDbSql ? ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS : ElcaProcessConversion::TABLE_NAME
                , $procDbSql
                , implode(', ', $parts)
                    , implode(', ', $parts)
                    );
            }
            else
            {
                $initValues['inUnit'] = $inUnit;
                $sql .= sprintf(' JOIN %s pc ON p.id = pc.process_config_id %s
                             WHERE :inUnit IN (pc.in_unit, pc.out_unit)'
                    , $procDbSql ? ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS : ElcaProcessConversion::TABLE_NAME,
                $procDbSql
                );
            }
        }

        if ($referenceProcessConfigsOnly)
            $sql .= ' AND p.is_reference = true';

        if ($processDbIds || $epdSubType) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x WHERE x.id = p.id '
                , ElcaProcessConfigSet::VIEW_PROCESS_CONFIG_PROCESS_DBS
            );

            if ($processDbIds) {
                $sql .= 'AND :processDbIds::int[] && x.process_db_ids';
                $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
            }

            if ($epdSubType) {
                $sql .= ' AND :epdSubType = ANY (x.epd_types)';
                $initValues['epdSubType'] = $epdSubType;
            }

            $sql .= ')';
        }

        if ($filterByProjectVariantId) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s y ON y.id = x.element_id WHERE x.process_config_id = p.id AND y.project_variant_id = :projectVariantId)'
                , ElcaElementComponent::TABLE_NAME
                , ElcaElement::TABLE_NAME
            );

            $initValues['projectVariantId'] = $filterByProjectVariantId;
        }

        $sql .= ' ORDER BY p_order2, p_order1';

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findExtened

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all categories extended by parentNode which are associated with operational process configs
     *
     * @param  boolean $force - Bypass caching
     *
     * @return ElcaProcessCategorySet
     */
    public static function findOperationCategories($inUnit, $referenceProcessConfigsOnly = false, $activeProcessesOnly = false, $force = false)
    {
        $initValues = [];
        $initValues['lifeCyclePhase'] = ElcaLifeCycle::PHASE_OP;
        $initValues['inUnit'] = $inUnit;

        $sql = sprintf("SELECT DISTINCT c.*
                             , replace(c.ref_num,  '.','')::int AS p_order1
                             , replace(c2.ref_num, '.','')::int AS p_order2
                             , c2.ref_num::numeric AS parent_ref_num
                             , c2.ref_num ||' '|| c2.name AS parent_node_name
                          FROM %s c
                          JOIN %s c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1
                          JOIN %s p  ON c.node_id = p.process_category_node_id
                          JOIN %s pa ON p.id = pa.process_config_id
                          JOIN %s pc ON p.id = pc.process_config_id AND pc.process_db_id = pa.process_db_id
                         WHERE pa.life_cycle_phase = :lifeCyclePhase
                           AND pc.in_unit = :inUnit %s"
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            , ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS
            , $referenceProcessConfigsOnly ? ' AND p.is_reference = true' : ''
        );

        if ($activeProcessesOnly) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active)'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );
        }

        $sql .= ' ORDER BY p_order2, p_order1';

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findOperationCategories

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all categories extended by parentNode which are associated with final energy supply process configs
     *
     * @param          $inUnit
     * @param bool     $referenceProcessConfigsOnly
     * @param  boolean $force - Bypass caching
     *
     * @throws Exception
     * @return ElcaProcessCategorySet
     */
    public static function findFinalEnergySupplyCategories($inUnit, $referenceProcessConfigsOnly = false, $activeProcessesOnly = false, $force = false)
    {
        $initValues = [];
        $initValues['lifeCyclePhase'] = ElcaLifeCycle::PHASE_OP;
        $initValues['inUnit'] = $inUnit;
        $initValues['opAsSupply'] = ElcaProcessConfigAttribute::IDENT_OP_AS_SUPPLY;

        $sql = sprintf("SELECT DISTINCT c.*
                             , replace(c.ref_num,  '.','')::int AS p_order1
                             , replace(c2.ref_num, '.','')::int AS p_order2
                             , c2.ref_num::numeric AS parent_ref_num
                             , c2.ref_num ||' '|| c2.name AS parent_node_name
                          FROM %s c
                          JOIN %s c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1
                          JOIN %s p  ON c.node_id = p.process_category_node_id
                          JOIN %s pca ON p.id = pca.process_config_id AND pca.ident = :opAsSupply AND pca.numeric_value = 1
                          JOIN %s pa ON p.id = pa.process_config_id
                          JOIN %s pc ON p.id = pc.process_config_id  AND pc.process_db_id = pa.process_db_id
                         WHERE pa.life_cycle_phase = :lifeCyclePhase
                           AND pc.in_unit = :inUnit %s"
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessConfigAttribute::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            , ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS
            , $referenceProcessConfigsOnly ? ' AND p.is_reference = true' : ''
        );

        if ($activeProcessesOnly) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active)'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );
        }

        $sql .= ' ORDER BY p_order2, p_order1';

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findFinalEnergySupplyCategories

    //////////////////////////////////////////////////////////////////////////////////////


    /**
     * Finds all categories extended by parentNode which are associated with operational process configs
     *
     * @param          $inUnit
     * @param bool     $referenceProcessConfigsOnly
     * @param  boolean $force - Bypass caching
     *
     * @throws Exception
     * @return ElcaProcessCategorySet
     */
    public static function findTransportCategories($inUnit, $referenceProcessConfigsOnly = false, $activeProcessesOnly = false, $force = false)
    {
        $initValues = [];
        $initValues['lifeCycleIdent1'] = ElcaLifeCycle::IDENT_A4;
        $initValues['lifeCycleIdent2'] = ElcaLifeCycle::PHASE_OP;
        $initValues['inUnit'] = $inUnit;

        $sql = sprintf("SELECT DISTINCT c.*
                             , replace(c.ref_num,  '.','')::int AS p_order1
                             , replace(c2.ref_num, '.','')::int AS p_order2
                             , c2.ref_num::numeric AS parent_ref_num
                             , c2.ref_num ||' '|| c2.name AS parent_node_name
                          FROM %s c
                          JOIN %s c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1
                          JOIN %s p  ON c.node_id = p.process_category_node_id
                          JOIN %s pa ON p.id = pa.process_config_id
                          JOIN %s pc ON p.id = pc.process_config_id AND pc.process_db_id = pa.process_db_id
                         WHERE pa.life_cycle_ident IN (:lifeCycleIdent1, :lifeCycleIdent2)
                           AND pc.in_unit = :inUnit %s"
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            , ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS
            , $referenceProcessConfigsOnly ? ' AND p.is_reference = true' : ''
        );

        if ($activeProcessesOnly) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active)'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );
        }

        $sql .= ' ORDER BY p_order2, p_order1';
        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findTransportCategories

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all direct child categories of the given parent category
     *
     * @param  -
     *
     * @return ElcaProcessCategorySet
     */
    public static function findByParent(ElcaProcessCategory $Category, $force = false)
    {
        if (!$Category->isInitialized())
            return new ElcaProcessCategorySet();

        $initValues = ['nodeId' => $Category->getNodeId()];

        $sql = sprintf('SELECT t.*
                          FROM %s n
                          JOIN %s t ON n.root_id = t.root_id AND t.lft BETWEEN n.lft AND n.rgt AND t.level = n.level + 1
                         WHERE n.id = :nodeId
                         ORDER BY t.lft'
            , NestedNode::getTablename()
            , self::VIEW_ELCA_PROCESS_CATEGORIES
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByParent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all parents of the given categoryId
     *
     * @param  -
     *
     * @return ElcaProcessCategorySet
     */
    public static function findParentsById($categoryId, $includeRoot = false, $force = false)
    {
        if (!$categoryId)
            return new ElcaProcessCategorySet();

        $sql = sprintf("SELECT t.*
                          FROM %s n
                          JOIN %s t ON n.root_id = t.root_id AND n.lft BETWEEN t.lft AND t.rgt AND t.level <= n.level
                         WHERE n.id = :nodeId %s
                         ORDER BY t.lft"
            , NestedNode::getTablename()
            , self::VIEW_ELCA_PROCESS_CATEGORIES
            , !$includeRoot ? 'AND t.id <> t.root_id' : ''
        );

        return self::_findBySql(get_class(), $sql, ['nodeId' => $categoryId], $force);
    }
    // End findParentsById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     *
     * @return ElcaProcessCategorySet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaProcessCategory::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy count
     *
     * @param  array   $initValues - key value array
     * @param  boolean $force      - Bypass caching
     *
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaProcessCategory::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaProcessCategorySet