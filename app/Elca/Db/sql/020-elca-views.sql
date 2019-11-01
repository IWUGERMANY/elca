----------------------------------------------------------------------------------------
-- This file is part of the eLCA project
--
-- eLCA
-- A web based life cycle assessment application
--
-- Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
--               BEIBOB Medienfreunde GbR - http://beibob.de/
--
-- eLCA is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- eLCA is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with eLCA. If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------------------------
SET client_encoding = 'UTF8';
SET search_path = elca, public;

BEGIN;

CREATE FUNCTION public.array_intersect(a1 int[], a2 int[]) returns int[] as $$
DECLARE
    ret int[];
BEGIN
    -- The reason for the kludgy NULL handling comes later.
    if a1 is null then
        return a2;
    elseif a2 is null then
        return a1;
    end if;
    select array_agg(e) into ret
    from (
             select unnest(a1)
             intersect
             select unnest(a2)
         ) as dt(e);
    return ret;
END;
$$ LANGUAGE plpgsql;

CREATE AGGREGATE public.array_intersect_agg(int[]) (
sfunc = public.array_intersect,
stype = int[]
);


-- indicators

CREATE OR REPLACE VIEW elca.process_categories_v AS
    SELECT
        n.*
        , c.*
    FROM public.nested_nodes n
        JOIN elca.process_categories c ON n.id = c.node_id;

-------------------------------------------------------------------------------

CREATE OR REPLACE VIEW elca.processes_v AS
    SELECT
        p.*
        , l.name        AS life_cycle_name
        , l.phase       AS life_cycle_phase
        , l.p_order     AS life_cycle_p_order
        , l.description AS life_cycle_description
    FROM elca.processes p
        JOIN elca.life_cycles l ON l.ident = p.life_cycle_ident;

-------------------------------------------------------------------------------

CREATE OR REPLACE VIEW elca.process_assignments_v AS
    SELECT
        p.*
        , a.id AS process_life_cycle_assignment_id
        , a.process_config_id
        , a.ratio
    FROM elca.processes_v p
        JOIN elca.process_life_cycle_assignments a ON p.id = a.process_id;


CREATE OR REPLACE VIEW elca.export_process_assignments_v AS
    SELECT
        p.*
        , c.ref_num AS category_ref_num
        , a.id      AS process_life_cycle_assignment_id
        , a.process_config_id
        , a.ratio
    FROM elca.processes p
        JOIN elca.process_life_cycle_assignments a ON p.id = a.process_id
        JOIN elca.process_categories c ON c.node_id = p.process_category_node_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.process_search_v;
CREATE VIEW elca.process_search_v AS
    SELECT
        p.id
        , p.process_db_id
        , p.name
        , p.life_cycle_ident
        , p.process_category_node_id
        , c.ref_num || ' ' || c.name   AS process_category_node_name
        , c2.ref_num || ' ' || c2.name AS process_category_parent_node_name
    FROM elca.processes p
        JOIN elca.process_categories_v c ON c.node_id = p.process_category_node_id
        JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.process_config_search_v;
CREATE VIEW elca.process_config_search_v AS
    SELECT
        p.id
        , p.name
        , p.description
        , p.process_category_node_id
        , p.is_reference
        , p.process_db_ids
        , p.epd_types
        , c.ref_num || ' ' || c.name   AS process_category_node_name
        , c2.ref_num || ' ' || c2.name AS process_category_parent_node_name
    FROM elca.process_config_process_dbs_view p
        JOIN elca.process_categories_v c ON c.node_id = p.process_category_node_id
        JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;

DROP VIEW IF EXISTS elca.process_config_search_all_v;
CREATE VIEW elca.process_config_search_all_v AS
    SELECT
        p.id
         , p.name
         , p.description
         , p.process_category_node_id
         , p.is_reference
         , p.process_db_ids
         , p.epd_types
         , c.ref_num || ' ' || c.name   AS process_category_node_name
         , c2.ref_num || ' ' || c2.name AS process_category_parent_node_name
    FROM elca.all_process_config_process_dbs_view p
             JOIN elca.process_categories_v c ON c.node_id = p.process_category_node_id
             JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.composite_elements_v;
CREATE VIEW elca.composite_elements_v AS
    SELECT
        c.composite_element_id
        , c.position
        , c.element_id
        , e.id
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
    FROM elca.composite_elements c
        JOIN elca.elements e ON e.id = c.element_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.process_config_process_dbs_view;
