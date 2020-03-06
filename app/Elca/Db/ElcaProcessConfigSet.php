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
 * Handles a set of ElcaProcessConfig
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfigSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_PROCESS_CONFIG_EXTENDED_SEARCH = 'elca.process_configs_extended_search_v';
    const VIEW_PROCESS_CONFIG_PROCESS_DBS = 'elca.process_config_process_dbs_view';
    const VIEW_ALL_PROCESS_CONFIG_PROCESS_DBS = 'elca.all_process_config_process_dbs_view';


    // public


    /**
     * Find all last modified process configs
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  boolean $force      - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function findLastModified(array $initValues = null, array $orderBy = null, $limit = 10, $force = false)
    {
        if (is_null($orderBy))
            $orderBy = array('coalesce(modified, created)' => 'DESC');

        $sql = sprintf('SELECT *
                          FROM %s'
            , ElcaProcessConfig::TABLE_NAME);

        if ($conditions = self::buildConditions($initValues))
            $sql .= ' WHERE ' . $conditions;

        if ($orderSql = self::buildOrderView($orderBy, $limit))
            $sql .= ' ' . $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findLastModified


    /**
     * Find all process configs by elementId
     *
     * @param  string  $elementId - key value array
     * @param  array   $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit     - limit on resultset
     * @param  boolean $force     - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function findByElementId($elementId, array $orderBy = null, $limit = null, $force = false)
    {
        if (!$elementId)
            return new ElcaProcessConfigSet();

        $initValues = array('elementId' => $elementId);

        $sql = sprintf('SELECT DISTINCT p.*
                          FROM %s c
                          JOIN %s p ON p.id = c.process_config_id
                         WHERE c.element_id = :elementId'
            , ElcaElementComponent::TABLE_NAME
            , ElcaProcessConfig::TABLE_NAME);

        if ($orderSql = self::buildOrderView($orderBy, $limit))
            $sql .= ' ' . $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByElementId


    /**
     * Find all process configs by composite elementId
     *
     * @param  string  $elementId - key value array
     * @param  array   $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit     - limit on resultset
     * @param  boolean $force     - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function findByCompositeElementId($elementId, array $orderBy = null, $limit = null, $force = false)
    {
        if (!$elementId)
            return new ElcaProcessConfigSet();

        $initValues = array('elementId' => $elementId);

        $sql = sprintf('SELECT DISTINCT p.*
                          FROM %s a
                          JOIN %s e ON e.id = a.element_id
                          JOIN %s c ON e.id = c.element_id
                          JOIN %s p ON p.id = c.process_config_id
                         WHERE a.composite_element_id = :elementId'
            , ElcaCompositeElement::TABLE_NAME
            , ElcaElement::TABLE_NAME
            , ElcaElementComponent::TABLE_NAME
            , ElcaProcessConfig::TABLE_NAME);

        if ($orderSql = self::buildOrderView($orderBy, $limit))
            $sql .= ' ' . $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByElementId


    /**
     * Find all process configs for a certain category and optional an certain in_unit
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function findByProcessCategoryNodeId($processCategoryNodeId, $inUnit = null, array $orderBy = null, $referenceOnly = false, array $processDbIds = null, $includeStale = false, $filterByProjectVariantId = null, $epdSubType = null, $onlyProdConfigs = true, $force = false)
    {
        if (!$processCategoryNodeId)
            return new ElcaProcessConfigSet();

        $initValues = array('processCategoryNodeId' => $processCategoryNodeId);

        $sql = sprintf('SELECT DISTINCT p.*
                          FROM %s p
                          JOIN %s c ON p.id = c.process_config_id AND c.process_db_id = ANY(p.process_db_ids)
                         WHERE p.process_category_node_id = :processCategoryNodeId
                           '
            , $onlyProdConfigs ? self::VIEW_PROCESS_CONFIG_PROCESS_DBS : self::VIEW_ALL_PROCESS_CONFIG_PROCESS_DBS
            , ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS
        );
        

        if ($inUnit) {
            if (\utf8_strpos($inUnit, ',') !== false)
                $inUnit = explode(',', $inUnit);

            if (is_array($inUnit)) {
                foreach ($inUnit as $i => $unit) {
                    $initValues['unit' . $i] = $unit;
                    $parts[] = ':unit' . $i;
                }
                $sql .= ' AND (in_unit IN (' . implode(', ', $parts) . ') OR out_unit IN (' . \implode(', ', $parts) .'))';
            } else {
                $initValues['inUnit'] = $inUnit;
                $sql .= ' AND :inUnit IN (in_unit, out_unit)';
            }
        }

        if ($referenceOnly)
            $sql .= ' AND p.is_reference = true';

        if ($processDbIds) {
            $sql .= ' AND :processDbIds::int[] && p.process_db_ids';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }

        if ($filterByProjectVariantId) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s y ON y.id = x.element_id WHERE x.process_config_id = p.id AND y.project_variant_id = :projectVariantId)'
                , ElcaElementComponent::TABLE_NAME
                , ElcaElement::TABLE_NAME
            );

            $initValues['projectVariantId'] = $filterByProjectVariantId;
        }

        if (null !== $epdSubType) {
            $sql .= ' AND :epdSubType = ANY (p.epd_types)';
            $initValues['epdSubType'] = $epdSubType;
        }

        if (!$includeStale) {
            $sql .= ' AND p.is_stale = false';
        }

        if ($orderSql = self::buildOrderView($orderBy))
            $sql .= ' ' . $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessCategoryNodeId


    /**
     * Find all operational process configs for a certain category
     *
     * @param          $processCategoryNodeId
     * @param null     $inUnit
     * @param  array   $orderBy - array map of columns on to directions array('id' => 'DESC')
     * @param bool     $referenceOnly
     * @param  boolean $force   - Bypass caching
     *
     * @throws Exception
     * @return ElcaProcessConfigSet
     */
    public static function findOperationsByProcessCategoryNodeId($processCategoryNodeId, $inUnit = null, array $orderBy = null, $referenceOnly = false, $activeProcessesOnly = false, $includeStale = false, $force = false)
    {
        if (!$processCategoryNodeId)
            return new ElcaProcessConfigSet();

        $initValues = array('processCategoryNodeId' => $processCategoryNodeId,
                            'lcPhase'               => ElcaLifeCycle::PHASE_OP);

        $convJoin = $inUnit ? sprintf('JOIN %s c ON p.id = c.process_config_id AND c.process_db_id = pa.process_db_id',
            ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS) : null;

        $sql = sprintf('SELECT DISTINCT p.*
                          FROM %s p
                          JOIN %s pa ON p.id = pa.process_config_id
                          %s
                         WHERE p.process_category_node_id = :processCategoryNodeId
                           AND pa.life_cycle_phase = :lcPhase'
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            , $convJoin
        );

        if ($inUnit) {
            $initValues['inUnit'] = $inUnit;
            $sql .= ' AND in_unit = :inUnit';
        }

        if ($referenceOnly)
            $sql .= ' AND p.is_reference = true';

        if ($activeProcessesOnly) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );

            if (is_numeric($activeProcessesOnly)) {
                $sql .= ' AND x.process_db_id = :processDbId)';
                $initValues['processDbId'] = $activeProcessesOnly;
            } else {
                $sql .= ')';
            }
        }
        if (!$includeStale) {
            $sql .= ' AND p.is_stale = false';
        }

        if ($orderSql = self::buildOrderView($orderBy))
            $sql .= ' ' . $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findOperationsByProcessCategoryNodeId


    /**
     * Find all operational process configs for a certain category
     *
     * @param          $processCategoryNodeId
     * @param null     $inUnit
     * @param  array   $orderBy - array map of columns on to directions array('id' => 'DESC')
     * @param bool     $referenceOnly
     * @param  boolean $force   - Bypass caching
     *
     * @throws Exception
     * @return ElcaProcessConfigSet
     */
    public static function findFinalEnergySuppliesByProcessCategoryNodeId($processCategoryNodeId, $inUnit = null, array $orderBy = null, $referenceOnly = false, $activeProcessesOnly = false, $includeStale = false, $force = false)
    {
        if (!$processCategoryNodeId)
            return new ElcaProcessConfigSet();

        $initValues = array('processCategoryNodeId' => $processCategoryNodeId,
                            'lcPhase'               => ElcaLifeCycle::PHASE_OP,
                            'opAsSupply'            => ElcaProcessConfigAttribute::IDENT_OP_AS_SUPPLY
        );

        $convJoin = $inUnit ? sprintf('JOIN %s c ON p.id = c.process_config_id AND c.process_db_id = pa.process_db_id',
            ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS) : null;

        $sql = sprintf('SELECT DISTINCT p.*
                          FROM %s p
                          JOIN %s pca ON p.id = pca.process_config_id AND pca.ident = :opAsSupply AND pca.numeric_value = 1
                          JOIN %s pa ON p.id = pa.process_config_id
                          %s
                         WHERE p.process_category_node_id = :processCategoryNodeId
                           AND pa.life_cycle_phase = :lcPhase'
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessConfigAttribute::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            , $convJoin
        );

        if ($inUnit) {
            $initValues['inUnit'] = $inUnit;
            $sql .= ' AND in_unit = :inUnit';
        }

        if ($referenceOnly)
            $sql .= ' AND p.is_reference = true';

        if ($activeProcessesOnly) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );

            if (is_numeric($activeProcessesOnly)) {
                $sql .= ' AND x.process_db_id = :processDbId)';
                $initValues['processDbId'] = $activeProcessesOnly;
            } else {
                $sql .= ')';
            }

        }
        if (!$includeStale) {
            $sql .= ' AND p.is_stale = false';
        }

        if ($orderSql = self::buildOrderView($orderBy))
            $sql .= ' ' . $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findFinalEnergySuppliesByProcessCategoryNodeId


    /**
     * Find all operational process configs for a certain category
     *
     * @param          $processCategoryNodeId
     * @param null     $inUnit
     * @param  array   $orderBy - array map of columns on to directions array('id' => 'DESC')
     * @param bool     $referenceOnly
     * @param  boolean $force   - Bypass caching
     *
     * @throws Exception
     * @return ElcaProcessConfigSet
     */
    public static function findTransportsByProcessCategoryNodeId($processCategoryNodeId, $inUnit = null, array $orderBy = null, $referenceOnly = false, $activeProcessesOnly = false, $includeStale = false, $force = false)
    {
        if (!$processCategoryNodeId)
            return new ElcaProcessConfigSet();

        $initValues = array('processCategoryNodeId' => $processCategoryNodeId,
                            'lcIdent1'              => ElcaLifeCycle::IDENT_A4,
                            'lcIdent2'              => ElcaLifeCycle::PHASE_OP
        );

        $convJoin = $inUnit ? sprintf('JOIN %s c ON p.id = c.process_config_id AND c.process_db_id = pa.process_db_id',
            ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS) : null;

        $sql = sprintf('SELECT DISTINCT p.*
                          FROM %s p
                          JOIN %s pa ON p.id = pa.process_config_id
                          %s
                         WHERE p.process_category_node_id = :processCategoryNodeId
                           AND pa.life_cycle_ident IN (:lcIdent1, :lcIdent2)'
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            , $convJoin
        );

        if ($inUnit) {
            $initValues['inUnit'] = $inUnit;
            $sql .= ' AND in_unit = :inUnit';
        }

        if ($referenceOnly)
            $sql .= ' AND p.is_reference = true';

        if ($activeProcessesOnly) {
            $sql .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );

            if (is_numeric($activeProcessesOnly)) {
                $sql .= ' AND x.process_db_id = :processDbId)';
                $initValues['processDbId'] = $activeProcessesOnly;
            } else {
                $sql .= ')';
            }
        }
        if (!$includeStale) {
            $sql .= ' AND p.is_stale = false';
        }
        if ($orderSql = self::buildOrderView($orderBy))
            $sql .= ' ' . $orderSql;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findTransportsByProcessCategoryNodeId


    /**
     * Find by search terms
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     *
     * @return ElcaElementSet
     */
    public static function searchExtended(array $keywords, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $conditions = array();

        if ($filter = self::buildConditions($initValues))
            $conditions[] = $filter;

        $searchTerms = array();
        foreach ($keywords as $keyword) {
            $keyword = \trim($keyword);

            if ($keyword === '\'')
                continue;

            $keyword = str_replace('*', '%', $keyword);

            if ($keyword === '')
                continue;

            $searchTerms[] = $keyword;
        }

        if (count($searchTerms)) {
            if ($searchConditions = self::getSearchConditions($searchTerms, 'pc.search_vector', $initValues)) {
                $conditions[] = $searchConditions;
            }
        }

        $sql = sprintf('SELECT DISTINCT
                                        pc.id
                                      , pc.process_category_node_id
                                      , pc.name
                                      , pc.description
                                      , pc.avg_life_time
                                      , pc.min_life_time
                                      , pc.max_life_time
                                      , pc.life_time_info
                                      , pc.avg_life_time_info
                                      , pc.min_life_time_info
                                      , pc.max_life_time_info
                                      , pc.density
                                      , pc.thermal_conductivity
                                      , pc.thermal_resistance
                                      , pc.is_reference
                                      , pc.f_hs_hi
                             , pc.waste_code
							 , pc.waste_code_suffix
							 , pc.lambda_value
							 , pc.element_group_a
							 , pc.element_group_b
                             , pc.element_district_heating
                             , pc.element_refrigerant
                             , pc.element_flammable
                                , pc.default_size
                                      , pc.uuid
                                      , pc.svg_pattern_id
                                      , pc.is_stale
                                      , pc.created
                                      , pc.modified
                          FROM %s pc'
            , self::VIEW_PROCESS_CONFIG_EXTENDED_SEARCH
        );

        if ($conditions)
            $sql .= ' WHERE ' . \implode(' AND ', $conditions);

        if ($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' ' . $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End searchExtended


    /**
     * Inits a list of ElcaProcessConfigs which have no processes assigned
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  boolean $force      - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function findStaleWithoutAssignments(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $sql = sprintf('SELECT pc.*
                          FROM %s pc
                     LEFT JOIN %s a ON a.process_config_id = pc.id
                         WHERE (pc.modified IS NULL OR pc.modified - pc.created < \'5 minutes\')
                           AND a.id IS NULL'
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessLifeCycleAssignment::TABLE_NAME
        );

        if ($conditions = self::buildConditions($initValues))
            $sql .= ' AND ' . $conditions;

        if ($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' ' . $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findWithoutAssignments


    /**
     * Inits a list of ElcaProcessConfigs by process uuid
     *
     * @param  string  $processUuid
     * @param array    $orderBy
     * @param null     $limit
     * @param null     $offset
     * @param  boolean $force - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function findByProcessUuid($processUuid, $lcPhase = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if (!$processUuid)
            return new ElcaProcessConfigSet();

        $initValues = array('processUuid' => $processUuid);

        $sql = sprintf("SELECT DISTINCT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.waste_code
							 , pc.waste_code_suffix
							 , pc.lambda_value
							 , pc.element_group_a
							 , pc.element_group_b
                             , pc.default_size
                             , pc.element_district_heating
                             , pc.element_refrigerant
                             , pc.element_flammable
                             , pc.uuid
                             , pc.svg_pattern_id
                             , pc.is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.uuid = :processUuid"
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
        );

        if (null !== $lcPhase) {
            $sql .= ' AND plca.life_cycle_phase = :lcPhase';
            $initValues['lcPhase'] = $lcPhase;
        }

        if ($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' ' . $orderView;


        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }

    public static function findByProcessId($processId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if (!$processId)
            return new ElcaProcessConfigSet();

        $sql = sprintf("SELECT DISTINCT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.waste_code
							 , pc.waste_code_suffix
							 , pc.lambda_value
							 , pc.element_group_a
							 , pc.element_group_b
                             , pc.default_size
                             , pc.element_district_heating
                             , pc.element_refrigerant
                             , pc.element_flammable
                             , pc.uuid
                             , pc.svg_pattern_id
                             , pc.is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.id = :processId"
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
        );

        if ($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' ' . $orderView;

        return self::_findBySql(get_class(), $sql, array('processId' => $processId), $force);
    }

    /**
     * Inits a list of ElcaProcessConfigs by a process name
     *
     * @param string   $processName
     * @param string   $lcPhase
     * @param array    $orderBy
     * @param null     $limit
     * @param null     $offset
     * @param  boolean $force - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function findByProcessName($processName, $lcPhase = null, $epdSubType = null, $geographicalRepresentativeness = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if (!$processName)
            return new ElcaProcessConfigSet();

        $initValues = array('processName' => $processName);

        $sql = sprintf("SELECT DISTINCT pc.*
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                          JOIN %s db ON db.id = plca.process_db_id
                         WHERE plca.name = :processName"
            , ElcaProcessConfig::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
            , ElcaProcessDb::TABLE_NAME
        );

        if ($lcPhase) {
            $sql .= ' AND plca.life_cycle_phase = :lcPhase';
            $initValues['lcPhase'] = $lcPhase;
        }

        if ($epdSubType) {
            $sql .= ' AND (plca.epd_type = :epdSubType OR plca.epd_type IS NULL)';
            $initValues['epdSubType'] = $epdSubType;
        }

        if ($geographicalRepresentativeness) {
            $sql .= ' AND (db.is_en15804_compliant = false OR plca.geographical_representativeness = :geographicalRepresentativeness)';
            $initValues['geographicalRepresentativeness'] = $geographicalRepresentativeness;
        }

        if ($orderView = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' ' . $orderView;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessName


    /**
     * Lazy find
     *
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     *
     * @return ElcaProcessConfigSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaProcessConfig::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find


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
        return self::_count(get_class(), ElcaProcessConfig::getTablename(), $initValues, $force);
    }
    // End dbCount

    /**
     * Returns the search conditions
     *
     * @param array   $keywords
     * @param  string $searchField
     * @param array   $initValues
     *
     * @return string
     */
    private static function getSearchConditions(array $keywords, $searchField, array &$initValues)
    {
        $lftBoundary = $rgtBoundary = '%';

        $queries = array();
        foreach ($keywords as $index => $token) {
            $varName = 'token' . $index;

            $queries[] = sprintf("%s ilike :%s", $searchField, $varName);
            $initValues[$varName] = $lftBoundary . $token . $rgtBoundary;
        }

        $conditions = null;
        if (count($queries))
            $conditions = '(' . join(' AND ', $queries) . ')';

        return $conditions;
    }
}
// End class ElcaProcessConfigSet