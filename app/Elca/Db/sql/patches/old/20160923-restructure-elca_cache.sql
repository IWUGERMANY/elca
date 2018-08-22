BEGIN;
SELECT public.register_patch('restructure-elca_cache', 'elca_cache');

ALTER TABLE elca_cache.items ADD COLUMN "project_id" int;
ALTER TABLE elca_cache.items ADD FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON DELETE CASCADE;
ALTER TABLE elca_cache.items ADD COLUMN "is_virtual" boolean NOT NULL DEFAULT false;



DROP VIEW IF EXISTS elca_cache.project_variants_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.project_variants_v AS
    SELECT i.*
        , v.*
    FROM elca_cache.project_variants v
        JOIN elca_cache.items    i ON i.id = v.item_id;


DROP VIEW IF EXISTS elca_cache.elements_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.elements_v AS
    SELECT i.*
        , e.*
    FROM elca_cache.elements e
        JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_components_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_components_v AS
    SELECT i.*
        , e.*
    FROM elca_cache.element_components e
        JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_types_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_types_v AS
    SELECT i.*
        , t.*
        , n.lft
        , n.rgt
        , n.level
        , n.ident
    FROM elca_cache.element_types t
        JOIN elca_cache.items         i ON i.id = t.item_id
        JOIN public.nested_nodes      n ON n.id = t.element_type_node_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.final_energy_demands_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_demands_v AS
    SELECT i.*
        , e.*
    FROM elca_cache.final_energy_demands e
        JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.final_energy_supplies_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_supplies_v AS
    SELECT i.*
        , e.*
    FROM elca_cache.final_energy_supplies e
        JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.final_energy_ref_models_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.final_energy_ref_models_v AS
    SELECT i.*
        , e.*
    FROM elca_cache.final_energy_ref_models e
        JOIN elca_cache.items    i ON i.id = e.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.transport_means_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.transport_means_v AS
    SELECT i.*
        , t.*
    FROM elca_cache.transport_means t
        JOIN elca_cache.items    i ON i.id = t.item_id;

--------------------------------------------------------------------------------


DROP VIEW IF EXISTS elca_cache.indicators_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.indicators_v AS
    SELECT i.*
        , ii.*
    FROM elca_cache.indicators i
        JOIN elca_cache.items      ii ON ii.id = i.item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_mass_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_mass_v AS
    SELECT parent_id AS element_item_id
        , sum(coalesce(mass, 0)) AS element_mass
    FROM elca_cache.element_components_v
    GROUP BY parent_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.composite_element_mass_v;
CREATE OR REPLACE VIEW elca_cache.composite_element_mass_v AS
    SELECT composite_item_id
        , sum(coalesce(mass, 0)) AS element_mass
    FROM elca_cache.elements_v
    WHERE composite_item_id IS NOT NULL
    GROUP BY composite_item_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.element_type_mass_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.element_type_mass_v AS
    SELECT t.item_id AS element_type_item_id
        , t.parent_id AS element_type_parent_id
        , t.level AS element_type_level
        , sum(coalesce(e.mass, 0)) AS element_type_mass
    FROM elca_cache.element_types_v t
        LEFT JOIN elca_cache.elements_v e ON t.item_id = e.parent_id AND NOT e.is_virtual
    GROUP BY t.item_id
        , t.parent_id
        , t.level;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicators_aggregate_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.indicators_aggregate_v AS
    SELECT parent_id AS item_id
        , life_cycle_ident
        , indicator_id
        , null::int AS process_id
        , sum(value) AS value
        , bool_and(is_partial) AS is_partial
    FROM elca_cache.indicators_v
    WHERE is_virtual = false
    GROUP BY parent_id
        , life_cycle_ident
        , indicator_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.composite_indicators_aggregate_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.composite_indicators_aggregate_v AS
    SELECT e.composite_item_id
        , i.life_cycle_ident
        , i.indicator_id
        , null::int AS process_id
        , sum(i.value) AS value
        , bool_and(i.is_partial) AS is_partial
    FROM elca_cache.elements_v e
        JOIN elca_cache.indicators_v i ON e.item_id = i.item_id
    WHERE e.composite_item_id IS NOT NULL
    GROUP BY e.composite_item_id
        , life_cycle_ident
        , indicator_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicators_totals_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.indicators_totals_v AS
    SELECT
        i.item_id
        , 'total' :: varchar(20) AS life_cycle_ident
        , i.indicator_id
        , null :: integer        AS process_id
        , sum(i.value)           AS value
        , 1                      AS ratio
        , true                   AS is_partial
    FROM elca_cache.indicators_v i
    WHERE
        life_cycle_ident = ANY ( ARRAY['maint'::varchar] ||
                                 ARRAY(SELECT u.life_cycle_ident
                                       FROM elca.project_life_cycle_usages u
                                       WHERE u.project_id = i.project_id
                                             AND (u.use_in_construction OR u.use_in_energy_demand))

        )
        AND i.is_partial = false
    GROUP BY i.item_id
        , i.indicator_id;


