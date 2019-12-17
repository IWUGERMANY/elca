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
use Exception;

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
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaReportSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_REPORT_TOTAL_EFFECTS = 'elca_cache.report_total_effects_v';
    const VIEW_REPORT_TOTAL_EFFECTS_LC_USAGE = 'elca_cache.report_total_effects_lc_usage_v';
    const VIEW_REPORT_ELEMENT_TYPE_EFFECTS = 'elca_cache.report_element_type_effects_v';
    const VIEW_REPORT_LIFE_CYCLE_EFFECTS = 'elca_cache.report_life_cycle_effects_v';
    const VIEW_REPORT_ASSETS = 'elca_cache.report_assets_v';
    const VIEW_REPORT_ASSETS_NOT_CALCULATED = 'elca_cache.report_assets_not_calculated_v';
    const VIEW_REPORT_TOP_ASSETS = 'elca_cache.report_top_assets_v';
    const VIEW_REPORT_EFFECTS = 'elca_cache.report_effects_v';
    const VIEW_REPORT_FINAL_ENERGY_DEMAND_EFFECTS = 'elca_cache.report_final_energy_demand_effects_v';
    const VIEW_REPORT_FINAL_ENERGY_SUPPLY_EFFECTS = 'elca_cache.report_final_energy_supply_effects_v';
    const VIEW_REPORT_FINAL_ENERGY_DEMAND_ASSETS = 'elca_cache.report_final_energy_demand_assets_v';
    const VIEW_REPORT_FINAL_ENERGY_SUPPLY_ASSETS = 'elca_cache.report_final_energy_supply_assets_v';
    const VIEW_REPORT_TOP_PROCESS_CONFIGS = 'elca_cache.report_top_process_config_effects_v';
    const VIEW_REPORT_ELEMENT_PROCESS_CONFIGS = 'elca_cache.report_element_process_config_effects_v';
    const VIEW_REPORT_COMPOSITE_ELEMENT_PROCESS_CONFIGS = 'elca_cache.report_composite_element_process_config_effects_v';
    const VIEW_REF_PROJECT_CONSTRUCTION_EFFECTS = 'elca_cache.ref_project_construction_effects_v';
    const VIEW_PROJECT_VARIANT_PROCESS_CONFIG_MASS = 'elca_cache.project_variant_process_config_mass_v';
    const VIEW_REPORT_TRANSPORT_ASSETS = 'elca_cache.report_transport_assets_v';
    const VIEW_REPORT_TRANSPORT_EFFECTS = 'elca_cache.report_transport_effects_v';
    const VIEW_REPORT_COMPARE_TOTAL_AND_LIFE_CYCLE_EFFECTS = 'elca_cache.report_compare_total_and_life_cycle_effects_v';
    const VIEW_REPORT_CONSTRUCTION_TOTAL_EFFECTS = 'elca_cache.report_construction_total_effects_v';
    const VIEW_REPORT_FINAL_ENERGY_REF_MODEL_EFFECTS = 'elca_cache.report_final_energy_ref_model_effects_v';
    const VIEW_REPORT_TOTAL_CONSTRUCTION_RECYCLING_EFFECTS = 'elca_cache.report_total_construction_recycling_effects_v';
    const VIEW_REPORT_TOTAL_ENERGY_RECYCLING_EFFECTS = 'elca_cache.report_total_energy_recycling_potential';
	
	const TABLE_REPORT_PDF_QUEUE = 'elca.reports_pdf_queue';


    /**
     * Returns a list of construction assets
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findConstructionAssets($projectVariantId, $force = false)
    {
        $Project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        return self::_find(
            get_class(),
            self::VIEW_REPORT_ASSETS,
            array(
                'project_variant_id'             => $projectVariantId,
                'process_db_id'                  => $Project->getProcessDbId(),
                'element_type_is_constructional' => true,
            ),
            array(
                'element_type_din_code'      => 'ASC',
                'element_name'               => 'ASC',
                'component_is_layer'         => 'ASC',
                'component_layer_position'   => 'ASC',
                'process_life_cycle_p_order' => 'ASC',
            )
        );
    }
    // End findConstructionAssets


    /**
     * Returns a list of system assets
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findSystemAssets($projectVariantId, $force = false)
    {
        $Project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        return self::_find(
            get_class(),
            self::VIEW_REPORT_ASSETS,
            array(
                'project_variant_id'             => $projectVariantId,
                'process_db_id'                  => $Project->getProcessDbId(),
                'element_type_is_constructional' => false,
            ),
            array(
                'element_type_din_code'      => 'ASC',
                'element_name'               => 'ASC',
                'component_is_layer'         => 'ASC',
                'component_layer_position'   => 'ASC',
                'process_life_cycle_p_order' => 'ASC',
            )
        );
    }
    // End findSystemAssets


    /**
     * Returns a list of final energy demand assets
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findFinalEnergyDemandAssets($projectVariantId, $force = false)
    {
        $Project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        return self::_find(
            get_class(),
            self::VIEW_REPORT_FINAL_ENERGY_DEMAND_ASSETS,
            array(
                'project_variant_id' => $projectVariantId,
                'process_db_id'      => $Project->getProcessDbId(),
            ),
            array(
                'id'                 => 'ASC',
                'life_cycle_p_order' => 'ASC',
            )
        );
    }
    // End findFinalEnergyDemandAssets

    /**
     * Returns a list of final energy supply assets
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findFinalEnergySupplyAssets($projectVariantId, $force = false)
    {
        $Project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        return self::_find(
            get_class(),
            self::VIEW_REPORT_FINAL_ENERGY_SUPPLY_ASSETS,
            array(
                'project_variant_id' => $projectVariantId,
                'process_db_id'      => $Project->getProcessDbId(),
            ),
            array(
                'id'                 => 'ASC',
                'life_cycle_p_order' => 'ASC',
            )
        );
    }
    // End findFinalEnergySupplyAssets

    /**
     * Returns a list of transport assets
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findTransportAssets($projectVariantId, $force = false)
    {
        $Project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        return self::_find(
            get_class(),
            self::VIEW_REPORT_TRANSPORT_ASSETS,
            array(
                'project_variant_id' => $projectVariantId,
                'process_db_id'      => $Project->getProcessDbId(),
            ),
            array(
                'transport_mean_id'  => 'ASC',
                'life_cycle_p_order' => 'ASC',
            )
        );
    }
    // End findTransportAssets

    /**
     * Returns the top N of assets
     *
     * @param  int   $projectVariantId
     * @param bool   $inTotal
     * @param string $orderDir
     * @param int    $limit
     * @param bool   $force
     *
     * @return ElcaReportSet
     */
    public static function findTopNAssets(
        $projectVariantId,
        $inTotal = false,
        $orderDir = 'DESC',
        $limit = 10,
        $force = false
    ) {
        $Project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        $initValues = array(
            'projectVariantId' => $projectVariantId,
            'processDbId'      => $Project->getProcessDbId(),
            'lcPhase'          => ElcaLifeCycle::PHASE_PROD,
        );

        $sql = sprintf(
            "SELECT project_variant_id
                             , element_type_din_code
                             , element_type_name
                             , element_id
                             , element_name
                             , element_quantity
                             , element_ref_unit
                             , cache_element_quantity
                             , cache_element_ref_unit
                             , element_component_id
                             , process_db_id
                             , process_life_cycle_description
                             , process_name_orig
                             , process_ref_value
                             , process_ref_unit
                             , process_config_name 
                             , %s
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND process_db_id = :processDbId
                           AND process_life_cycle_phase = :lcPhase"
            ,
            $inTotal ? 'cache_component_mass'
                : 'CASE WHEN element_quantity > 0 THEN cache_component_mass / element_quantity ELSE 0 END AS cache_component_mass'
            ,
            self::VIEW_REPORT_TOP_ASSETS
        );

        if ($orderSql = self::buildOrderView(array('cache_component_mass' => $orderDir), $limit)) {
            $sql .= ' ' . $orderSql;
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findConstructionAssets


    /**
     * Returns a list of construction effects
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findConstructionEffects($projectVariantId, $force = false)
    {
        $initValues = array(
            'project_variant_id'             => $projectVariantId,
            'element_type_is_constructional' => true,
            'is_hidden'                      => false,
        );

        return self::_find(
            get_class(),
            self::VIEW_REPORT_EFFECTS,
            $initValues,
            array(
                'element_type_din_code' => 'ASC',
                'element_name'          => 'ASC',
                'indicator_p_order'     => 'ASC',
            ),
            $force
        );
    }
    // End findConstructionEffects


    /**
     * Returns a list of effects for all elements from the element catalog (composite and unassigned elements)
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findElementCatalogEffects($projectVariantId, $force = false)
    {
        $initValues = array('projectVariantId' => $projectVariantId);

        $sql = sprintf(
            'SELECT DISTINCT e.*
                          FROM %s e
                     LEFT JOIN %s c ON e.element_id = c.element_id
                         WHERE e.project_variant_id = :projectVariantId
                           AND e.is_hidden = false
                           AND e.element_type_is_constructional
                           AND c.composite_element_id IS NULL
                      ORDER BY e.element_type_din_code ASC
                             , element_name ASC
                             , indicator_p_order ASC'
            ,
            self::VIEW_REPORT_EFFECTS
            ,
            ElcaCompositeElement::TABLE_NAME
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findConstructionEffects


    /**
     * Returns a list of system effects
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findSystemEffects($projectVariantId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REPORT_EFFECTS,
            array(
                'project_variant_id'             => $projectVariantId,
                'element_type_is_constructional' => false,
                'is_hidden'                      => false,
            ),
            array(
                'element_type_din_code' => 'ASC',
                'element_name'          => 'ASC',
                'indicator_p_order'     => 'ASC',
            )
        );
    }
    // End findSystemEffects


    /**
     * Returns a list of final energy demand effects
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findFinalEnergyDemandEffects($projectVariantId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REPORT_FINAL_ENERGY_DEMAND_EFFECTS,
            array('project_variant_id' => $projectVariantId, 'is_hidden' => false),
            array(
                'id'                => 'ASC',
                'indicator_p_order' => 'ASC',
            )
        );
    }
    // End findFinalEnergyDemandEffects


    /**
     * Returns a list of final energy supply effects
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findFinalEnergySupplyEffects($projectVariantId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REPORT_FINAL_ENERGY_SUPPLY_EFFECTS,
            array('project_variant_id' => $projectVariantId, 'is_hidden' => false),
            array(
                'id'                => 'ASC',
                'indicator_p_order' => 'ASC',
            )
        );
    }
    // End findFinalEnergySupplyEffects


    /**
     * Returns a list of operation effects
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findTransportEffects($projectVariantId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REPORT_TRANSPORT_EFFECTS,
            array('project_variant_id' => $projectVariantId, 'is_hidden' => false),
            array(
                'transport_id'      => 'ASC',
                'indicator_p_order' => 'ASC',
            )
        );
    }
    // End findTransportEffects


    /**
     * Find data in PDF Queue
	 * @param  $projectVariantId
	 * @param  $projectId
	 * @param  $userId
	 * @param  $report_name
     * @return array
     */
    public static function findPdfInQueue($projectId, $projectVariantId,$userId, $report_name)
    {
		$initValues = array('project_variant_id' => $projectVariantId, 'project_id' => $projectId, 'user_id' => $userId, 'report_name' => $report_name);
        $sql = sprintf(
            'SELECT * FROM %s 
                    WHERE user_id = :user_id 
					AND	current_variant_id = :project_variant_id
					AND projects_id = :project_id
					AND report_name = :report_name'
            , self::TABLE_REPORT_PDF_QUEUE
        );
		// 	AND ready IS NOT NULL
        return self::_findBySql(get_class(), $sql, $initValues);		
    }	

    /**
     * Find data in PDF Queue
	 * @param  $projectVariantId
	 * @param  $projectId
	 * @param  $userId
	 * @param  $key
     * @return array
     */
    public static function findPdfInQueueByHash($projectId, $projectVariantId,$userId, $key_hash)
    {
		$initValues = array('project_variant_id' => $projectVariantId, 'project_id' => $projectId, 'user_id' => $userId, 'key' => $key_hash);
        $sql = sprintf(
            'SELECT * FROM %s 
                    WHERE user_id = :user_id 
					AND	current_variant_id = :project_variant_id
					AND projects_id = :project_id
					AND key = :key'
            , self::TABLE_REPORT_PDF_QUEUE
        );
		// 	AND ready IS NOT NULL
        return self::_findBySql(get_class(), $sql, $initValues);		
    }	

	/**
     * Save data in PDF queue
     */
    public static function setPdfInQueue(array $initValues)
    {
		$PDFinfo = self::findPdfInQueue($initValues['projects_id'], $initValues['current_variant_id'],$initValues['user_id'], $initValues['report_name']);
		
		if( $PDFinfo->isEmpty() )
		{
			$sql = sprintf(
				'INSERT INTO %s (user_id,projects_id,report_name,projects_filename,current_variant_id,pdf_cmd,key)
				 VALUES  (:user_id, :projects_id, :report_name, :projects_filename, :current_variant_id, :pdf_cmd, :key)'
				,
				self::TABLE_REPORT_PDF_QUEUE
			);
		}	
		else
		{
			$infoArrayKey = (array)$PDFinfo[0]->key;
			$initValues['key'] = $infoArrayKey[0];
			
			$sql = sprintf(
				'UPDATE %s set projects_filename=:projects_filename, pdf_cmd=:pdf_cmd, key=:key, ready=NULL, created=NOW()
				WHERE user_id=:user_id
				AND	projects_id=:projects_id
				AND report_name=:report_name
				AND current_variant_id=:current_variant_id
				AND key=:key',
				self::TABLE_REPORT_PDF_QUEUE
			);
		}	
	
		$Stmt = self::prepareStatement($sql,$initValues ); 
		if (!$Stmt->execute()) {
			throw new \Exception(self::getSqlErrorMessage($sql, $initValues));
        }
        return true;
    }
	
	
	/**
     * Save data in PDF queue
     */
    public static function setPdfReadyInQueue($initValues)
    {
		$sql = sprintf(
				'UPDATE %s set ready=NOW()
				WHERE user_id=:user_id
				AND	projects_id=:projects_id
				AND report_name=:report_name
				AND current_variant_id=:current_variant_id',
				self::TABLE_REPORT_PDF_QUEUE
		);

		$Stmt = self::prepareStatement($sql,$initValues ); 
		if (!$Stmt->execute()) {
			throw new \Exception(self::getSqlErrorMessage($sql, $initValues));
        }
        return true;
    }	

    /**
     * Create PDF - query Queue in runner.php Task
     * @return array
     */
    public static function createPdfInQueue()
    {
		$sql = sprintf(
            'SELECT * FROM %s 
                    WHERE ready IS NULL' 
            , self::TABLE_REPORT_PDF_QUEUE
        );
		// 	AND ready IS NOT NULL
        return self::_findBySql(get_class(), $sql, null);		
    }	


    /**
     * Returns a list of top n elements
     *
     * @param  int   $projectVariantId
     * @param        $indicatorId
     * @param bool   $inTotal
     * @param string $orderDir
     * @param int    $limit
     * @param bool   $force
     *
     * @return ElcaReportSet
     */
    public static function findTopNEffects(
        $projectVariantId,
        $indicatorId,
        $inTotal = false,
        $orderDir = 'DESC',
        $limit = 10,
        $force = false
    ) {
        $initValues = array(
            'projectVariantId' => $projectVariantId,
            'indicatorId'      => $indicatorId,
            'lcPhase'          => ElcaLifeCycle::PHASE_TOTAL,
        );

        $sql = sprintf(
            "SELECT     c.element_id
                             , c.project_variant_id
                             , c.element_name
                             , c.element_ref_unit
                             , c.element_type_node_id
                             , c.element_type_din_code
                             , c.element_type_name
                             , c.element_type_is_constructional
                             , c.element_type_parent_name
                             , c.element_type_parent_din_code
                             , c.life_cycle_phase
                             , c.indicator_id
                             , c.indicator_name
                             , c.indicator_unit
                             , c.indicator_p_order
                             , e.id AS composite_element_id
                             , e.name AS composite_element_name
                             , %s
                             , %s
                          FROM %s c
                          LEFT JOIN %s ce ON c.element_id = ce.element_id
                          LEFT JOIN %s e ON e.id = ce.composite_element_id
                         WHERE c.project_variant_id = :projectVariantId
                           AND c.life_cycle_phase = :lcPhase
                           AND c.indicator_id = :indicatorId
                           AND c.is_hidden = false
                           AND c.is_composite = false"
            ,$inTotal ? 'indicator_value'
                : 'CASE WHEN element_quantity > 0 THEN indicator_value / element_quantity ELSE 0 END AS indicator_value'
            ,$inTotal
                ? 'c.element_quantity'
                : '1 AS element_quantity'
            , self::VIEW_REPORT_EFFECTS
            , ElcaCompositeElement::TABLE_NAME
            , ElcaElement::TABLE_NAME
        );

        if ($orderSql = self::buildOrderView(
            array(
                'indicator_value'       => $orderDir,
                'element_type_din_code' => 'ASC',
                'element_name'          => 'ASC',
            ),
            $limit
        )
        ) {
            $sql .= ' ' . $orderSql;
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findConstructionEffects


    /**
     * Returns a list of top n elements
     *
     * @param  int   $projectVariantId
     * @param        $indicatorId
     * @param string $orderDir
     * @param int    $limit
     * @param bool   $force
     *
     * @return ElcaReportSet
     */
    public static function findTopNProcessConfigEffects(
        $projectVariantId,
        $indicatorId,
        $orderDir = 'DESC',
        $limit = 10,
        $force = false
    ) {
        $initValues = array(
            'projectVariantId' => $projectVariantId,
            'indicatorId'      => $indicatorId,
        );

        $sql = sprintf(
            "SELECT project_variant_id
                             , process_config_id
                             , process_config_name
                             , indicator_id
                             , life_cycle_phase
                             , indicator_name
                             , indicator_unit
                             , indicator_p_order
                             , quantity
                             , indicator_value
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND is_hidden = false
                           AND indicator_id = :indicatorId"
            ,
            self::VIEW_REPORT_TOP_PROCESS_CONFIGS
        );

        if ($orderSql = self::buildOrderView(array('indicator_value' => $orderDir), $limit)) {
            $sql .= ' ' . $orderSql;
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findConstructionEffects


    /**
     * Returns a list of element process configs
     *
     * @param      $elementId
     * @param null $indicatorId
     * @param bool $aggregated
     * @param bool $includeTotals
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findElementProcessConfigEffects(
        $elementId,
        $indicatorId = null,
        $aggregated = false,
        $includeTotals = false,
        $force = false
    ) {
        $initValues = array('elementId' => $elementId);

        if ($indicatorId) {
            $initValues['indicatorId'] = $indicatorId;
        }

        $sql = 'SELECT element_id
                     , process_config_id
                     , process_config_name
                     , indicator_id
                     , life_cycle_phase
                     , life_cycle_ident
                     , life_cycle_name
                     , indicator_name
                     , indicator_ident
                     , indicator_unit
                     , indicator_p_order
                     , ref_unit';

        if ($aggregated) {
            $sql .= ', sum(quantity) AS quantity
                     , sum(indicator_value) AS indicator_value
                     , bool_and(is_extant) AS is_extant';
        } else {
            $sql .= ', element_component_id
                     , is_extant
                     , is_layer
                     , layer_position
                     , layer_area_ratio
                     , quantity
                     , indicator_value';
        }

        $sql .= sprintf(
            " FROM %s
                         WHERE element_id = :elementId AND is_hidden = false"
            ,
            self::VIEW_REPORT_ELEMENT_PROCESS_CONFIGS
        );

        if ($indicatorId) {
            $sql .= ' AND indicator_id = :indicatorId';
        }

        if (!$includeTotals) {
            $sql .= ' AND life_cycle_phase <> \'' . ElcaLifeCycle::PHASE_TOTAL . '\'';
        }

        if ($aggregated) {
            $sql .= ' GROUP BY element_id
                             , process_config_id
                             , process_config_name
                             , indicator_id
                             , life_cycle_phase
                             , life_cycle_ident
                             , life_cycle_name
                             , indicator_name
                             , indicator_ident
                             , indicator_unit
                             , indicator_p_order
                             , ref_unit
                      ORDER BY process_config_name
                             , life_cycle_phase DESC
                             , indicator_p_order';
        } else {
            $sql .= ' ORDER BY layer_position, life_cycle_phase DESC, indicator_p_order';
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findElementProcessConfigEffects


    /**
     * Returns a list of element process configs
     *
     * @param      $compositeElementId
     * @param bool $indicatorId
     * @param bool $aggregated
     * @param bool $includeTotals
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findCompositeElementProcessConfigEffects(
        $compositeElementId,
        $indicatorId = false,
        $aggregated = false,
        $includeTotals = false,
        $force = false
    ) {
        $initValues = array('compositeElementId' => $compositeElementId);

        if ($indicatorId) {
            $initValues['indicatorId'] = $indicatorId;
        }

        $sql = 'SELECT composite_element_id
                     , process_config_id
                     , process_config_name
                     , indicator_id
                     , life_cycle_phase
                     , life_cycle_ident
                     , life_cycle_name
                     , indicator_name
                     , indicator_ident
                     , indicator_unit
                     , indicator_p_order
                     , ref_unit';

        if ($aggregated) {
            $sql .= ', sum(quantity) AS quantity
                     , sum(indicator_value) AS indicator_value
                     , bool_and(is_extant) AS is_extant';
        } else {
            $sql .= ', element_id
                     , element_name
                     , element_component_id
                     , is_extant
                     , is_layer
                     , layer_position
                     , layer_area_ratio
                     , quantity
                     , indicator_value';
        }

        $sql .= sprintf(
            " FROM %s
                        WHERE composite_element_id = :compositeElementId
                         AND is_hidden = false"
            ,
            self::VIEW_REPORT_COMPOSITE_ELEMENT_PROCESS_CONFIGS
        );

        if ($indicatorId) {
            $sql .= ' AND indicator_id = :indicatorId';
        }

        if (!$includeTotals) {
            $sql .= ' AND life_cycle_phase <> \'' . ElcaLifeCycle::PHASE_TOTAL . '\'';
        }


        if ($aggregated) {
            $sql .= ' GROUP BY composite_element_id
                             , process_config_id
                             , process_config_name
                             , indicator_id
                             , life_cycle_phase
                             , life_cycle_ident
                             , life_cycle_name
                             , indicator_name
                             , indicator_ident
                             , indicator_unit
                             , indicator_p_order
                             , ref_unit
                       ORDER BY process_config_name
                             , life_cycle_phase DESC
                             , indicator_p_order';
        } else {
            $sql .= ' ORDER BY element_id, layer_position, life_cycle_phase DESC, indicator_p_order';
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findCompositeElementProcessConfigEffects


    /**
     * Returns a list of total effects
     *
     * @param      $projectVariantId
     * @param bool $force
     *
     * @return DataObjectSet
     */
    public static function findTotalEffects($projectVariantId, $onlyHiddenIndicators = false, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REPORT_TOTAL_EFFECTS,
            [
                'project_variant_id' => $projectVariantId,
                'is_hidden'          => $onlyHiddenIndicators,
            ],
            ['indicator_p_order' => 'ASC'],
            null,
            null,
            $force
        );
    }
    // End findTotalEffects


    /**
     * Returns a list of constructional recycling effects
     *
     * @param      $projectVariantId
     * @param bool $force
     * @return DataObjectSet
     */
    public static function findTotalConstructionRecyclingEffects($projectVariantId, $onlyHiddenIndicators = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REPORT_TOTAL_CONSTRUCTION_RECYCLING_EFFECTS,
            ['project_variant_id' => $projectVariantId, 'is_hidden' => $onlyHiddenIndicators],
            ['indicator_p_order' => 'ASC']
        );
    }
    // End findTotalEffects


    /**
     * Returns a list of constructional recycling effects
     *
     * @param      $projectVariantId
     * @param bool $force
     * @return DataObjectSet
     */
    public static function findTotalEnergyRecyclingEffects($projectVariantId, $onlyHiddenIndicators = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REPORT_TOTAL_ENERGY_RECYCLING_EFFECTS,
            array('project_variant_id' => $projectVariantId, 'is_hidden' => $onlyHiddenIndicators),
            ['indicator_p_order' => 'ASC']
        );
    }
    // End findTotalEffects

    /**
     * Returns a list of level1-3 element type effects
     *
     * @param          $projectVariantId
     * @param array    $lcPhases
     * @param null     $indicatorId
     * @param null     $parentElementTypeNodeId
     * @param int|null $maxLevel
     * @param int      $minLevel
     * @param bool     $force
     * @return DataObjectSet
     */
    public static function findEffectsPerElementType(
        $projectVariantId,
        array $lcPhases = null,
        $indicatorId = null,
        $parentElementTypeNodeId = null,
        $maxLevel = 3,
        $minLevel = 1,
        $force = false
    ) {
        $initValues['projectVariantId'] = $projectVariantId;
        $initValues['minLevel']         = (int)$minLevel;
        $initValues['maxLevel']         = (int)$maxLevel;

        if ($indicatorId) {
            $initValues['indicatorId'] = $indicatorId;
        }

        $lcSql = null;
        if ($lcPhases) {
            $pieces = array();
            foreach ($lcPhases as $index => $phase) {
                $initValues['lcPhase' . $index] = $phase;
                $pieces[]                       = ':lcPhase' . $index;
            }
            $lcSql = join(', ', $pieces);
        }

        if ($parentElementTypeNodeId) {
            $initValues['parentElementTypeNodeId'] = $parentElementTypeNodeId;
        }


        $sql = sprintf(
            'SELECT *
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND is_hidden = false 
                           AND level BETWEEN :minLevel AND :maxLevel
                           %s %s %s
                      ORDER BY din_code
                             , life_cycle_phase DESC
                             , indicator_p_order'
            ,
            self::VIEW_REPORT_ELEMENT_TYPE_EFFECTS
            ,
            $indicatorId ? 'AND indicator_id = :indicatorId' : ''
            ,
            $lcSql ? ' AND life_cycle_phase IN (' . $lcSql . ')' : ''
            ,
            $parentElementTypeNodeId ? ' AND parent_element_type_node_id = :parentElementTypeNodeId' : ''
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }
    // End findEffectsPerElementType


    /**
     * Returns a list of life cycle effects
     *
     * @param  int  $projectVariantId
     * @param array $initValues
     * @param array $orderBy
     * @param bool  $force
     *
     * @return ElcaReportSet
     */
    public static function findTotalEffectsPerLifeCycle(
        $projectVariantId,
        array $initValues = array(),
        array $orderBy = null,
        $force = false
    ) {
        $initValues['project_variant_id'] = $projectVariantId;

        if (!isset($initValues['is_hidden'])) {
            $initValues['is_hidden'] = false;
        }

        return self::_find(
            get_class(),
            self::VIEW_REPORT_LIFE_CYCLE_EFFECTS,
            $initValues,
            array('life_cycle_p_order' => 'ASC', 'indicator_p_order' => 'ASC')
        );
    }

    /**
     * Returns a filtered list of life cycle effects
     *
     * @param  int  $projectVariantId
     * @param array $lifeCycleIdents
     * @param bool  $force
     *
     * @return ElcaReportSet
     */
    public static function findTotalEffectsPerLifeCycleFiltered(
        $projectVariantId,
        array $lifeCycleIdents,
        $force = false
    ) {
        if (!count($lifeCycleIdents)) {
            return new static();
        }

        $initValues['project_variant_id'] = $projectVariantId;

        $filter = [];
        foreach ($lifeCycleIdents as $ident) {
            $name              = str_replace('-', '', $ident);
            $initValues[$name] = $ident;
            $filter[]          = ':' . $name;
        }
        $filterSql = implode(', ', $filter);

        $sql = sprintf(
            'SELECT *
                          FROM %s
                         WHERE ident IN (%s)
                         ORDER BY life_cycle_p_order, indicator_p_order',
            self::VIEW_REPORT_LIFE_CYCLE_EFFECTS,
            $filterSql
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }

    /**
     * Returns a list of level1-3 element type effects
     *
     * @param       $projectVariantAId
     * @param       $projectVariantBId
     * @param array $lcPhases
     * @param null  $indicatorId
     * @param null  $parentElementTypeNodeId
     * @param null  $maxLevel
     * @param bool  $force
     *
     * @return ElcaReportSet
     */
    public static function findComparisonEffectsPerElementTypes(
        $projectVariantAId,
        $projectVariantBId,
        array $lcPhases = null,
        $indicatorId = null,
        $parentElementTypeNodeId = null,
        $maxLevel = 3,
        $minLevel = 1,
        $force = false
    ) {
        $reportSetA = self::findEffectsPerElementType(
            $projectVariantAId,
            $lcPhases,
            $indicatorId,
            $parentElementTypeNodeId,
            $maxLevel,
            $minLevel,
            $force
        );
        $reportSetB = self::findEffectsPerElementType(
            $projectVariantBId,
            $lcPhases,
            $indicatorId,
            $parentElementTypeNodeId,
            $maxLevel,
            $minLevel,
            $force
        );

        $map = array();
        foreach ($reportSetA as $effect) {
            $dataObject                              = $map[$effect->category][$effect->life_cycle_ident][$effect->indicator_id] = new \stdClass(
            );
            $dataObject->project_variant_a_id        = $effect->project_variant_id;
            $dataObject->project_variant_b_id        = null;
            $dataObject->value_a                     = $effect->value;
            $dataObject->value_b                     = null;
            $dataObject->life_cycle_phase            = $effect->life_cycle_phase;
            $dataObject->life_cycle_ident            = $effect->life_cycle_ident;
            $dataObject->life_cycle_name             = $effect->life_cycle_name;
            $dataObject->indicator_id                = $effect->indicator_id;
            $dataObject->name                        = $effect->name;
            $dataObject->unit                        = $effect->unit;
            $dataObject->category                    = $effect->category;
            $dataObject->din_code                    = $effect->din_code;
            $dataObject->element_type_node_id        = $effect->element_type_node_id;
            $dataObject->parent_element_type_node_id = $effect->parent_element_type_node_id;
        }

        foreach ($reportSetB as $effect) {
            if (isset($map[$effect->category][$effect->life_cycle_ident][$effect->indicator_id])) {
                $dataObject = $map[$effect->category][$effect->life_cycle_ident][$effect->indicator_id];
            } else {
                $dataObject = $map[$effect->category][$effect->life_cycle_ident][$effect->indicator_id] = new \stdClass();
            }

            $dataObject->project_variant_b_id        = $effect->project_variant_id;
            $dataObject->value_b                     = $effect->value;
            $dataObject->life_cycle_phase            = $effect->life_cycle_phase;
            $dataObject->life_cycle_ident            = $effect->life_cycle_ident;
            $dataObject->life_cycle_name             = $effect->life_cycle_name;
            $dataObject->indicator_id                = $effect->indicator_id;
            $dataObject->name                        = $effect->name;
            $dataObject->unit                        = $effect->unit;
            $dataObject->category                    = $effect->category;
            $dataObject->din_code                    = $effect->din_code;
            $dataObject->element_type_node_id        = $effect->element_type_node_id;
            $dataObject->parent_element_type_node_id = $effect->parent_element_type_node_id;

            if (!isset($dataObject->value_a)) {
                $dataObject->value_a = $dataObject->project_variant_a_id = null;
            }
        }

        $reportSet = new ElcaReportSet();
        foreach ($map as $category => $stages) {
            foreach ($stages as $indicators) {
                foreach ($indicators as $dataObject) {
                    $reportSet->add($dataObject);
                }
            }
        }

        return $reportSet;
    }
    // End findComparisonEffectsPerElementTypes


    /**
     * Returns a list of life cycle effects
     *
     * @param      $projectVariantAId
     * @param      $projectVariantBId
     * @param null $indicatorId
     * @param bool $force
     *
     * @throws Exception
     * @return ElcaReportSet
     */
    public static function findComparisonTotalEffectsPerLifeCycle(
        $projectVariantAId,
        $projectVariantBId,
        $indicatorId = null,
        $force = false
    ) {
        $initValues['projectVariantAId'] = $projectVariantAId;
        $initValues['projectVariantBId'] = $projectVariantBId;

        if ($indicatorId) {
            $initValues['indicatorId'] = $indicatorId;
        }

        $sql = sprintf(
            'SELECT *
                          FROM %s
                         WHERE project_variant_a_id = :projectVariantAId
                           AND project_variant_b_id = :projectVariantBId
                           AND is_hidden = false
                            %s
                      ORDER BY life_cycle_p_order
                             , indicator_p_order'
            ,
            self::VIEW_REPORT_COMPARE_TOTAL_AND_LIFE_CYCLE_EFFECTS
            ,
            $indicatorId ? 'AND indicator_id = :indicatorId' : ''
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }
    // End findComparisonTotalEffectsPerLifeCycle


    /**
     * Returns a list of life cycle effects
     *
     * @param      $projectVariantAId
     * @param      $projectVariantBId
     * @param null $indicatorId
     * @param bool $force
     *
     * @throws Exception
     * @return ElcaReportSet
     */
    public static function findComparisonTotalEffectsPerLifeCyclePhase(
        $projectVariantAId,
        $projectVariantBId,
        $indicatorId = null,
        $force = false
    ) {
        $initValues['projectVariantAId'] = $projectVariantAId;
        $initValues['projectVariantBId'] = $projectVariantBId;

        if ($indicatorId) {
            $initValues['indicatorId'] = $indicatorId;
        }

        $sql = sprintf(
            'SELECT project_variant_a_id
                             , project_variant_b_id
                             , indicator_id
                             , sum(value_a)
                             , sum(value_b)
                             , name
                             , ident
                             , unit
                             , indicator_p_order
                             , l.phase AS life_cycle_phase
                             , life_cycle_p_order
                          FROM %s
                         WHERE project_variant_a_id = :projectVariantAId
                           AND project_variant_b_id = :projectVariantBId
                            %s
                      ORDER BY life_cycle_p_order
                             , indicator_p_order'
            ,
            self::VIEW_REPORT_COMPARE_TOTAL_AND_LIFE_CYCLE_EFFECTS
            ,
            $indicatorId ? 'AND indicator_id = :indicatorId' : ''
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }
    // End findComparisonTotalEffectsPerLifeCycle


    /**
     * Finds effects totals for constructions per project variant
     *
     * @param      $projectVariantId
     * @param null $indicatorId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findConstructionTotalEffects($projectVariantId, $indicatorId = null, $force = false)
    {
        if (!$projectVariantId) {
            return new ElcaReportSet();
        }

        $initValues = array('project_variant_id' => $projectVariantId);

        if ($indicatorId) {
            $initValues['indicator_id'] = $indicatorId;
        }

        return self::_find(
            get_class(),
            self::VIEW_REPORT_CONSTRUCTION_TOTAL_EFFECTS,
            $initValues,
            array('indicator_id' => 'ASC')
        );
    }
    // End findConstructionTotalEffects


    /**
     * Finds indicator benchmarks over all reference projects
     *
     * @param null $processDbId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findRefProjectConstructionEffects($benchmarkVersionId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_REF_PROJECT_CONSTRUCTION_EFFECTS,
            ['benchmark_version_id' => $benchmarkVersionId],
            array('indicator_id' => 'ASC'),
            null,
            null,
            $force
        );
    }
    // End findRefProjectConstructionEffects


    /**
     * Finds final energy ref model effects
     *
     * @param      $projectVariantId
     * @param null $indicatorId
     * @param bool $force
     *
     * @return ElcaReportSet
     */
    public static function findFinalEnergyRefModelEffects($projectVariantId, $indicatorId = null, $force = false)
    {
        if (!$projectVariantId) {
            return new ElcaReportSet();
        }

        $initValues = array('project_variant_id' => $projectVariantId);

        if ($indicatorId) {
            $initValues['indicator_id'] = $indicatorId;
        }

        return self::_find(
            get_class(),
            self::VIEW_REPORT_FINAL_ENERGY_REF_MODEL_EFFECTS,
            $initValues,
            array('indicator_id' => 'ASC')
        );
    }
    // End findFinalEnergyRefModelEffects


    /**
     * @param            $projectVariantId
     * @param bool|false $force
     *
     * @return ElcaReportSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function findNonDefaultLifeTimeAssets($projectVariantId, $limit = null, $force = false)
    {
        $project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        $sql = sprintf(
            'SELECT *
                         FROM %s
                        WHERE project_variant_id = :projectVariantId
                          AND process_db_id = :processDbId
                          AND has_non_default_life_time = true
                        ORDER BY element_type_din_code
                               , component_is_layer
                               , component_layer_position
                               , process_life_cycle_p_order'
            ,
            self::VIEW_REPORT_ASSETS
        );

        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }

        return self::_findBySql(
            get_class(),
            $sql,
            [
                'projectVariantId' => $projectVariantId,
                'processDbId'      => $project->getProcessDbId(),
            ],
            $force
        );
    }

    /**
     * @param            $projectVariantId
     * @param bool|false $force
     *
     * @return ElcaReportSet
     * @throws \Beibob\Blibs\Exception
     */
    public static function findNotCalculatedComponents($projectVariantId, $limit = null, $force = false)
    {
        $project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        $sql = sprintf(
            'SELECT *
                         FROM %s
                        WHERE project_variant_id = :projectVariantId
                          AND process_db_id = :processDbId
                        ORDER BY element_type_din_code
                               , component_is_layer
                               , component_layer_position
                               , process_life_cycle_p_order'
            ,
            self::VIEW_REPORT_ASSETS_NOT_CALCULATED
        );

        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }

        return self::_findBySql(
            get_class(),
            $sql,
            [
                'projectVariantId' => $projectVariantId,
                'processDbId'      => $project->getProcessDbId(),
            ],
            $force
        );
    }

    /**
     * @param            $projectVariantId
     * @param bool|false $force
     *
     * @return int
     */
    public static function countNonDefaultLifeTimeAssets($projectVariantId, $force = false)
    {
        $project = ElcaProjectVariant::findById($projectVariantId)->getProject();

        $sql = sprintf(
            'SELECT count(DISTINCT element_component_id) AS counter
                         FROM %s
                        WHERE project_variant_id = :projectVariantId
                          AND process_db_id = :processDbId
                          AND has_non_default_life_time = true'
            ,
            self::VIEW_REPORT_ASSETS
        );

        return self::_countBySql(
            get_class(),
            $sql,
            [
                'projectVariantId' => $projectVariantId,
                'processDbId'      => $project->getProcessDbId(),
            ],
            'counter',
            $force
        );
    }

    /**
     * @param       $processDbId
     * @param       $projectVariantId
     * @param array $filterLifeCyclePhases
     * @param bool  $force
     * @return DataObjectSet
     */
    public static function countEpdSubTypes(
        $processDbId,
        $projectVariantId,
        array $filterLifeCyclePhases = [],
        $force = false
    ) {
        if (!$processDbId || !$projectVariantId) {
            return new self();
        }

        $initValues = [
            'processDbId'      => $processDbId,
            'projectVariantId' => $projectVariantId,
        ];

        list($initValues, $filterSql) = self::filterLifeCyclePhases($filterLifeCyclePhases, $initValues);

        $sql = sprintf(
            'SELECT a.epd_type AS epd_type
                             , count(*) AS count
                             , count(DISTINCT a.id) AS distinct_count
                          FROM %s e
                          JOIN %s c ON e.id = c.element_id
                          JOIN %s a ON a.process_config_id = c.process_config_id
                         WHERE e.project_variant_id = :projectVariantId
                           AND a.process_db_id = :processDbId
                            %s
                      GROUP BY a.epd_type
                      ORDER BY a.epd_type',
            ElcaElement::TABLE_NAME,
            ElcaElementComponent::TABLE_NAME,
            ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS,
            $filterSql
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }

    /**
     * @param       $processDbId
     * @param       $projectVariantId
     * @param array $filterLifeCyclePhases
     * @param bool  $force
     * @return DataObjectSet
     */
    public static function countEpdSubTypesPerLifeCycle(
        $processDbId,
        $projectVariantId,
        array $filterLifeCyclePhases = [],
        $force = false
    ) {
        if (!$processDbId || !$projectVariantId) {
            return new self();
        }

        $initValues = [
            'processDbId'      => $processDbId,
            'projectVariantId' => $projectVariantId,
        ];

        list($initValues, $filterSql) = self::filterLifeCyclePhases($filterLifeCyclePhases, $initValues);

        $sql = sprintf(
            'SELECT a.epd_type AS epd_type
                             , a.life_cycle_phase
                             , a.life_cycle_ident
                             , a.life_cycle_p_order
                             , count(*) AS count
                             , count(DISTINCT a.id) AS distinct_count
                          FROM %s e
                          JOIN %s c ON e.id = c.element_id
                          JOIN %s a ON a.process_config_id = c.process_config_id
                          JOIN %s v ON v.id = e.project_variant_id
                          JOIN %s lu ON lu.project_id = v.project_id AND lu.life_cycle_ident = a.life_cycle_ident
                         WHERE e.project_variant_id = :projectVariantId
                           AND a.process_db_id = :processDbId
                            %s
                      GROUP BY a.epd_type
                             , a.life_cycle_phase
                             , a.life_cycle_ident
                             , a.life_cycle_p_order
                      ORDER BY a.life_cycle_p_order',
            ElcaElement::TABLE_NAME,
            ElcaElementComponent::TABLE_NAME,
            ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS,
            ElcaProjectVariant::TABLE_NAME,
            ElcaProjectLifeCycleUsage::TABLE_NAME,
            $filterSql
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }

    /**
     * @param $projectVariantId
     * @return DataObjectSet
     */
    public static function totalIndicatorEffectsPerLifeCycleAndEpdType($projectVariantId, $force = false)
    {
        if (!$projectVariantId) {
            return new self();
        }

        $initValues = [
            'projectVariantId' => $projectVariantId,
        ];
        $sql        = sprintf(
            'SELECT *
                          FROM (
                              SELECT
                                  p.epd_type
                                  , i.indicator_id
                                  , lc.p_order AS life_cycle_p_order
                                  , lc.phase AS life_cycle_phase
                                  , i.life_cycle_ident
                                  , sum(i.value) AS value
                              FROM %s cc
                                  JOIN %s i ON i.item_id = cc.item_id
                                  JOIN %s lc ON lc.ident = i.life_cycle_ident
                                  JOIN %s p ON p.id = i.process_id
                                  JOIN %s c ON c.id = cc.element_component_id
                                  JOIN %s e ON e.id = c.element_id
                                  JOIN %s v ON v.id = e.project_variant_id
                                  JOIN %s lu
                                     ON lu.project_id = v.project_id AND lu.life_cycle_ident = i.life_cycle_ident
                              WHERE e.project_variant_id = :projectVariantId
                                    AND true IN (lu.use_in_construction, lu.use_in_energy_demand)
                              GROUP BY p.epd_type, lc.p_order, lc.phase, i.life_cycle_ident, i.indicator_id
                              UNION
                              SELECT
                                  p.epd_type
                                  , i.indicator_id
                                  , 10000 AS life_cycle_p_order
                                  , \'maint\'::varchar AS life_cycle_phase
                                  , i.life_cycle_ident
                                  , sum(i.value * cc.num_replacements) AS value
                                  FROM %s cc
                                  JOIN %s i ON i.item_id = cc.item_id
                                  JOIN %s p ON p.id = i.process_id
                                  JOIN %s c ON c.id = cc.element_component_id
                                  JOIN %s e ON e.id = c.element_id
                                  JOIN %s v ON v.id = e.project_variant_id
                                  JOIN %s lu
                                     ON lu.project_id = v.project_id AND lu.life_cycle_ident = i.life_cycle_ident
                              WHERE e.project_variant_id = :projectVariantId
                                    AND lu.use_in_maintenance
                              GROUP BY p.epd_type, i.life_cycle_ident, i.indicator_id
                          ) x
                          ORDER BY life_cycle_p_order, epd_type, life_cycle_ident, indicator_id',
            ElcaCacheElementComponent::TABLE_NAME,
            ElcaCacheIndicator::TABLE_NAME,
            ElcaLifeCycle::TABLE_NAME,
            ElcaProcess::TABLE_NAME,
            ElcaElementComponent::TABLE_NAME,
            ElcaElement::TABLE_NAME,
            ElcaProjectVariant::TABLE_NAME,
            ElcaProjectLifeCycleUsage::TABLE_NAME,
            ElcaCacheElementComponent::TABLE_NAME,
            ElcaCacheIndicator::TABLE_NAME,
            ElcaProcess::TABLE_NAME,
            ElcaElementComponent::TABLE_NAME,
            ElcaElement::TABLE_NAME,
            ElcaProjectVariant::TABLE_NAME,
            ElcaProjectLifeCycleUsage::TABLE_NAME
        );

        return self::_findBySql(
            get_class(),
            $sql,
            $initValues,
            $force
        );
    }


    /**
     * @param array $filterLifeCyclePhases
     * @param       $initValues
     * @return array
     */
    protected static function filterLifeCyclePhases(array $filterLifeCyclePhases, $initValues)
    {
        $filters = [];
        foreach ($filterLifeCyclePhases as $phase) {
            $initValues[$phase] = $phase;
            $filters[]          = ':' . $phase;
        }

        $filterSql = '';
        if (count($filters)) {
            $filterSql = 'AND a.life_cycle_phase IN (' . implode(',', $filters) . ')';
        }

        return [$initValues, $filterSql];
    }
	
}
// End ElcaReportSet
