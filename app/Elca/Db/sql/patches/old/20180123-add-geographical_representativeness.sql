BEGIN;
SELECT
    public.register_patch('20180123-add-geographical_representativeness.sql', 'eLCA');

-- Sicht elca.process_assignments_v hängt von Sicht elca.processes_v ab
-- Sicht elca.process_configs_extended_search_v hängt von Sicht elca.process_assignments_v ab
-- Sicht soda4lca.processes_with_process_configs_v hängt von Sicht elca.process_assignments_v ab
-- Sicht soda4lca.processes_v hängt von Sicht elca.process_assignments_v ab
-- Sicht elca_cache.report_top_assets_v hängt von Sicht elca.process_assignments_v ab
-- Sicht elca_cache.report_assets_v hängt von Sicht elca.process_assignments_v ab
-- Sicht elca.process_config_sanities_v hängt von Sicht elca.process_assignments_v ab
-- Sicht elca_cache.report_transport_assets_v hängt von Sicht elca.process_assignments_v ab
-- Sicht elca_cache.report_top_process_effects_v hängt von Sicht elca.process_assignments_v ab
-- Sicht elca_cache.report_final_energy_supply_assets_v hängt von Sicht elca.process_assignments_v ab
-- Sicht elca_cache.report_final_energy_demand_assets_v hängt von Sicht elca.process_assignments_v ab

DROP VIEW IF EXISTS elca.processes_v CASCADE;

ALTER TABLE elca.processes ADD "geographical_representativeness" varchar(10);

CREATE OR REPLACE VIEW elca.processes_v AS
    SELECT
        p.*
        , l.name        AS life_cycle_name
        , l.phase       AS life_cycle_phase
        , l.p_order     AS life_cycle_p_order
        , l.description AS life_cycle_description
    FROM elca.processes p
        JOIN elca.life_cycles l ON l.ident = p.life_cycle_ident;


CREATE OR REPLACE VIEW elca.process_assignments_v AS
    SELECT
        p.*
        , a.id AS process_life_cycle_assignment_id
        , a.process_config_id
        , a.ratio
    FROM elca.processes_v p
        JOIN elca.process_life_cycle_assignments a ON p.id = a.process_id;


CREATE VIEW elca.process_configs_extended_search_v AS
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
        , pc.uuid
        , pc.svg_pattern_id
        , pc.is_stale
        , pc.created
        , pc.modified
        , to_tsvector('german', pc.name || ' ' ||
                                coalesce(array_to_string(array_agg(DISTINCT p.uuid :: text), ' '), '') || ' ' ||
                                coalesce(array_to_string(array_agg(DISTINCT p.name_orig), ' '), '')) AS search_vector
    FROM elca.process_configs pc
        LEFT JOIN elca.process_assignments_v p ON pc.id = p.process_config_id
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
        , pc.uuid
        , pc.is_stale
        , pc.created
        , pc.modified;

CREATE VIEW soda4lca.processes_with_process_configs_v AS
    SELECT DISTINCT p.import_id
        , p.version
        , p.latest_version
        , p.status
        , p.error_code
        , p.uuid
        , p.details
        , COALESCE(c.ref_num ||' '|| a.name_orig, p.class_id||' '||p.name) AS name
        , array_to_string(array_agg(DISTINCT '"'||pc.name||'"'), ', ') AS process_configs
        , CASE WHEN count(DISTINCT a.life_cycle_name) > 0 THEN array_to_string(array_agg(DISTINCT a.life_cycle_name), ', ')
          ELSE p.epd_modules
          END AS modules
    FROM soda4lca.processes         p
        LEFT JOIN elca.process_assignments_v a ON a.uuid = p.uuid
        LEFT JOIN elca.process_categories    c ON c.node_id = a.process_category_node_id
        LEFT JOIN elca.process_configs      pc ON pc.id = a.process_config_id
    GROUP BY p.import_id
        , c.ref_num
        , p.name
        , p.uuid
        , p.class_id
        , p.epd_modules
        , a.name_orig
        , p.version
        , p.status
        , p.error_code
        , p.details;