--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.indicator_results_v;
CREATE VIEW elca_cache.indicator_results_v AS
    SELECT ci.item_id
        , ci.life_cycle_ident
        , ci.indicator_id
        , ci.process_id
        , ci.value
        , ci.ratio
        , ci.is_partial
        , p.name_orig
        , l.name AS life_cycle_name
        , l.phase AS life_cycle_phase
        , l.p_order AS life_cycle_p_order
        , i.name AS indicator_name
        , i.ident AS indicator_ident
        , i.p_order AS indicator_p_order
    FROM elca_cache.indicators ci
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
        LEFT JOIN elca.processes p ON ci.process_id = p.id;



--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_assets_v;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_top_assets_v;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_final_energy_demand_assets_v;
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

DROP VIEW IF EXISTS elca_cache.report_final_energy_supply_assets_v;
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


--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_transport_assets_v;
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

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_top_process_effects_v CASCADE;
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
        , i.p_order;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_top_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_top_process_config_effects_v AS
    SELECT e.project_variant_id
        , c.process_config_id
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , l.phase AS life_cycle_phase
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
        , cc.ref_unit
        , sum(cc.quantity) AS quantity
        , sum(ci.value)    AS indicator_value
    FROM elca.elements                 e
        JOIN elca.element_components       c  ON c.element_id  = e.id
        JOIN elca.process_configs          pc ON pc.id = c.process_config_id
        JOIN elca_cache.element_components cc ON c.id  = cc.element_component_id
        JOIN elca_cache.indicators         ci ON cc.item_id = ci.item_id
        JOIN elca.indicators               i  ON i.id = ci.indicator_id
        JOIN elca.life_cycles              l  ON l.ident = ci.life_cycle_ident
    WHERE l.phase = 'total'
    GROUP BY e.project_variant_id
        , c.process_config_id
        , pc.name
        , cc.ref_unit
        , ci.indicator_id
        , l.phase
        , i.name
        , i.unit
        , i.p_order;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_element_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_element_process_config_effects_v AS
    SELECT c.element_id
        , c.id AS element_component_id
        , c.process_config_id
        , c.calc_lca
        , c.is_extant
        , c.is_layer
        , c.layer_position
        , c.layer_area_ratio
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , l.phase AS life_cycle_phase
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
        , cc.ref_unit
        , cc.quantity
        , ci.value AS indicator_value
    FROM elca.element_components       c
        JOIN elca.process_configs          pc ON pc.id = c.process_config_id
        JOIN elca_cache.element_components cc ON c.id  = cc.element_component_id
        JOIN elca_cache.indicators         ci ON cc.item_id = ci.item_id
        JOIN elca.indicators               i  ON i.id = ci.indicator_id
        JOIN elca.life_cycles              l  ON l.ident = ci.life_cycle_ident
    WHERE l.phase IN ('maint', 'prod', 'eol', 'rec', 'total');

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_composite_element_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_composite_element_process_config_effects_v AS
    SELECT a.composite_element_id
        , e.id AS element_id
        , e.name AS element_name
        , c.id AS element_component_id
        , c.process_config_id
        , c.calc_lca
        , c.is_extant
        , c.is_layer
        , c.layer_position
        , c.layer_area_ratio
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , l.phase AS life_cycle_phase
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
        , cc.ref_unit
        , cc.quantity
        , ci.value AS indicator_value
    FROM elca.composite_elements       a
        JOIN elca.elements                 e  ON e.id = a.element_id
        JOIN elca.element_components       c  ON c.element_id = a.element_id
        JOIN elca.process_configs          pc ON pc.id = c.process_config_id
        JOIN elca_cache.element_components cc ON c.id  = cc.element_component_id
        JOIN elca_cache.indicators         ci ON cc.item_id = ci.item_id
        JOIN elca.indicators               i  ON i.id = ci.indicator_id
        JOIN elca.life_cycles              l  ON l.ident = ci.life_cycle_ident
    WHERE l.phase IN ('maint', 'prod', 'eol', 'rec', 'total');

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_effects_v CASCADE;
CREATE VIEW elca_cache.report_effects_v AS
    SELECT e.id AS element_id
        , e.project_variant_id
        , e.name AS element_name
        , e.quantity AS element_quantity
        , e.ref_unit AS element_ref_unit
        , e.element_type_node_id
        , e.is_composite
        , t.din_code AS element_type_din_code
        , t.name AS element_type_name
        , t.is_constructional AS element_type_is_constructional
        , t.pref_has_element_image AS has_element_image
        , tt.name AS element_type_parent_name
        , tt.din_code AS element_type_parent_din_code
        , l.phase AS life_cycle_phase
        , ci.indicator_id AS indicator_id
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
        , sum(ci.value) AS indicator_value
    FROM elca.elements e
        JOIN elca.element_types_v   t ON e.element_type_node_id = t.node_id
        JOIN elca.element_types_v  tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
        JOIN elca_cache.elements_v ce ON e.id = ce.element_id
        JOIN elca_cache.indicators ci ON ce.item_id = ci.item_id
        JOIN elca.life_cycles       l ON l.ident = ci.life_cycle_ident
        JOIN elca.indicators        i ON i.id = ci.indicator_id
    WHERE l.phase IN ('total', 'prod', 'maint', 'eol', 'rec')
    GROUP BY e.id
        , e.project_variant_id
        , e.name
        , e.quantity
        , e.ref_unit
        , e.element_type_node_id
        , e.is_composite
        , t.din_code
        , t.name
        , t.is_constructional
        , t.pref_has_element_image
        , tt.name
        , tt.din_code
        , l.phase
        , ci.indicator_id
        , i.name
        , i.unit
        , i.p_order;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_final_energy_demand_effects_v CASCADE;