CREATE OR REPLACE VIEW elca.process_config_process_dbs_view AS
    SELECT pc.id
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
        , pc.default_size
        , pc.waste_code
        , pc.waste_code_suffix
        , pc.lambda_value
        , pc.element_group_a
        , pc.element_group_b
        , pc.uuid
        , pc.svg_pattern_id
        , pc.is_stale
        , pc.created
        , pc.modified
        , array_agg(DISTINCT p.process_db_id) AS process_db_ids
        , array_agg(DISTINCT p.epd_type) FILTER (WHERE epd_type IS NOT NULL) AS epd_types
    FROM elca.process_configs pc
        JOIN elca.process_life_cycle_assignments a ON pc.id = a.process_config_id
        JOIN elca.processes p ON p.id = a.process_id
        JOIN elca.life_cycles lc ON lc.ident = p.life_cycle_ident
    WHERE lc.phase = 'prod'
    GROUP BY pc.id
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
        , pc.default_size
            , pc.waste_code
            , pc.waste_code_suffix
            , pc.lambda_value
            , pc.element_group_a
            , pc.element_group_b
        , pc.uuid
        , pc.svg_pattern_id
        , pc.is_stale
        , pc.created
        , pc.modified
;

DROP VIEW IF EXISTS elca.all_process_config_process_dbs_view;
CREATE OR REPLACE VIEW elca.all_process_config_process_dbs_view AS
    SELECT pc.id
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
         , pc.default_size
            , pc.waste_code
            , pc.waste_code_suffix
            , pc.lambda_value
            , pc.element_group_a
            , pc.element_group_b         , pc.uuid
         , pc.svg_pattern_id
         , pc.is_stale
         , pc.created
         , pc.modified
         , array_agg(DISTINCT p.process_db_id) AS process_db_ids
         , array_agg(DISTINCT p.epd_type) FILTER (WHERE epd_type IS NOT NULL) AS epd_types
    FROM elca.process_configs pc
             JOIN elca.process_life_cycle_assignments a ON pc.id = a.process_config_id
             JOIN elca.processes p ON p.id = a.process_id
             JOIN elca.life_cycles lc ON lc.ident = p.life_cycle_ident
    GROUP BY pc.id
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
           , pc.default_size
            , pc.waste_code
            , pc.waste_code_suffix
            , pc.lambda_value
            , pc.element_group_a
            , pc.element_group_b
             , pc.uuid
           , pc.svg_pattern_id
           , pc.is_stale
           , pc.created
           , pc.modified
;


DROP VIEW IF EXISTS elca.element_extended_search_v;
CREATE OR REPLACE VIEW elca.element_extended_search_v AS
    SELECT
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
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.element_components c ON e.id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = false
    GROUP BY e.id
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
        , e.modified;

DROP VIEW IF EXISTS elca.composite_element_extended_search_v;
CREATE OR REPLACE VIEW elca.composite_element_extended_search_v AS
    SELECT
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
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.composite_elements a ON e.id = a.composite_element_id
        LEFT JOIN elca.element_components c ON a.element_id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = true
    GROUP BY e.id
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
        , e.modified;

DROP VIEW IF EXISTS elca.element_search_v;
CREATE OR REPLACE VIEW elca.element_search_v AS
    SELECT
        e.id
        , e.name
        , e.element_type_node_id
        , e.project_variant_id
        , e.access_group_id
        , e.is_reference
        , e.is_public
        , t.din_code || ' ' || t.name AS element_type_node_name
        , e.process_db_ids
    FROM elca.element_extended_search_v e
        JOIN elca.element_types t ON t.node_id = e.element_type_node_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
CREATE OR REPLACE VIEW elca.process_configs_extended_search_v AS
    SELECT
        pc.id
        , pc.process_category_node_id
        , pc.name
        , pc.description
        , pc.life_time_info
        , pc.min_life_time
        , pc.min_life_time_info
        , pc.avg_life_time
        , pc.avg_life_time_info
        , pc.max_life_time
        , pc.max_life_time_info
        , pc.density
        , pc.thermal_conductivity
        , pc.thermal_resistance
        , pc.is_reference
        , pc.f_hs_hi
        , pc.default_size
            , pc.waste_code
            , pc.waste_code_suffix
            , pc.lambda_value
            , pc.element_group_a
            , pc.element_group_b        , pc.uuid
        , pc.svg_pattern_id
        , pc.is_stale
        , pc.created
        , pc.modified
        , pc.name || ' ' || coalesce(array_to_string(array_agg(DISTINCT n.name :: text), ' '), '') AS search_vector
    FROM elca.process_configs pc
        LEFT JOIN elca.process_config_names n ON pc.id = n.process_config_id
    GROUP BY pc.id
        , pc.process_category_node_id
        , pc.name
        , pc.description
        , pc.life_time_info
        , pc.min_life_time
        , pc.min_life_time_info
        , pc.avg_life_time
        , pc.avg_life_time_info
        , pc.max_life_time
        , pc.max_life_time_info
        , pc.density
        , pc.thermal_conductivity
        , pc.thermal_resistance
        , pc.is_reference
        , pc.f_hs_hi
        , pc.default_size
            , pc.waste_code
            , pc.waste_code_suffix
            , pc.lambda_value
            , pc.element_group_a
            , pc.element_group_b
             , pc.uuid
        , pc.is_stale
        , pc.created
        , pc.modified;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.element_types_v;
