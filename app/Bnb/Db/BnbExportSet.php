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

namespace Bnb\Db;

use Beibob\Blibs\DataObjectSet;
use Elca\Db\ElcaCacheElementComponent;
use Elca\Db\ElcaCacheFinalEnergyDemand;
use Elca\Db\ElcaCacheIndicator;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectVariant;
use Exception;

/**
 * Builds report views
 *
 * @package bnb
 * @author  Tobias Lode <tobias@beibob.de>
 */
class BnbExportSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_EXPORT_TOTAL_EFFECTS = 'bnb.export_total_effects_v';
    const VIEW_EXPORT_TOTAL_ELEMENT_TYPE_EFFECTS = 'bnb.export_total_element_type_effects_v';
    const VIEW_EXPORT_LIFE_CYCLE_EFFECTS = 'bnb.export_life_cycle_effects_v';


    /**
     * Returns a list of total effects
     *
     * @param      $projectVariantId
     * @param bool $force
     *
     * @return BnbExportSet
     */
    public static function findTotalEffects($projectVariantId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_EXPORT_TOTAL_EFFECTS,
            ['project_variant_id' => $projectVariantId],
            ['indicator_p_order' => 'ASC'],
            $force
        );
    }
    // End findTotalEffects


    /**
     * Returns a list of element type effects
     *
     * @param  int $projectVariantId
     * @param bool $force
     *
     * @return BnbExportSet
     */
    public static function findTotalEffectsPerElementType($projectVariantId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_EXPORT_TOTAL_ELEMENT_TYPE_EFFECTS,
            ['project_variant_id' => $projectVariantId],
            ['din_code' => 'ASC', 'indicator_p_order' => 'ASC'],
            $force
        );
    }
    // End findTotalEffectsPerElementType


    /**
     * Returns a list of life cycle effects
     *
     * @param int  $itemId
     * @param      $indicatorId
     * @param bool $force
     *
     * @return BnbExportSet
     */
    public static function findEffectsPerLifeCycleByItemId($itemId, $indicatorId, $force = false)
    {
        return self::_find(
            get_class(),
            self::VIEW_EXPORT_LIFE_CYCLE_EFFECTS,
            ['item_id' => $itemId, 'indicator_id' => $indicatorId],
            ['life_cycle_p_order' => 'ASC', 'indicator_p_order' => 'ASC'],
            $force
        );
    }
    // End findEffectsPerLifeCycle

    /**
     * Finds all elements for csv exports
     *
     * @param ElcaProjectVariant $ProjectVariant
     * @param  boolean           $force - Bypass caching
     *
     * @throws Exception
     * @internal param ElcaProcessDb $ProcessDb
     * @return BnbExportSet
     */
    public static function findElementsForCsvExport(ElcaProjectVariant $ProjectVariant, $force = false)
    {
        if (!$ProjectVariant->isInitialized()) {
            return new BnbExportSet();
        }

        $ProcessDb                      = $ProjectVariant->getProject()->getProcessDb();
        $Indicators                     = ElcaIndicatorSet::findByProcessDbId($ProcessDb->getId());
        $firstIndicatorId               = $Indicators[0]->getId();
        $initValues                     = [];
        $initValues['projectVariantId'] = $ProjectVariant->getId();
        $initValues['processDbId']      = $ProcessDb->getId();

        if ($ProcessDb->isEn15804Compliant()) {
            $lcIdents = [
                ElcaLifeCycle::IDENT_A13,
                ElcaLifeCycle::PHASE_MAINT,
                ElcaLifeCycle::IDENT_B6,
                ElcaLifeCycle::IDENT_C3,
                ElcaLifeCycle::IDENT_C4,
                ElcaLifeCycle::IDENT_D,
            ];
        } else {
            $lcIdents = [
                ElcaLifeCycle::PHASE_PROD,
                ElcaLifeCycle::PHASE_MAINT,
                ElcaLifeCycle::PHASE_OP,
                ElcaLifeCycle::PHASE_EOL,
            ];
        }

        $indicatorCols = $processCols = $lftJoins = [];
        foreach ($lcIdents as $index => $lcIdent) {
            $tblIdent = str_replace('-', '', \utf8_strtolower($lcIdent));

            if ($lcIdent != ElcaLifeCycle::PHASE_MAINT) {
                $lftJoins[]    = 'LEFT JOIN ' . ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS . ' p_' . $tblIdent . ' ON pc.id = p_' . $tblIdent . '.process_config_id AND p_' . $tblIdent . '.life_cycle_ident = :lcIdent' . $index . ' AND p_' . $tblIdent . '.process_db_id = :processDbId';
                $processCols[] = 'CASE WHEN i_' . $tblIdent . '_' . $firstIndicatorId . '.value IS NOT NULL THEN p_' . $tblIdent . '.name_orig ELSE null END AS p_' . $tblIdent;
            }

            $initValues['lcIdent' . $index] = $lcIdent;

            foreach ($Indicators as $i => $Indicator) {
                $indicatorId                              = $Indicator->getId();
                $lftJoins[]                               = 'LEFT JOIN ' . ElcaCacheIndicator::TABLE_NAME . ' i_' . $tblIdent . '_' . $indicatorId . ' ON cec.item_id = i_' . $tblIdent . '_' . $indicatorId . '.item_id AND i_' . $tblIdent . '_' . $indicatorId . '.life_cycle_ident = :lcIdent' . $index . ' AND i_' . $tblIdent . '_' . $indicatorId . '.indicator_id = :indicatorId' . $indicatorId;
                $initValues['indicatorId' . $indicatorId] = $indicatorId;
                $indicatorCols[]                          = 'i_' . $tblIdent . '_' . $indicatorId . '.value AS i_' . $tblIdent . '_' . $Indicator->getIdent(
                    );
            }
        }

        $sql = sprintf(
            "SELECT 'B' AS export_type
                             , COALESCE(ce.name, e.name) AS composite_name
                             , left(t.din_code::text, 2)||'0' AS composite_din_code
                             , e.name
                             , t.din_code
                             , c.layer_position
                             , pc.name AS process_config
                             , c.layer_area_ratio * 100 AS layer_area_ratio
                             , e.quantity AS element_quantity
                             , e.ref_unit AS element_ref_unit
                             , c.layer_size
                             , c.layer_width * c.layer_length * c.layer_size * e.quantity AS layer_volume
                             , pc.density
                             , cec.quantity AS component_quantity
                             , cec.ref_unit AS component_ref_unit
                             , cec.mass
                             , cec.num_replacements
                             , %s
                             , %s
                          FROM %s e
                          JOIN %s t    ON t.node_id = e.element_type_node_id
                          JOIN %s c    ON e.id = c.element_id
                          JOIN %s pc   ON pc.id = c.process_config_id
                          JOIN %s cec  ON c.id = cec.element_component_id
                          JOIN %s v    ON v.id = e.project_variant_id
                          JOIN %s proj ON proj.id = v.project_id
                     LEFT JOIN %s coe  ON NOT e.is_composite AND e.id = coe.element_id
                     LEFT JOIN %s ce   ON ce.id = coe.composite_element_id
                            %s
                         WHERE e.project_variant_id = :projectVariantId
                      ORDER BY t.din_code
                             , composite_name
                             , c.layer_position"
            ,
            join(', ', $processCols)
            ,
            join(', ', $indicatorCols)
            ,
            ElcaElement::TABLE_NAME
            ,
            ElcaElementType::TABLE_NAME
            ,
            ElcaElementComponent::TABLE_NAME
            ,
            ElcaProcessConfig::TABLE_NAME
            ,
            ElcaCacheElementComponent::TABLE_NAME
            ,
            ElcaProjectVariant::TABLE_NAME
            ,
            ElcaProject::TABLE_NAME
            ,
            ElcaCompositeElement::TABLE_NAME
            ,
            ElcaElement::TABLE_NAME
            ,
            join(' ', $lftJoins)
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findElementsForCsvExport

    /**
     * Finds all final energy demands for csv exports
     *
     * @param ElcaProjectVariant $ProjectVariant
     * @param  boolean           $force - Bypass caching
     *
     * @throws Exception
     * @internal param ElcaProcessDb $ProcessDb
     * @return BnbExportSet
     */
    public static function findEnergyDemandsForCsvExport(ElcaProjectVariant $ProjectVariant, $force = false)
    {
        if (!$ProjectVariant->isInitialized()) {
            return new BnbExportSet();
        }

        $ProcessDb        = $ProjectVariant->getProject()->getProcessDb();
        $Indicators       = ElcaIndicatorSet::findByProcessDbId($ProcessDb->getId());
        $firstIndicatorId = $Indicators[0]->getId();

        $initValues                     = [];
        $initValues['projectVariantId'] = $ProjectVariant->getId();
        $initValues['processDbId']      = $ProcessDb->getId();

        if ($ProcessDb->isEn15804Compliant()) {
            $lcIdents = [
                ElcaLifeCycle::IDENT_A13,
                ElcaLifeCycle::PHASE_MAINT,
                ElcaLifeCycle::IDENT_B6,
                ElcaLifeCycle::IDENT_C3,
                ElcaLifeCycle::IDENT_C4,
                ElcaLifeCycle::IDENT_D,
            ];
        } else {
            $lcIdents = [
                ElcaLifeCycle::PHASE_PROD,
                ElcaLifeCycle::PHASE_MAINT,
                ElcaLifeCycle::PHASE_OP,
                ElcaLifeCycle::PHASE_EOL,
            ];
        }

        $indicatorCols = $processCols = $lftJoins = [];
        foreach ($lcIdents as $index => $lcIdent) {
            $tblIdent = str_replace('-', '', \utf8_strtolower($lcIdent));

            if ($lcIdent === ElcaLifeCycle::PHASE_OP || $lcIdent === ElcaLifeCycle::IDENT_B6) {

                if ($lcIdent !== ElcaLifeCycle::PHASE_MAINT) {
                    $lftJoins[]    = 'LEFT JOIN ' . ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS . ' p_' . $tblIdent . ' ON pc.id = p_' . $tblIdent . '.process_config_id AND p_' . $tblIdent . '.life_cycle_ident = :lcIdent' . $index . ' AND p_' . $tblIdent . '.process_db_id = :processDbId';
                    $processCols[] = 'p_' . $tblIdent . '.name_orig AS p_' . $tblIdent;
                    $processCols[] = 'CASE WHEN i_' . $tblIdent . '_' . $firstIndicatorId . '.value IS NOT NULL THEN p_' . $tblIdent . '.name_orig ELSE null END AS p_' . $tblIdent;
                }

                $initValues['lcIdent' . $index] = $lcIdent;
            } else {
                $processCols[] = 'NULL::varchar AS p_' . $tblIdent;
            }


            foreach ($Indicators as $i => $Indicator) {
                $indicatorId = $Indicator->getId();

                if ($lcIdent === ElcaLifeCycle::PHASE_OP || $lcIdent === ElcaLifeCycle::IDENT_B6) {
                    $lftJoins[]                               = 'LEFT JOIN ' . ElcaCacheIndicator::TABLE_NAME . ' i_' . $tblIdent . '_' . $indicatorId . ' ON cfed.item_id = i_' . $tblIdent . '_' . $indicatorId . '.item_id AND i_' . $tblIdent . '_' . $indicatorId . '.life_cycle_ident = :lcIdent' . $index . ' AND i_' . $tblIdent . '_' . $indicatorId . '.indicator_id = :indicatorId' . $indicatorId;
                    $initValues['indicatorId' . $indicatorId] = $indicatorId;
                    $indicatorCols[]                          = 'i_' . $tblIdent . '_' . $indicatorId . '.value AS i_' . $tblIdent . '_' . $Indicator->getIdent(
                        );
                } else {
                    $indicatorCols[] = 'NULL::numeric AS i_' . $tblIdent . '_' . $Indicator->getIdent();
                }
            }
        }

        $sql = sprintf(
            "SELECT 'E' AS export_type
                             , null AS composite_name
                             , null AS composite_din_code
                             , null AS name
                             , null AS din_code
                             , null AS layer_position
                             , cfed.quantity AS element_quantity
                             , cfed.ref_unit AS element_ref_unit
                             , pc.name AS process_config
                             , null AS layer_area_ratio
                             , cfed.quantity AS component_quantity
                             , cfed.ref_unit AS component_ref_unit
                             , null AS layer_size
                             , null AS layer_volume
                             , null AS density
                             , null AS mass
                             , null AS num_replacements
                             , %s
                             , %s
                          FROM %s fed
                          JOIN %s pc   ON pc.id = fed.process_config_id
                          JOIN %s cfed  ON fed.id = cfed.final_energy_demand_id
                          JOIN %s v    ON v.id = fed.project_variant_id
                          JOIN %s proj ON proj.id = v.project_id
                            %s
                         WHERE fed.project_variant_id = :projectVariantId
                      ORDER BY pc.name"
            ,
            join(', ', $processCols)
            ,
            join(', ', $indicatorCols)
            ,
            ElcaProjectFinalEnergyDemand::TABLE_NAME
            ,
            ElcaProcessConfig::TABLE_NAME
            ,
            ElcaCacheFinalEnergyDemand::TABLE_NAME
            ,
            ElcaProjectVariant::TABLE_NAME
            ,
            ElcaProject::TABLE_NAME
            ,
            join(' ', $lftJoins)
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findElementsForCsvExport
}
// End BnbExportSet