CREATE VIEW elca_cache.report_final_energy_demand_effects_v AS
    SELECT f.id
        , f.project_variant_id
        , f.ident
        , cf.quantity AS element_quantity
        , cf.ref_unit AS element_ref_unit
        , pc.name AS element_name
        , ci.indicator_id AS indicator_id
        , ci.value AS indicator_value
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
    FROM elca.project_final_energy_demands f
        JOIN elca.process_configs              pc ON pc.id = f.process_config_id
        JOIN elca_cache.final_energy_demands_v cf ON f.id = cf.final_energy_demand_id
        JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;

DROP VIEW IF EXISTS elca_cache.report_final_energy_supply_effects_v CASCADE;
CREATE VIEW elca_cache.report_final_energy_supply_effects_v AS
    SELECT f.id
        , f.project_variant_id
        , cf.quantity AS element_quantity
        , cf.ref_unit AS element_ref_unit
        , pc.name AS element_name
        , ci.indicator_id AS indicator_id
        , ci.value AS indicator_value
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
    FROM elca.project_final_energy_supplies f
        JOIN elca.process_configs              pc ON pc.id = f.process_config_id
        JOIN elca_cache.final_energy_supplies_v cf ON f.id = cf.final_energy_supply_id
        JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;


--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_transport_effects_v CASCADE;
CREATE VIEW elca_cache.report_transport_effects_v AS
    SELECT m.id AS transport_mean_id
        , t.id AS transport_id
        , t.name AS element_name
        , t.project_variant_id
        , cm.quantity AS element_quantity
        , cm.ref_unit AS element_ref_unit
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , ci.value AS indicator_value
        , i.ident AS indicator_ident
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
    FROM elca.project_transports            t
        JOIN elca.project_transport_means       m ON t.id = m.project_transport_id
        JOIN elca.process_configs              pc ON pc.id = m.process_config_id
        JOIN elca_cache.transport_means_v      cm ON m.id = cm.transport_mean_id
        JOIN elca_cache.indicators             ci ON cm.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_life_cycle_effects_v CASCADE;
