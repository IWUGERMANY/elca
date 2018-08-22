<?php declare(strict_types=1);
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
use Beibob\Blibs\DbObject;
use Elca\Controller\Admin\BenchmarksCtrl;
use PDO;

class ElcaCacheReferenceProjectSet extends DataObjectSet
{
    const MATERIALIZED_VIEW_REFERENCE_PROJECT_EFFECTS_VIEW = 'elca_cache.reference_projects_effects_v';

    public static function findByProjectVariantId($projectVariantId, $indicatorId, $force = false)
    {
        if (!$projectVariantId || !$indicatorId) {
            return new self();
        }

        $sql = sprintf(
            'SELECT
    benchmark_version_id,
    element_type_node_id,
    indicator_id,
    indicator_ident,
    value,
    ref_value,
    (value - ref_value) / abs(ref_value) AS deviation,
    ref_min,
    ref_max
FROM (
    SELECT
        r.benchmark_version_id,
        r.element_type_node_id,
        r.indicator_id,
        i.ident AS indicator_ident,
        r.min * COALESCE(s_min.numeric_value, 1) AS ref_min,
        r.avg * COALESCE(s_avg.numeric_value, 1) AS ref_value,
        r.max * COALESCE(s_max.numeric_value, 1) AS ref_max,
        t.value / (p.life_time * c.net_floor_space) AS value
    FROM %s t
        JOIN %s r ON r.element_type_node_id = t.element_type_node_id AND t.indicator_id = r.indicator_id
        JOIN %s v ON v.id = t.project_variant_id
        JOIN %s c ON t.project_variant_id = c.project_variant_id
        JOIN %s p ON p.id = v.project_id AND p.benchmark_version_id = r.benchmark_version_id
        JOIN %s i ON i.id = r.indicator_id
        LEFT JOIN %s s_min ON s_min.section = \'%s.\'||r.benchmark_version_id AND s_min.ident = i.ident||\'.min\'
        LEFT JOIN %s s_avg ON s_avg.section = \'%s.\'||r.benchmark_version_id AND s_avg.ident = i.ident||\'.avg\'
        LEFT JOIN %s s_max ON s_max.section = \'%s.\'||r.benchmark_version_id AND s_max.ident = i.ident||\'.max\'
    WHERE t.project_variant_id = :projectVariantId 
    AND life_cycle_ident = :lifeCycleIdent
    AND r.indicator_id = :indicatorId
) x',
            ElcaReportSet::VIEW_REPORT_ELEMENT_TYPE_EFFECTS,
            self::MATERIALIZED_VIEW_REFERENCE_PROJECT_EFFECTS_VIEW,
            ElcaProjectVariant::TABLE_NAME,
            ElcaProjectConstruction::TABLE_NAME,
            ElcaProject::TABLE_NAME,
            ElcaIndicator::TABLE_NAME,
            ElcaSetting::TABLE_NAME,
            BenchmarksCtrl::SETTING_SECTION_DIN_CODES,
            ElcaSetting::TABLE_NAME,
            BenchmarksCtrl::SETTING_SECTION_DIN_CODES,
            ElcaSetting::TABLE_NAME,
            BenchmarksCtrl::SETTING_SECTION_DIN_CODES
        );

        return self::_findBySql(
            __CLASS__,
            $sql,
            [
                'projectVariantId' => $projectVariantId,
                'indicatorId' => $indicatorId,
                'lifeCycleIdent' => ElcaLifeCycle::PHASE_TOTAL
            ],
            $force
        );
    }

    public static function findByBenchmarkVersionIdAndIndicatorId(int $benchmarkVersionId, int $indicatorId, array $initValues = [], array $orderBy = null, int $limit = null, int $offset = null, bool $force = false)
    {
        $initValues['benchmark_version_id'] = $benchmarkVersionId;
        $initValues['indicator_id']         = $indicatorId;

        return self::_find(
            __CLASS__,
            self::MATERIALIZED_VIEW_REFERENCE_PROJECT_EFFECTS_VIEW,
            $initValues,
            $orderBy,
            $limit,
            $offset,
            $force
        );
    }

    public static function findDistinctBenchmarkVersionIds(): array
    {
        $sql = sprintf('SELECT array_agg(DISTINCT benchmark_version_id) AS list FROM %s',self::MATERIALIZED_VIEW_REFERENCE_PROJECT_EFFECTS_VIEW);

        $stmt = self::prepareStatement($sql);

        $stmt->execute();
        $stmt->bindColumn('list', $result, PDO::PARAM_STR);
        $stmt->fetch(PDO::FETCH_BOUND);

        if (0 === $stmt->rowCount() || null === $result) {
            return [];
        }

        return str_getcsv(trim($result, '{}'));
    }


    public static function refreshMaterializedView()
    {
        $sql = sprintf('REFRESH MATERIALIZED VIEW %s', self::MATERIALIZED_VIEW_REFERENCE_PROJECT_EFFECTS_VIEW);

        $statement = DbObject::prepareStatement($sql);
        $statement->execute();
    }
}