CREATE VIEW elca.element_types_v AS
    SELECT
        n.*
        , e.*
    FROM public.nested_nodes n
        JOIN elca.element_types e ON n.id = e.node_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.indicators_v;
DROP MATERIALIZED VIEW IF EXISTS elca.indicators_v;
CREATE MATERIALIZED VIEW  elca.indicators_v AS
    SELECT DISTINCT
        i.*
        , p.process_db_id
    FROM elca.indicators i
        JOIN elca.process_indicators pi ON i.id = pi.indicator_id
        JOIN elca.processes p ON p.id = pi.process_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.element_components_v;
CREATE VIEW elca.element_components_v AS
    SELECT
        c.*
        , e.name AS element_name
    FROM elca.element_components c
        JOIN elca.elements e ON e.id = c.element_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.element_layers_v;
CREATE VIEW elca.element_layers_v AS
    SELECT
        *
    FROM elca.element_components
    WHERE is_layer = true;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.element_single_components_v;
CREATE VIEW elca.element_single_components_v AS
    SELECT
        *
    FROM elca.element_components
    WHERE is_layer = false;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.process_config_sanities_v;
CREATE OR REPLACE VIEW elca.process_config_sanities_v AS
    SELECT
        'STALE'     AS status
         , pc.id       AS process_config_id
         , pc.name
         , pc.process_category_node_id
         , null :: int AS process_db_id
    FROM elca.process_configs pc
    WHERE is_stale = true
    UNION
    SELECT
        'MISSING_LIFE_TIME' AS status
         , pc.id               AS process_config_id
         , pc.name
         , pc.process_category_node_id
         , null :: int         AS process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE coalesce(pc.min_life_time, pc.avg_life_time, pc.max_life_time) IS NULL
    GROUP BY pc.id
           , pc.name
           , pc.process_category_node_id
           , a.process_db_id
    HAVING 'op' != ANY (array_agg(DISTINCT a.life_cycle_phase))
    UNION
    SELECT
        'MISSING_CONVERSIONS' AS status
            , pc.id                 AS process_config_id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id           AS process_db_id
    FROM elca.process_configs pc
             JOIN (
        SELECT DISTINCT
            process_config_id
                , process_db_id
                , a.ref_unit AS in
                , b.ref_unit AS out
        FROM elca.process_assignments_v a
                 JOIN elca.process_assignments_v b USING (process_config_id, process_db_id)
        WHERE 'op' NOT IN (a.life_cycle_phase, b.life_cycle_phase)
                AND a.ref_unit <> b.ref_unit
    ) a ON pc.id = a.process_config_id
             LEFT JOIN elca.process_conversions_v c ON pc.id = c.process_config_id AND (a.in, a.out) IN ((c.in_unit, c.out_unit), (c.out_unit, c.in_unit))
    WHERE c.id IS NULL
    UNION
    SELECT
        'MISSING_PRODUCTION' AS status
         , pc.id                AS process_config_id
         , pc.name
         , pc.process_category_node_id
         , a.process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE a.life_cycle_phase != 'op'
    GROUP BY pc.id
           , pc.name
           , pc.process_category_node_id
           , a.process_db_id
    HAVING 'prod' != ALL (array_agg(DISTINCT a.life_cycle_phase))
    UNION
    SELECT
        'MISSING_EOL' AS status
         , pc.id         AS process_config_id
         , pc.name
         , pc.process_category_node_id
         , a.process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE a.life_cycle_phase != 'op'
    GROUP BY pc.id
           , pc.name
           , pc.process_category_node_id
           , a.process_db_id
    HAVING 'eol' != ALL (array_agg(DISTINCT a.life_cycle_phase))
    UNION
    SELECT DISTINCT
        'MISSING_DENSITY' AS status,
        pc.id AS process_config_id,
        pc.name,
        pc.process_category_node_id,
        a.process_db_id   AS process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id AND a.life_cycle_phase = 'prod' AND a.ref_unit = 'm2'
             LEFT JOIN elca.process_conversions_v c ON pc.id = c.process_config_id AND c.process_db_id = a.process_db_id AND c.in_unit = 'm3' AND c.out_unit = 'kg'
    WHERE c.id IS NULL
    UNION
    SELECT
        'MISSING_MASS_CONVERSION' AS status,
        pc.id AS process_config_id,
        pc.name,
        pc.process_category_node_id,
        a.process_db_id           AS process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE
        NOT EXISTS(SELECT * FROM elca.process_conversions_v c WHERE (pc.id, a.process_db_id) = (c.process_config_id, a.process_db_id) AND 'kg' IN (c.in_unit, c.out_unit))
    GROUP BY
        pc.id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id
    HAVING 'op' != ANY (array_agg(DISTINCT a.life_cycle_phase))
            AND 'kg' != ANY (array_agg(DISTINCT a.ref_unit));