CREATE VIEW elca_cache.report_life_cycle_effects_v AS
    SELECT cv.project_variant_id
        , ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.p_order AS indicator_p_order
        , l.name AS category
        , l.ident AS life_cycle_ident
        , l.phase AS life_cycle_phase
        , l.p_order AS life_cycle_p_order
    FROM elca_cache.project_variants cv
        JOIN elca_cache.indicators ci ON ci.item_id = cv.item_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
    WHERE ci.is_partial = false;



DROP VIEW IF EXISTS elca_cache.report_total_effects_v CASCADE;
CREATE VIEW elca_cache.report_total_effects_v AS
    SELECT ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.p_order AS indicator_p_order
        , v.project_variant_id
        , 'Gesamt'::varchar AS category
    FROM elca_cache.project_variants v
        JOIN elca_cache.indicators ci ON ci.item_id = v.item_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
    WHERE ci.life_cycle_ident = 'total';



-- @deprecated
DROP VIEW IF EXISTS elca_cache.report_total_effects_lc_usage_v CASCADE;
-- CREATE VIEW elca_cache.report_total_effects_lc_usage_v AS
--     SELECT l.item_id
--         , l.indicator_id
--         , l.name
--         , l.ident
--         , l.unit
--         , l.indicator_p_order
--         , l.project_variant_id
--         , 'Gesamt'::varchar AS category
--         , sum(l.value) AS value
--     FROM elca_cache.report_life_cycle_effects_v l
--         JOIN elca.project_variants v ON v.id = l.project_variant_id
--         LEFT JOIN elca.project_life_cycle_usages u ON u.project_id = v.project_id AND (u.life_cycle_ident = l.life_cycle_ident)
--     WHERE (l.life_cycle_ident = 'maint' OR true IN (use_in_construction, use_in_energy_demand))
--     GROUP BY l.item_id
--         , l.indicator_id
--         , l.name
--         , l.ident
--         , l.unit
--         , l.indicator_p_order
--         , l.project_variant_id;

--------------------------------------------------------------------------------



DROP VIEW IF EXISTS elca_cache.report_element_type_effects_v CASCADE;
CREATE VIEW elca_cache.report_element_type_effects_v AS
    SELECT ct.project_variant_id
        , ci.item_id
        , ci.indicator_id
        , ci.value
        , lc.phase AS life_cycle_phase
        , lc.ident AS life_cycle_ident
        , lc.name AS life_cycle_name
        , i.name AS name
        , i.unit AS unit
        , i.p_order AS indicator_p_order
        , t.name AS category
        , ct.level
        , coalesce(t.din_code, '000') AS din_code
        , t.node_id AS element_type_node_id
        , tt.id AS parent_element_type_node_id
    FROM elca_cache.element_types_v ct
        JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
        JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
        JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
    WHERE ct.level BETWEEN 1 AND 3;

--------------------------------------------------------------------------------