CREATE VIEW soda4lca.processes_v AS
    SELECT DISTINCT p.import_id
        , p.version
        , p.latest_version
        , p.status
        , p.error_code
        , p.uuid
        , p.details
        , COALESCE(c.ref_num ||' '|| a.name_orig, p.class_id||' '||p.name) AS name
        , CASE WHEN count(DISTINCT a.life_cycle_name) > 0 THEN array_to_string(array_agg(DISTINCT a.life_cycle_name), ', ')
          ELSE p.epd_modules
          END AS modules
    FROM soda4lca.processes         p
        LEFT JOIN elca.process_assignments_v a ON a.uuid = p.uuid
        LEFT JOIN elca.process_categories    c ON c.node_id = a.process_category_node_id
    GROUP BY p.import_id
        , c.ref_num
        , p.name
        , p.uuid
        , p.class_id
        , p.epd_modules
        , a.name_orig
        , p.version
        , p.status
        , p.error_code
        , p.details;

CREATE VIEW elca_cache.report_top_assets_v AS
    SELECT e.project_variant_id
        , t.din_code AS element_type_din_code
        , t.name AS element_type_name
        , e.id   AS element_id
        , e.name AS element_name
        , e.quantity AS element_quantity
        , e.ref_unit AS element_ref_unit
        , ce.quantity AS cache_element_quantity
        , ce.ref_unit AS cache_element_ref_unit
        , ce.mass AS element_mass
        , c.id AS element_component_id
        , c.is_layer AS component_is_layer
        , c.layer_position AS component_layer_position
        , c.calc_lca AS component_calc_lca
        , c.is_extant AS component_is_extant
        , a.process_db_id
        , a.name_orig     AS process_name_orig
        , a.scenario_id AS process_scenario_id
        , a.ref_value AS process_ref_value
        , a.ref_unit AS process_ref_unit
        , a.life_cycle_description AS process_life_cycle_description
        , a.life_cycle_phase AS process_life_cycle_phase
        , a.ratio AS process_ratio
        , pc.name AS process_config_name
        , cc.quantity AS cache_component_quantity
        , cc.ref_unit AS cache_component_ref_unit
        , cc.mass AS cache_component_mass
        , cc.num_replacements AS cache_component_num_replacements
    FROM elca.elements e
        JOIN elca_cache.elements        ce ON e.id = ce.element_id
        JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
        JOIN elca.element_components    c  ON e.id = c.element_id
        JOIN elca_cache.element_components cc ON c.id = cc.element_component_id
        JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
        JOIN elca.process_configs       pc ON pc.id = a.process_config_id;

