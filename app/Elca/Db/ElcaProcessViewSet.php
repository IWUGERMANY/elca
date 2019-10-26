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
 * Set of ElcaProcesses
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessViewSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_ELCA_PROCESS_INDICATORS = 'elca.process_indicators_v';
    const VIEW_ELCA_CACHE_INDICATOR_RESULTS = 'elca_cache.indicator_results_v';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a list of processes with indicator
     *
     * @param  int $processDbId
     * @param  int $limit
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProcessViewSet
     */
    public static function findWithIndicators($processDbId, $limit = null, $force = false)
    {
        $indicatorSqls = $targets = array();

        $IndicatorSet = ElcaIndicatorSet::find();
        foreach($IndicatorSet as $i => $Indicator)
        {
            $tbl = 'i'.$i;
            $targets[] = $tbl.'.value AS "'.$Indicator->getIdent().'"';
            $indicatorSqls[] = ' LEFT JOIN '. ElcaProcessIndicator::TABLE_NAME.' '. $tbl .' ON p.id = '.$tbl.'.process_id AND '.$tbl.'.indicator_id = '.$Indicator->getId();
        }

        $sql = sprintf('SELECT p.id
                             , p.uuid
                             , p.ref_value AS "refValue"
                             , p.ref_unit AS "refUnit"
                             , CASE WHEN p.scenario_id IS NOT NULL THEN p.name_orig ||\' [\'|| s.description ||\']\'
                                    ELSE p.name_orig
                               END AS "nameOrig"
                             , p.life_cycle_ident AS "lifeCycleIdent"
                             , p.life_cycle_name AS "lifeCycleName"
                             , c.name AS "category"
                             , %s
                          FROM %s p %s
                          JOIN %s c ON c.node_id = p.process_category_node_id
                     LEFT JOIN %s s ON s.id = p.scenario_id
                         WHERE p.process_db_id = :processDbId
                      ORDER BY name_orig, life_cycle_p_order'
                       , join(', ', $targets)
                       , ElcaProcessSet::VIEW_ELCA_PROCESSES
                       , join(' ', $indicatorSqls)
                       , ElcaProcessCategory::TABLE_NAME
                       , ElcaProcessScenario::TABLE_NAME
                       );

        if($limit)
            $sql .= ' LIMIT '.$limit;

        return self::_findBySql(get_class(),
                                $sql,
                                array('processDbId' => $processDbId)
                                );
    }
    // End findConstructionAssets

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all processes for the given process config id
     *
     * @throws \Beibob\Blibs\Exception
     * @param  int     $processConfigId
     * @param  string  $lcPhase
     * @param  string  $lcIdent
     * @param  boolean $din15804
     * @param  number  $ratio
     * @param  boolean $force - Bypass caching
     * @return ElcaProcessViewSet
     */
    public static function findWithProcessDbByProcessConfigIdAndLifeCycle(
        $processConfigId, $lcPhase, $lcIdent = null, $din15804 = false, $ratio = null, $force = false
    )
    {
        if(!$processConfigId)
            return new ElcaProcessViewSet();

        $initValues = array();
        $initValues['processConfigId'] = $processConfigId;
        $initValues['lifeCyclePhase'] = $lcPhase;

        if ($lcIdent) {
            $initValues['lifeCycleIdent'] = $lcIdent;
            $lcSql = '(p.life_cycle_ident = :lifeCycleIdent OR p.life_cycle_ident = :lifeCyclePhase)';
        }
        else {
            $lcSql = 'p.life_cycle_phase = :lifeCyclePhase';
        }

        if (!is_null($ratio)) {
            $initValues['ratio'] = $ratio;
        }

        $sql = sprintf('SELECT p.id AS "processId" 
                             , CASE WHEN p.scenario_id IS NOT NULL THEN p.name_orig ||\' [\'|| s.description ||\']\'
                                    ELSE p.name_orig
                               END AS "nameOrig"
                             , (p.ratio * 100)::int ||\'%%\'  AS "ratio"
                             , p.uuid
                             , p.version
                             , p.ref_value AS "refValue"
                             , p.ref_unit AS "refUnit"
                             , d.name AS "processDb"
                             , d.id AS "processDbId"
                             , p.life_cycle_name AS "lifeCycleName"
                             , p.epd_type AS "epdType"
                             , p.geographical_representativeness AS "geographicalRepresentativeness"
                          FROM %s p
                          JOIN %s d ON d.id = p.process_db_id
                     LEFT JOIN %s s ON s.id = p.scenario_id
                         WHERE p.process_config_id = :processConfigId
                           AND %s %s
                      ORDER BY d.version'
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       , ElcaProcessDb::TABLE_NAME
                       , ElcaProcessScenario::TABLE_NAME
                       , $lcSql
                       , !is_null($ratio)? 'AND ratio = :ratio' : ''
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all processes for the given process config id
     *
     * @param  int      $processConfigId
     * @param  array    $lifeCyclePhase
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProcessViewSet
     */
    public static function findWithProcessDbByProcessConfigIdAndPhase($processConfigId, $lifeCyclePhase, $force = false)
    {
        if(!$processConfigId)
            return new ElcaProcessViewSet();

        $initValues = array();
        $initValues['processConfigId'] = $processConfigId;
        $initValues['lifeCyclePhase'] = $lifeCyclePhase;

        $sql = sprintf('SELECT p.id AS "processId" 
                             , p.name_orig AS "nameOrig"
                             , (p.ratio * 100)::int ||\'%%\'  AS "ratio"
                             , p.uuid
                             , p.ref_value AS "refValue"
                             , p.ref_unit AS "refUnit"
                             , d.name AS "processDb"
                             , p.life_cycle_description AS "lifeCycleDescription"
                          FROM %s p
                          JOIN %s d ON d.id = p.process_db_id
                         WHERE p.process_config_id = :processConfigId
                           AND p.life_cycle_phase = :lifeCyclePhase
                           AND d.is_active 
                      ORDER BY d.version, p.life_cycle_ident'
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       , ElcaProcessDb::TABLE_NAME
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all processes for the given process config id
     *
     * @param  int      $processConfigId
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProcessViewSet
     */
    public static function findWithProcessDbByProcessConfigId($processConfigId, $force = false)
    {
        if(!$processConfigId)
            return new ElcaProcessViewSet();

        $initValues = array();
        $initValues['processConfigId'] = $processConfigId;

        $sql = sprintf('SELECT p.id AS "processId"
                             , p.name AS "name"
                             , (p.ratio * 100)::int ||\'%%\'  AS "ratio"
                             , p.uuid
                             , p.ref_unit AS "refUnit"
                             , d.name AS "processDb"
                             , p.life_cycle_phase AS "lifeCyclePhase"
                          FROM %s p
                          JOIN %s d ON d.id = p.process_db_id
                         WHERE p.process_config_id = :processConfigId
                      ORDER BY d.version, p.life_cycle_ident'
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       , ElcaProcessDb::TABLE_NAME
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessConfigId

    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find processes for the given elementComponentId with indicator results
     *
     * @param  int $elementComponentId
     * @param null $limit
     * @return ElcaProcessViewSet
     */
    public static function findResultsByElementComponentId($elementComponentId, $limit = null)
    {
        if(!$elementComponentId)
            return new ElcaProcessViewSet();

        $CElementComponent = ElcaCacheElementComponent::findByElementComponentId($elementComponentId);

        return self::_find(get_class(),
                           self::VIEW_ELCA_CACHE_INDICATOR_RESULTS,
                           array('item_id' => $CElementComponent->getItemId(), 'is_hidden' => false),
                           array('life_cycle_p_order' => 'ASC',
                                 'indicator_p_order' => 'ASC',
                                 'process_id' => 'ASC'),
                           $limit
                           );
    }
    // End findResultsByElementComponentId

    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find processes for the given elementId with indicator results
     *
     * @param  int      $elementId
     * @return ElcaProcessViewSet
     */
    public static function findResultsByElementId($elementId)
    {
        if(!$elementId)
            return new ElcaProcessViewSet();

        $CElement = ElcaCacheElement::findByElementId($elementId);

        return self::_find(get_class(),
                           self::VIEW_ELCA_CACHE_INDICATOR_RESULTS,
                           array('item_id' => $CElement->getItemId(), 'is_hidden' => false),
                           array('life_cycle_p_order' => 'ASC',
                                 'indicator_p_order' => 'ASC')
                           );
    }
    // End findResultsByElementComponentId

    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find processes for the given finalEnergyDemandId with indicator results
     *
     * @param  int $finalEnergyDemandId
     * @param null $limit
     * @return ElcaProcessViewSet
     */
    public static function findResultsByFinalEnergyDemandId($finalEnergyDemandId, $limit = null)
    {
        if(!$finalEnergyDemandId)
            return new ElcaProcessViewSet();

        $CDemand = ElcaCacheFinalEnergyDemand::findByFinalEnergyDemandId($finalEnergyDemandId);

        return self::_find(get_class(),
                           self::VIEW_ELCA_CACHE_INDICATOR_RESULTS,
                           array('item_id' => $CDemand->getItemId(), 'is_hidden' => false),
                           array('process_id' => 'ASC',
                                 'life_cycle_p_order' => 'ASC',
                                 'indicator_p_order' => 'ASC'),
                           $limit
                           );
    }
    // End findResultsByFinalEnergyDemandId


    /**
     * Find processes for the given cacheItemId with indicator results
     *
     * @param      $cacheItemId
     * @param null $limit
     * @return ElcaProcessViewSet
     */
    public static function findResultsByCacheItemId($cacheItemId, $limit = null)
    {
        if(!$cacheItemId)
            return new ElcaProcessViewSet();

        return self::_find(get_class(),
                           self::VIEW_ELCA_CACHE_INDICATOR_RESULTS,
                           array('item_id' => $cacheItemId, 'is_hidden' => false),
                           array('process_id' => 'ASC',
                                 'life_cycle_p_order' => 'ASC',
                                 'indicator_p_order' => 'ASC'),
                           $limit
        );
    }
    // End findResultsByCacheItemId


    /**
     * Find processes for the given transportId with indicator results
     *
     * @param  int $transportId
     * @param bool $force
     * @throws Exception
     * @return ElcaProcessViewSet
     */
    public static function findResultsByTransportId($transportId, $force = false)
    {
        if(!$transportId)
            return new ElcaProcessViewSet();

        $sql = sprintf('SELECT i.item_id
                             , i.life_cycle_ident
                             , i.indicator_id
                             , i.indicator_ident
                             , i.indicator_name
                             , i.process_id
                             , i.life_cycle_name
                             , i.life_cycle_p_order
                             , i.indicator_p_order
                             , i.name_orig
                             , m.project_transport_id AS transport_id
                             , i.value
                             , i.is_hidden
                          FROM %s i
                          JOIN %s cm ON i.item_id = cm.item_id
                          JOIN %s m  ON m.id = cm.transport_mean_id
                         WHERE m.project_transport_id = :transportId
                           AND i.is_hidden = false
                      ORDER BY process_id
                             , life_cycle_p_order
                             , indicator_p_order'
                      , self::VIEW_ELCA_CACHE_INDICATOR_RESULTS
                      , ElcaCacheTransportMean::TABLE_NAME
                      , ElcaProjectTransportMean::TABLE_NAME
        );

        return self::_findBySql(get_class(), $sql, array('transportId' => $transportId), $force);
    }
    // End findResultsByTransportId

    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all prod and eol processes assignments for 2011 process configs
     *
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProcessViewSet
     */
    public static function findProcessAssignments($dbVersion, $force = false)
    {
        $initValues = array();
        $initValues['version'] = $dbVersion;

        $sql = sprintf("SELECT c.name
                             , prod.version AS process_db_version
                             , prod.uuid AS prod_uuid
                             , prod.name_orig AS prod_name
                             , prod.ref_value AS prod_ref_value
                             , prod.ref_unit AS prod_ref_unit
                             , eol.uuid AS eol_uuid
                             , eol.name AS eol_name
                             , eol.ref_value AS eol_ref_value
                             , eol.ref_unit AS eol_ref_unit
                             , c.density
                             , c.avg_life_time
                             , c.min_life_time
                             , c.max_life_time
                             , c.life_time_info
                             , c.avg_life_time_info
                             , c.min_life_time_info
                             , c.max_life_time_info
                             , c.f_hs_hi
                             , array_to_string(array_accum(round(conv.factor, 2) ||' '|| conv.out_unit||'/'||conv.in_unit||''), '; ') AS conversions
                          FROM %s c
                          JOIN %s prod ON c.id = prod.process_config_id AND prod.life_cycle_ident = 'prod' AND prod.version = :version
                     LEFT JOIN %s eol  ON c.id = eol.process_config_id AND eol.life_cycle_ident = 'eol' AND eol.version = :version
                     LEFT JOIN %s conv ON c.id = conv.process_config_id AND conv.in_unit <> conv.out_unit
                      GROUP BY c.name
                             , prod.version
                             , prod.uuid
                             , prod.name_orig
                             , prod.ref_value
                             , prod.ref_unit
                             , eol.uuid
                             , eol.name
                             , eol.ref_value
                             , eol.ref_unit
                             , c.density
                             , c.avg_life_time
                             , c.min_life_time
                             , c.max_life_time
                             , c.life_time_info
                             , c.avg_life_time_info
                             , c.min_life_time_info
                             , c.max_life_time_info
                             , c.f_hs_hi
                      ORDER BY prod.name_orig ASC"
                       , ElcaProcessConfig::TABLE_NAME
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       , ElcaProcessConversion::TABLE_NAME
                       );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findProcessAssignments2011

    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all prod and eol processes assignments for 2011 process configs
     *
     * @param ElcaProcessDb $ProcessDb
     * @param  boolean      $force - Bypass caching
     * @throws Exception
     * @return ElcaProcessViewSet
     */
    public static function findProcessAssignmentsByProcessDbId(ElcaProcessDb $ProcessDb, $force = false)
    {
        if (!$ProcessDb->isInitialized())
            return new ElcaProcessViewSet();

        $initValues = array();
        $initValues['processDbId'] = $ProcessDb->getId();

        if ($ProcessDb->isEn15804Compliant()) {
            $lcIdents = array(ElcaLifeCycle::IDENT_A13, ElcaLifeCycle::IDENT_A4, ElcaLifeCycle::IDENT_B6,
                              ElcaLifeCycle::IDENT_C3, ElcaLifeCycle::IDENT_C4, ElcaLifeCycle::IDENT_D);
        } else {
            $lcIdents = array(ElcaLifeCycle::PHASE_PROD, ElcaLifeCycle::PHASE_OP, ElcaLifeCycle::PHASE_EOL);
        }

        $columns = $grpColumns = $lftJoins = $idColumns = array();
        foreach ($lcIdents as $index => $lcIdent) {
            $tblIdent = str_replace('-', '', \utf8_strtolower($lcIdent));

            $lftJoins[] = 'LEFT JOIN '. ElcaProcessSet::VIEW_ELCA_EXPORT_PROCESS_ASSIGNMENTS. ' '. $tblIdent . ' ON c.id = '. $tblIdent .'.process_config_id AND '. $tblIdent .'.life_cycle_ident = :lcIdent'. $index .' AND '. $tblIdent  .'.process_db_id = :processDbId';
            $initValues['lcIdent'.$index] = $lcIdent;
            $idColumns[] = $tblIdent.'.id';
            foreach (array('uuid', 'name_orig', 'ref_value', 'ref_unit') as $colName) {
                if ($colName == 'name_orig' && $ProcessDb->isEn15804Compliant()) {
                    $columns[] = $tblIdent. '.category_ref_num ||\' \'||'. $tblIdent. '.'. $colName.' AS '. $tblIdent .'_'. $colName;
                    $grpColumns[] = $tblIdent. '.category_ref_num ||\' \'||'. $tblIdent. '.'. $colName;

                } else {
                    $columns[] = $tblIdent. '.'. $colName.' AS '. $tblIdent .'_'. $colName;
                    $grpColumns[] = $tblIdent. '.'. $colName;
                }
            }
        }

        $sql = sprintf("SELECT c.id
                             , c.name
                             , c.density
                             , c.avg_life_time
                             , c.min_life_time
                             , c.max_life_time
                             , c.life_time_info
                             , c.avg_life_time_info
                             , c.min_life_time_info
                             , c.max_life_time_info
                             , c.f_hs_hi
                             , %s
                             , array_to_string(array_accum(round(conv.factor, 2) ||' '|| conv.out_unit||'/'||conv.in_unit||''), '; ') AS conversions
                          FROM %s c
                            %s
                     LEFT JOIN %s conv ON c.id = conv.process_config_id AND conv.in_unit <> conv.out_unit
                         WHERE coalesce(%s) IS NOT NULL
                      GROUP BY c.id
                             , c.name
                             , c.density
                             , c.avg_life_time
                             , c.min_life_time
                             , c.max_life_time
                             , c.life_time_info
                             , c.avg_life_time_info
                             , c.min_life_time_info
                             , c.max_life_time_info
                             , c.f_hs_hi
                             , %s
                      ORDER BY c.name ASC"
            , join(', ', $columns)
            , ElcaProcessConfig::TABLE_NAME
            , join(' ', $lftJoins)
            , ElcaProcessConversion::TABLE_NAME
            , join(', ', $idColumns)
            , join(', ', $grpColumns)
        );
        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findProcessAssignmentsByProcessDbId

}
// End ElcaProcessesViewSet