--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_construction_total_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_construction_total_effects_v AS
    SELECT ct.project_variant_id
        , ci.indicator_id
        , ci.value
    FROM elca_cache.element_types   ct
        JOIN elca.element_types_v        t ON t.level = 0 AND t.node_id = ct.element_type_node_id
        JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total';

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.ref_project_construction_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.ref_project_construction_effects_v AS
    SELECT p.process_db_id
        , ci.indicator_id
        , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
        , min(ci.value / (p.life_time * c.net_floor_space)) AS min
        , max(ci.value / (p.life_time * c.net_floor_space)) AS max
        , count(*) AS counter
    FROM elca.projects p
        JOIN elca.project_variants       v ON p.id = v.project_id
        JOIN elca.project_constructions  c ON v.id = c.project_variant_id
        JOIN elca_cache.element_types   ct ON ct.project_variant_id = v.id
        JOIN elca.element_types_v        t ON t.level = 1 AND t.node_id = ct.element_type_node_id
        JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total'
    WHERE p.is_reference = true
          AND ci.value > 0
    GROUP BY p.process_db_id
        , ci.indicator_id;


DROP VIEW IF EXISTS elca_cache.project_variant_process_config_mass_v;
CREATE OR REPLACE VIEW elca_cache.project_variant_process_config_mass_v AS
    SELECT e.project_variant_id
        , c.process_config_id
        , p.name
        , sum(cec.mass) AS mass
    FROM elca_cache.element_components cec
        JOIN elca.element_components c ON c.id = cec.element_component_id
        JOIN elca.elements e ON e.id = c.element_id
        JOIN elca.process_configs p ON p.id = c.process_config_id
    GROUP BY e.project_variant_id
        , c.process_config_id
        , p.name;



DROP VIEW IF EXISTS elca_cache.report_compare_total_and_life_cycle_effects_v;
CREATE VIEW elca_cache.report_compare_total_and_life_cycle_effects_v AS
    SELECT cva.project_variant_id AS project_variant_a_id
        , cvb.project_variant_id AS project_variant_b_id
        , i.id AS indicator_id
        , cia.value AS value_a
        , cib.value AS value_b
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.p_order AS indicator_p_order
        , l.name AS category
        , l.ident AS life_cycle_ident
        , l.phase AS life_cycle_phase
        , CASE WHEN l.ident = 'total' THEN 0 ELSE l.p_order END AS life_cycle_p_order
    FROM elca_cache.project_variants cva
        CROSS JOIN elca_cache.project_variants cvb
        JOIN elca_cache.indicators cia ON cia.item_id = cva.item_id
        JOIN elca_cache.indicators cib ON cib.item_id = cvb.item_id
        JOIN elca.indicators i ON i.id = cia.indicator_id AND i.id = cib.indicator_id
        JOIN elca.life_cycles l ON cia.life_cycle_ident = l.ident AND cib.life_cycle_ident = l.ident;


DROP VIEW IF EXISTS elca_cache.report_final_energy_ref_model_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.report_final_energy_ref_model_effects_v AS
    SELECT r.project_variant_id
        , ci.indicator_id
        , sum(ci.value) AS value
    FROM elca_cache.final_energy_ref_models cr
        JOIN elca.project_final_energy_ref_models r ON r.id = cr.final_energy_ref_model_id
        JOIN elca_cache.indicators              ci ON cr.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
    GROUP BY r.project_variant_id
        , ci.indicator_id;


DROP FUNCTION IF EXISTS elca_cache.update_cache();
CREATE OR REPLACE FUNCTION elca_cache.update_cache()
    RETURNS void
    --
    -- Updates all outdated components, elements and its ancestor element types
    --
AS $$

DECLARE
    r  record;
    parents int ARRAY;
    outdated int ARRAY;
    composites int ARRAY;
    variants int ARRAY;