CREATE VIEW elca_cache.report_assets_v AS
    SELECT e.project_variant_id
        , t.din_code AS element_type_din_code
        , t.name AS element_type_name
        , tt.name AS element_type_parent_name
        , tt.din_code AS element_type_parent_din_code
        , t.is_constructional AS element_type_is_constructional
        , t.pref_has_element_image AS has_element_image
        , e.id   AS element_id
        , e.name AS element_name
        , e.quantity AS element_quantity
        , e.ref_unit AS element_ref_unit
        , ce.quantity AS cache_element_quantity
        , ce.ref_unit AS cache_element_ref_unit
        , ce.mass AS element_mass
        , c.id AS element_component_id
        , c.is_layer AS component_is_layer
        , c.calc_lca AS component_calc_lca
        , c.is_extant AS component_is_extant
        , c.layer_size AS component_size
        , c.quantity AS component_quantity
        , c.life_time AS component_life_time
        , c.life_time_delay AS component_life_time_delay
        , c.life_time_info AS component_life_time_info
        , c.layer_position AS component_layer_position
        , c.layer_area_ratio AS component_layer_area_ratio
        , a.process_db_id
        , a.name_orig     AS process_name_orig
        , a.scenario_id AS process_scenario_id
        , a.ref_value AS process_ref_value
        , a.ref_unit AS process_ref_unit
        , a.uuid     AS process_uuid
        , a.life_cycle_description AS process_life_cycle_description
        , a.life_cycle_ident AS process_life_cycle_ident
        , a.life_cycle_p_order AS process_life_cycle_p_order
        , a.ratio AS process_ratio
        , pc.name AS process_config_name
        , cc.quantity AS cache_component_quantity
        , cc.ref_unit AS cache_component_ref_unit
        , cc.num_replacements AS cache_component_num_replacements
        , pc.min_life_time, pc.avg_life_time, pc.max_life_time
        , c.life_time NOT IN (coalesce(pc.min_life_time, 0), coalesce(pc.avg_life_time, 0), coalesce(pc.max_life_time, 0)) AS has_non_default_life_time
    FROM elca.elements e
        JOIN elca_cache.elements        ce ON e.id = ce.element_id
        JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
        JOIN elca.element_types_v       tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
        JOIN elca.element_components    c  ON e.id = c.element_id
        JOIN elca_cache.element_components cc ON c.id = cc.element_component_id
        JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
        JOIN elca.process_configs       pc ON pc.id = a.process_config_id;

CREATE VIEW elca.process_config_sanities_v AS
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
        , null :: int           AS process_db_id
    FROM elca.process_configs pc
        JOIN (
                 SELECT DISTINCT
                     process_config_id
                     , a.ref_unit AS in
                     , b.ref_unit AS out
                 FROM elca.process_assignments_v a
                     JOIN elca.process_assignments_v b USING (process_config_id)
                 WHERE 'op' NOT IN (a.life_cycle_phase, b.life_cycle_phase) AND a.ref_unit <> b.ref_unit
             ) a ON pc.id = a.process_config_id
        LEFT JOIN elca.process_conversions c
            ON pc.id = c.process_config_id AND (a.in, a.out) IN ((c.in_unit, c.out_unit), (c.out_unit, c.in_unit))
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
        null :: int           AS process_db_id
    FROM elca.process_configs pc
        JOIN elca.process_assignments_v a ON pc.id = a.process_config_id AND a.life_cycle_phase = 'prod' AND a.ref_unit = 'm2'
    WHERE pc.density IS NULL
    UNION
    SELECT
        'MISSING_MASS_CONVERSION' AS status,
        pc.id AS process_config_id,
        pc.name,
        pc.process_category_node_id,
        null :: int           AS process_db_id
    FROM elca.process_configs pc
        JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE
        NOT EXISTS(SELECT * FROM elca.process_conversions c WHERE pc.id = c.process_config_id AND 'kg' IN (c.in_unit, c.out_unit))
    GROUP BY
        pc.id
        , pc.name
        , pc.process_category_node_id
    HAVING 'op' != ANY (array_agg(DISTINCT a.life_cycle_phase))
           AND 'kg' != ANY (array_agg(DISTINCT a.ref_unit));

CREATE VIEW elca_cache.report_transport_assets_v AS
    SELECT tm.id AS transport_mean_id
        , t.project_variant_id
        , t.id AS transport_id
        , t.name AS transport_name
        , t.quantity AS transport_quantity
        , pc.name AS process_config_name
        , p.id AS process_id
        , p.process_db_id
        , p.name_orig AS process_name_orig
        , p.scenario_id AS process_scenario_id
        , p.ref_value AS process_ref_value
        , p.ref_unit AS process_ref_unit
        , p.uuid AS process_uuid
        , p.life_cycle_description AS life_cycle_description
        , p.life_cycle_ident AS life_cycle_ident
        , p.life_cycle_phase AS life_cycle_phase
        , p.life_cycle_p_order AS life_cycle_p_order
        , ct.quantity AS total
        , ct.ref_unit AS total_unit
    FROM elca.project_transport_means tm
        JOIN elca.project_transports t ON t.id = tm.project_transport_id
        JOIN elca_cache.transport_means ct ON tm.id = ct.transport_mean_id
        JOIN elca.process_configs pc ON pc.id = tm.process_config_id
        JOIN elca.process_assignments_v p ON p.process_config_id = tm.process_config_id AND p.life_cycle_ident = 'A4';

