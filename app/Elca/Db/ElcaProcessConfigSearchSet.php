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
 * Builds the process search view
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfigSearchSet extends DataObjectSet
{
    /**
     * Views
     */
    const VIEW_PROCESS_CONFIG_SEARCH = 'elca.process_config_search_v';
    const VIEW_PROCESS_CONFIG_SEARCH_ALL = 'elca.process_config_search_all_v';


    /**
     * Returns a list of matching process configs
     *
     * @param array $keywords
     * @param null  $inUnit
     * @param bool  $referenceOnly
     * @param bool  $force
     *
     * @return ElcaProcessConfigSearchSet
     * @throws Exception
     */
    public static function findByKeywords(array $keywords, $languageIdent, $inUnit = null, $referenceOnly = false,
        array $processDbIds = null, $filterByProjectVariantId = null, $epdSubType = null, $onlyProdConfigs = true,
        $force = false)
    {
        $initValues = array('locale' => $languageIdent);

        if (!$conditions = self::getSearchConditions($keywords, 'p.name', 'names.name', $initValues)) {
            return new ElcaProcessConfigSearchSet();
        }

        if ($inUnit) {
            if (\utf8_strpos($inUnit, ',') !== false) {
                $inUnit = explode(',', $inUnit);
            }

            if (is_array($inUnit)) {
                foreach ($inUnit as $i => $unit) {
                    $initValues['unit' . $i] = $unit;
                    $parts[]                 = ':unit' . $i;
                }
                $conditions .= ' AND in_unit IN (' . join(', ', $parts) . ')';
            } else {
                $initValues['inUnit'] = $inUnit;
                $conditions           .= ' AND c.in_unit = :inUnit';
            }
        }

        if ($referenceOnly) {
            $conditions .= ' AND is_reference = true';
        }

        if ($processDbIds) {
            $conditions                 .= ' AND :processDbIds::int[] && p.process_db_ids';
            $initValues['processDbIds'] = sprintf('{%s}', implode(',', $processDbIds));
        }
        if ($epdSubType) {
            $conditions               .= ' AND :epdSubType = ANY (p.epd_types)';
            $initValues['epdSubType'] = $epdSubType;
        }

        if ($filterByProjectVariantId) {
            $conditions .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s y ON y.id = x.element_id WHERE x.process_config_id = p.id AND y.project_variant_id = :projectVariantId)'
                , ElcaElementComponent::TABLE_NAME
                , ElcaElement::TABLE_NAME
            );

            $initValues['projectVariantId'] = $filterByProjectVariantId;
        }

        $sql = sprintf("SELECT DISTINCT p.*
                          FROM %s p
                          JOIN %s c ON p.id = c.process_config_id AND c.process_db_id = ANY(p.process_db_ids)
                          LEFT JOIN %s names ON p.id = names.process_config_id AND names.lang = :locale
                         WHERE %s
                      ORDER BY process_category_node_name
                             , p.name"
            , $onlyProdConfigs ? self::VIEW_PROCESS_CONFIG_SEARCH : self::VIEW_PROCESS_CONFIG_SEARCH_ALL
            , ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS
            , ElcaProcessConfigName::TABLE_NAME
            , $conditions
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByKeywords

    /**
     * Returns the search conditions
     *
     * @param array  $keywords
     * @param string $searchField
     * @param array  $initValues
     *
     * @return string
     */
    private static function getSearchConditions(array $keywords, $searchField, $multiLanguageField, array &$initValues)
    {
        $lftBoundary = $rgtBoundary = '%';

        $queries = array();
        foreach ($keywords as $index => $token) {
            $varName = 'token' . $index;

            $queries[]            = sprintf("(%s ilike :%s OR %s ilike :%s)", $searchField, $varName,
                $multiLanguageField, $varName);
            $initValues[$varName] = $lftBoundary . $token . $rgtBoundary;
        }

        $conditions = false;
        if (count($queries)) {
            $conditions = '(' . join(' AND ', $queries) . ')';
        }

        return $conditions;
    }
    // End findByKeywords

    /**
     * Returns a list of matching process configs
     *
     * @param array $keywords
     * @param null  $inUnit
     * @param bool  $referenceOnly
     * @param bool  $force
     *
     * @return ElcaProcessConfigSearchSet
     * @throws Exception
     */
    public static function findFinalEnergySuppliesByKeywords(array $keywords, $languageIdent, $inUnit = null,
        $referenceOnly = false, $activeProcessesOnly = false, $force = false)
    {
        $initValues = ['locale' => $languageIdent, 'opAsSupply' => ElcaProcessConfigAttribute::IDENT_OP_AS_SUPPLY];

        if (!$conditions = self::getSearchConditions($keywords, 'p.name', 'names.name', $initValues)) {
            return new ElcaProcessConfigSearchSet();
        }

        if ($inUnit) {
            $initValues['inUnit'] = $inUnit;
            $conditions           .= ' AND c.in_unit = :inUnit';
        }

        if ($referenceOnly) {
            $conditions .= ' AND is_reference = true';
        }

        if ($activeProcessesOnly) {
            $conditions .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active)'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );
        }

        $sql = sprintf("SELECT DISTINCT p.*
                          FROM %s p
                          JOIN %s pca ON p.id = pca.process_config_id AND pca.ident = :opAsSupply AND pca.numeric_value = 1
                          JOIN %s c ON p.id = c.process_config_id AND c.process_db_id = ANY(p.process_db_ids)
                          LEFT JOIN %s names ON p.id = names.process_config_id AND names.lang = :locale
                         WHERE %s
                      ORDER BY process_category_node_name
                             , p.name"
            , self::VIEW_PROCESS_CONFIG_SEARCH
            , ElcaProcessConfigAttribute::TABLE_NAME
            , ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS
            , ElcaProcessConfigName::TABLE_NAME
            , $conditions
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }

    /**
     * Returns a list of matching process configs
     *
     * @param array $keywords
     * @param null  $inUnit
     * @param bool  $referenceOnly
     * @param bool  $force
     *
     * @return ElcaProcessConfigSearchSet
     */
    public static function findKwkByKeywords(array $keywords, $languageIdent, $inUnit = null, $referenceOnly = false,
        $activeProcessesOnly = false, $force = false)
    {
        $initValues = ['locale' => $languageIdent];

        if (!$conditions = self::getSearchConditions($keywords, 'p.name', 'names.name', $initValues)) {
            return new ElcaProcessConfigSearchSet();
        }

        if ($inUnit) {
            $initValues['inUnit'] = $inUnit;
            $conditions           .= ' AND c.in_unit = :inUnit';
        }

        if ($referenceOnly) {
            $conditions .= ' AND is_reference = true';
        }

        if ($activeProcessesOnly) {
            $conditions .= sprintf(' AND EXISTS (SELECT x.id FROM %s x JOIN %s d ON d.id = x.process_db_id WHERE x.process_config_id = p.id AND d.is_active)'
                , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                , ElcaProcessDb::TABLE_NAME
            );
        }

        $conditions .= ' AND p.element_district_heating';

        $sql = sprintf("SELECT DISTINCT p.*
                          FROM %s p
                          JOIN %s c ON p.id = c.process_config_id AND c.process_db_id = ANY(p.process_db_ids)
                          LEFT JOIN %s names ON p.id = names.process_config_id AND names.lang = :locale
                         WHERE %s
                      ORDER BY process_category_node_name
                             , p.name"
            , self::VIEW_PROCESS_CONFIG_SEARCH
            , ElcaProcessConversionSet::VIEW_PROCESS_CONVERSIONS
            , ElcaProcessConfigName::TABLE_NAME
            , $conditions
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
}
// End ElcaProcessConfigSearchSet