BEGIN
    -- loop through all outdated element components
    -- and rebuild indicator totals
    FOR r IN SELECT item_id
             FROM elca_cache.element_components_v
             WHERE is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
    END LOOP;

    -- remove outdated mark on those components
    UPDATE elca_cache.items
    SET is_outdated = false
        , modified = now()
    WHERE type = 'Elca\Db\ElcaCacheElementComponent'
          AND is_outdated = true;

    -- loop through all outdated elements
    FOR r IN SELECT item_id
                 , parent_id
                 , CASE WHEN is_virtual THEN item_id
                   ELSE composite_item_id
                   END AS composite_item_id
                 , is_virtual
             FROM elca_cache.elements_v
             WHERE is_outdated = true
    LOOP
        IF NOT r.is_virtual THEN -- it is no composite element
            PERFORM elca_cache.aggregate_indicators(r.item_id);
            parents  := parents || r.parent_id;
            outdated := outdated || r.item_id;

            IF r.composite_item_id IS NOT NULL THEN
                composites := composites || r.composite_item_id;
            END IF;
        ELSE
            composites := composites || r.composite_item_id;
        END IF;
    END LOOP;

    -- aggregate element indicators on composite elements
    FOR r IN SELECT DISTINCT unnest(composites) AS composite_item_id

    LOOP
        PERFORM elca_cache.aggregate_composite_indicators(r.composite_item_id);
    END LOOP;

    -- update element mass on components
    UPDATE elca_cache.elements e
    SET mass = x.element_mass
    FROM elca_cache.element_mass_v x
    WHERE e.item_id = x.element_item_id
          AND e.item_id = ANY (outdated);

    -- update element mass on composite elements
    UPDATE elca_cache.elements e
    SET mass = x.element_mass
    FROM elca_cache.composite_element_mass_v x
    WHERE e.item_id = x.composite_item_id
          AND e.item_id = ANY (composites);

    -- update all outdated composites
    UPDATE elca_cache.items
    SET is_outdated = false
        , modified = now()
    WHERE id = ANY (composites)
          AND is_outdated = true;

    -- update tree for each (distinct) parent
    FOR r IN SELECT DISTINCT unnest(parents) AS parent

    LOOP
        variants := variants || elca_cache.update_element_type_tree(r.parent);
    END LOOP;

    -- update tree for element types which do not have child elements anymore
    FOR r IN SELECT DISTINCT unnest(variants) AS variant_item_id
    LOOP
        PERFORM elca_cache.update_element_type_tree(t.id)
        FROM elca_cache.project_variants v
            JOIN elca_cache.element_types_v t ON v.project_variant_id = t.project_variant_id
            LEFT JOIN elca_cache.elements_v e ON e.parent_id = t.item_id
        WHERE e.id IS NULL
              AND t.level = 3
              AND v.item_id = r.variant_item_id;
    END LOOP;

    -- loop through all outdated final_energy_demands
    FOR r IN SELECT item_id
                 , parent_id
             FROM elca_cache.final_energy_demands_v
             WHERE is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
        UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;
    END LOOP;

    -- loop through all outdated final_energy_supplies
    FOR r IN SELECT item_id
                 , parent_id
             FROM elca_cache.final_energy_supplies_v
             WHERE is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
        UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;
    END LOOP;

    -- loop through all outdated final_energy_ref_models
    FOR r IN SELECT item_id
             FROM elca_cache.final_energy_ref_models_v
             WHERE is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);
        UPDATE elca_cache.items SET is_outdated = false WHERE id = r.item_id;
    END LOOP;

    -- loop through all outdated transport means
    FOR r IN SELECT item_id
                 , parent_id
                 , is_virtual
                 , transport_mean_id
             FROM elca_cache.transport_means_v
             WHERE is_outdated = true
    LOOP
        PERFORM elca_cache.update_totals(r.item_id);

        IF NOT r.is_virtual THEN
            -- append to parent
            UPDATE elca_cache.items SET is_outdated = true WHERE id = r.parent_id;

        ELSE
            UPDATE elca_cache.items
            SET is_outdated = true
            WHERE id = (SELECT v.item_id FROM elca.project_transport_means m
                JOIN elca.project_transports      t ON t.id = m.project_transport_id
                JOIN elca_cache.project_variants  v ON t.project_variant_id = v.project_variant_id
            WHERE m.id = r.transport_mean_id);
        END IF;
    END LOOP;

    FOR r IN SELECT item_id
             FROM elca_cache.project_variants_v
             WHERE is_outdated = true
    LOOP
        PERFORM elca_cache.update_project_variant(r.item_id);
    END LOOP;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION elca_cache.find_or_create_element_type_item(in_node_id int, in_project_variant_id int) RETURNS int
AS $$

DECLARE
    r record;
    in_project_id int;
    parent_node_id int;
    parent_item_id int;
    element_type_item_id int;