CREATE VIEW elca_cache.report_top_process_effects_v AS
    SELECT e.project_variant_id
        , a.process_db_id
        , a.id AS process_id
        , a.name_orig AS process_name_orig
        , a.scenario_id AS process_scenario_id
        , ci.indicator_id AS indicator_id
        , l.ident
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , cc.ref_unit
        , sum(cc.quantity) AS quantity
        , sum(ci.value) AS indicator_value
    FROM elca_cache.element_components cc
        JOIN elca_cache.indicators      ci ON cc.item_id = ci.item_id
        JOIN elca.element_components     c ON c.id = cc.element_component_id
        JOIN elca.elements               e ON e.id = c.element_id
        JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
        JOIN elca.process_configs       pc ON pc.id = a.process_config_id
        JOIN elca.life_cycles           l  ON l.ident = ci.life_cycle_ident AND l.phase = 'total'
        JOIN elca.indicators            i  ON i.id = ci.indicator_id
    GROUP BY e.project_variant_id
        , a.process_db_id
        , a.id
        , a.name_orig
        , a.scenario_id
        , cc.ref_unit
        , ci.indicator_id
        , l.ident
        , i.name
        , i.unit
        , i.is_hidden
        , i.p_order;

CREATE VIEW elca_cache.report_final_energy_supply_assets_v AS
    SELECT f.id
        , f.project_variant_id
        , pc.name AS process_config_name
        , p.id AS process_id
        , p.process_db_id
        , p.name_orig AS process_name_orig
        , p.scenario_id AS process_scenario_id
        , p.ref_value AS process_ref_value
        , p.ref_unit AS process_ref_unit
        , p.uuid AS process_uuid
        , p.life_cycle_description AS life_cycle_description
        , p.life_cycle_ident AS life_cycle_ident
        , p.life_cycle_phase AS life_cycle_phase
        , p.life_cycle_p_order AS life_cycle_p_order
        , f.quantity
        , f.en_ev_ratio
        , cf.quantity AS total
        , cf.ref_unit AS total_unit
    FROM elca.project_final_energy_supplies f
        JOIN elca_cache.final_energy_supplies cf ON f.id = cf.final_energy_supply_id
        JOIN elca.process_configs pc ON pc.id = f.process_config_id
        JOIN elca.process_assignments_v p ON p.process_config_id = f.process_config_id AND p.life_cycle_phase = 'op';

CREATE VIEW elca_cache.report_final_energy_demand_assets_v AS
    SELECT f.id
        , f.project_variant_id
        , pc.name AS process_config_name
        , p.id AS process_id
        , p.process_db_id
        , p.name_orig AS process_name_orig
        , p.scenario_id AS process_scenario_id
        , p.ref_value AS process_ref_value
        , p.ref_unit AS process_ref_unit
        , p.uuid AS process_uuid
        , p.life_cycle_description AS life_cycle_description
        , p.life_cycle_ident AS life_cycle_ident
        , p.life_cycle_phase AS life_cycle_phase
        , p.life_cycle_p_order AS life_cycle_p_order
        , f.ident
        , f.heating
        , f.water
        , f.lighting
        , f.ventilation
        , f.cooling
        , cf.quantity AS total
        , cf.ref_unit AS total_unit
    FROM elca.project_final_energy_demands f
        JOIN elca_cache.final_energy_demands cf ON f.id = cf.final_energy_demand_id
        JOIN elca.process_configs pc ON pc.id = f.process_config_id
        JOIN elca.process_assignments_v p ON p.process_config_id = f.process_config_id AND p.life_cycle_phase = 'op';

COMMIT;