DROP VIEW IF EXISTS elca.element_process_config_sanities_v;
CREATE OR REPLACE VIEW elca.element_process_config_sanities_v AS
    SELECT
          e.id    AS element_id
        , e.name  AS element_name
        , t.din_code
        , CASE WHEN c.is_layer
        THEN c.layer_position
          ELSE NULL
          END     AS layer_position
        , pc.name AS process_config_name
        , e.access_group_id
    FROM
        elca.process_configs pc
        JOIN
        elca.element_components c ON pc.id = c.process_config_id
        JOIN
        elca.elements e ON e.id = c.element_id
        JOIN
        elca.element_types t ON t.node_id = e.element_type_node_id

    WHERE pc.is_stale = true OR pc.uuid = 'ffffffff-ffff-ffff-ffff-ffffffffffff'
          AND e.project_variant_id IS NULL;


DROP VIEW IF EXISTS elca.projects_view;
CREATE OR REPLACE VIEW elca.projects_view AS
    SELECT
        p.id
        , p.process_db_id
        , p.current_variant_id
        , p.access_group_id
        , p.owner_id
        , p.name
        , p.description
        , p.project_nr
        , p.constr_measure
        , p.life_time
        , p.created
        , p.modified
        , p.constr_class_id
        , p.editor
        , p.is_reference
        , p.benchmark_version_id
        , p.password
        , array_agg(g.user_id)
              FILTER (WHERE g.user_id IS NOT NULL) || p.owner_id AS user_ids
    FROM elca.projects p
        LEFT JOIN public.group_members g ON g.group_id = p.access_group_id
    GROUP BY p.id
        , p.process_db_id
        , p.current_variant_id
        , p.access_group_id
        , p.owner_id
        , p.name
        , p.description
        , p.project_nr
        , p.constr_measure
        , p.life_time
        , p.created
        , p.modified
        , p.constr_class_id
        , p.editor
        , p.is_reference
        , p.benchmark_version_id
        , p.password;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.benchmark_versions_with_constr_classes;
CREATE VIEW elca.benchmark_versions_with_constr_classes AS
SELECT v.id,
       v.benchmark_system_id,
       v.name,
       v.process_db_id,
       v.is_active,
       v.use_reference_model,
       v.project_life_time,
       array_agg(c.constr_class_id) FILTER (WHERE c.id IS NOT NULL) as constr_class_ids
 FROM elca.benchmark_versions v
    LEFT JOIN elca.benchmark_version_constr_classes c ON v.id = c.benchmark_version_id
    GROUP BY v.id,
        v.benchmark_system_id,
        v.name,
        v.process_db_id,
        v.is_active,
        v.use_reference_model,
        v.project_life_time;

-------------------------------------------------------------------------------
DROP VIEW IF EXISTS elca.process_conversions_v;
CREATE OR REPLACE VIEW elca.process_conversions_v AS
SELECT c.id
     , c.process_config_id
     , v.process_db_id
     , c.in_unit
     , c.out_unit
     , v.factor
     , v.ident
     , v.created
     , v.modified
FROM elca.process_conversions c
         JOIN elca.process_conversion_versions v ON c.id = v.conversion_id;

-------------------------------------------------------------------------------
COMMIT;