BEGIN
    -- projectId
    SELECT project_id INTO in_project_id
    FROM elca.project_variants
    WHERE id = in_project_variant_id;

    -- parent node item id
    SELECT p_type.node_id
    INTO parent_node_id
    FROM elca.element_types_v c_type
        JOIN elca.element_types_v p_type ON c_type.lft BETWEEN p_type.lft AND p_type.rgt AND p_type.level = c_type.level - 1
    WHERE c_type.node_id = in_node_id;

    SELECT item_id
    INTO element_type_item_id
    FROM elca_cache.element_types_v
    WHERE element_type_node_id = in_node_id
          AND project_variant_id = in_project_variant_id;

    IF NOT FOUND THEN

        IF parent_node_id IS NULL THEN
            -- find or create cache project variant
            SELECT cv.item_id
            INTO parent_item_id
            FROM elca_cache.project_variants cv
            WHERE cv.project_variant_id = in_project_variant_id;

            IF NOT FOUND THEN
                INSERT INTO elca_cache.items (parent_id, type, is_outdated, project_id)
                VALUES (
                    null,
                    'Elca\Db\ElcaCacheProjectVariant',
                    true,
                    in_project_id
                ) RETURNING id INTO parent_item_id;

                INSERT INTO elca_cache.project_variants (item_id, project_variant_id)
                VALUES (parent_item_id, in_project_variant_id);
            END IF;
        ELSE
            SELECT elca_cache.find_or_create_element_type_item(parent_node_id, in_project_variant_id)
            INTO parent_item_id;
        END IF;

        INSERT INTO elca_cache.items (parent_id, type, is_outdated, project_id)
        VALUES (
            parent_item_id,
            'Elca\Db\ElcaCacheElementType',
            true,
            in_project_id
        ) RETURNING id INTO element_type_item_id;

        INSERT INTO elca_cache.element_types (item_id, project_variant_id, element_type_node_id, mass)
        VALUES (
            element_type_item_id,
            in_project_variant_id,
            in_node_id,
            0
        );

    END IF;

    RETURN element_type_item_id;

END;

$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION temp_populate_project_id() RETURNS void
AS $$
DECLARE
    r record;
    v int;

BEGIN

    FOR r IN SELECT v.project_id
                 , cv.item_id
             FROM elca.project_variants v
                 JOIN elca_cache.project_variants cv ON v.id = cv.project_variant_id
    LOOP

        WITH RECURSIVE cache_tree(id, parent_id, type) AS (
            SELECT v.id, v.parent_id, v.type
            FROM elca_cache.items v WHERE id = r.item_id
            UNION ALL
            SELECT i.id,
                i.parent_id,
                i.type
            FROM elca_cache.items i
                JOIN cache_tree       c ON i.parent_id = c.id
        )
        UPDATE elca_cache.items i
        SET project_id = r.project_id
        FROM cache_tree t
        WHERE i.id = t.id;

    END LOOP;

    WITH composite_cache_elements AS (
        SELECT ce.item_id AS composite_element_item_id
            , v.project_id
            , ct.item_id  AS element_type_item_id
        FROM elca.elements e
            JOIN elca.project_variants v ON v.id = e.project_variant_id
            JOIN elca_cache.element_types ct ON ct.element_type_node_id = e.element_type_node_id AND v.id = ct.project_variant_id
            JOIN elca_cache.elements ce ON ce.element_id = e.id
        WHERE e.is_composite AND e.project_variant_id IS NOT NULL
    )
    UPDATE elca_cache.items i
    SET is_virtual = true
        , project_id = c.project_id
        , parent_id = element_type_item_id
    FROM composite_cache_elements c
    WHERE i.id = c.composite_element_item_id;

    -- fix cache node tree for composite elements
    WITH stale_composite_elements AS (
        SELECT
            ce.item_id AS composite_element_item_id,
            e.element_type_node_id,
            v.id AS project_variant_id,
            v.project_id,
            cv.item_id AS variant_item_id,
            e.is_composite
        FROM elca.elements e
            JOIN elca.project_variants v ON v.id = e.project_variant_id
            JOIN elca_cache.project_variants cv ON v.id = cv.project_variant_id
            JOIN elca_cache.elements_v ce ON ce.element_id = e.id
            LEFT JOIN elca_cache.element_types ct ON ct.element_type_node_id = e.element_type_node_id AND
                                                     v.id = ct.project_variant_id
        WHERE e.is_composite AND e.project_variant_id IS NOT NULL
              AND ct.item_id IS NULL
    )
    UPDATE elca_cache.items i
    SET parent_id = elca_cache.find_or_create_element_type_item(x.element_type_node_id, x.project_variant_id)
        , is_virtual = true
        , project_id = x.project_id
        , is_outdated = true
    FROM stale_composite_elements x
    WHERE i.id = x.composite_element_item_id;


    WITH cache_transports AS (
        SELECT cm.item_id AS mean_item_id
            , v.project_id
            , cv.item_id  AS variant_item_id
            , t.calc_lca
        FROM elca.project_transports t
            JOIN elca.project_transport_means m ON t.id = m.project_transport_id
            JOIN elca.project_variants v ON v.id = t.project_variant_id
            JOIN elca_cache.project_variants cv ON cv.project_variant_id = v.id
            JOIN elca_cache.transport_means cm ON cm.transport_mean_id = m.id
    )
    UPDATE elca_cache.items i
    SET is_virtual = NOT c.calc_lca
        , project_id = c.project_id
        , parent_id = variant_item_id
        , is_outdated = c.calc_lca
    FROM cache_transports c
    WHERE i.id = c.mean_item_id;

    -- fix transport mean items in project variants which does not have an cache item. add them
    FOR r IN
    SELECT cm.item_id AS mean_item_id
        , v.project_id
        , cv.item_id  AS variant_item_id
        , t.calc_lca
        , t.project_variant_id
    FROM elca.project_transports t
        JOIN elca.project_transport_means m ON t.id = m.project_transport_id
        JOIN elca_cache.transport_means cm ON cm.transport_mean_id = m.id
        JOIN elca.project_variants v ON v.id = t.project_variant_id
        LEFT JOIN elca_cache.project_variants cv ON cv.project_variant_id = v.id
    WHERE cv.item_id IS NULL

    LOOP

        INSERT INTO elca_cache.items (type, is_outdated, project_id, is_virtual)
        VALUES (
            'Elca\Db\ElcaCacheProjectVariant' :: varchar,
            r.calc_lca,
            r.project_id,
            false
        )
        RETURNING id INTO v;

        INSERT INTO elca_cache.project_variants (item_id, project_variant_id)
        VALUES (v, r.project_variant_id);

        UPDATE elca_cache.items i
        SET is_virtual = NOT r.calc_lca
            , project_id = r.project_id
            , parent_id = v
            , is_outdated = r.calc_lca
        WHERE i.id = r.mean_item_id;
    END LOOP;


    WITH cache_energy_ref_models AS (
        SELECT cr.item_id AS ref_model_item_id
            , v.project_id
            , cv.item_id  AS variant_item_id
        FROM elca.project_final_energy_ref_models ref
            JOIN elca.project_variants v ON v.id = ref.project_variant_id
            JOIN elca_cache.project_variants cv ON cv.project_variant_id = v.id
            JOIN elca_cache.final_energy_ref_models cr ON cr.final_energy_ref_model_id = ref.id
    )
    UPDATE elca_cache.items i
    SET is_virtual = true
        , project_id = c.project_id
        , parent_id = variant_item_id
        , is_outdated = true
    FROM cache_energy_ref_models c
    WHERE i.id = c.ref_model_item_id;

END;

$$ LANGUAGE plpgsql;

SELECT * FROM temp_populate_project_id();
DROP FUNCTION temp_populate_project_id();

--ALTER TABLE elca_cache.items ALTER COLUMN "project_id" SET NOT NULL;


SELECT elca_cache.update_cache();
SELECT elca_cache.update_element_type_tree(id) FROM elca_cache.items WHERE is_outdated;
COMMIT;