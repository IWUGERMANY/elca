BEGIN;
SELECT public.register_patch('20170517-additional-indicators.sql', 'eLCA');

ALTER TABLE elca.indicators ADD "is_hidden" boolean NOT NULL DEFAULT false;

UPDATE elca.indicators
    SET is_hidden = true WHERE is_excluded;

UPDATE elca.indicators
    set is_excluded = false WHERE ident IN ('fw', 'hwd', 'mfr');

DROP VIEW IF EXISTS elca_cache.report_compare_total_and_life_cycle_effects_v;
DROP VIEW IF EXISTS elca_cache.report_element_type_effects_v;
DROP VIEW IF EXISTS elca_cache.report_total_energy_recycling_potential;
DROP VIEW IF EXISTS elca_cache.report_total_construction_recycling_effects_v;
DROP VIEW IF EXISTS elca_cache.report_life_cycle_effects_v;
DROP VIEW IF EXISTS elca_cache.report_total_effects_v;
DROP VIEW IF EXISTS elca_cache.report_transport_effects_v;
DROP VIEW IF EXISTS elca_cache.report_final_energy_supply_effects_v;
DROP VIEW IF EXISTS elca_cache.report_final_energy_demand_effects_v;
DROP VIEW IF EXISTS elca_cache.report_effects_v;
DROP VIEW IF EXISTS elca_cache.report_composite_element_process_config_effects_v;
DROP VIEW IF EXISTS elca_cache.report_element_process_config_effects_v;
DROP VIEW IF EXISTS elca_cache.report_top_process_config_effects_v;
DROP VIEW IF EXISTS elca_cache.report_top_process_effects_v;
DROP VIEW IF EXISTS elca_cache.indicator_results_v;
DROP VIEW IF EXISTS elca_cache.indicators_totals_v;
DROP VIEW IF EXISTS elca_cache.composite_indicators_aggregate_v;
DROP VIEW IF EXISTS elca_cache.indicators_aggregate_v;
DROP VIEW IF EXISTS elca_cache.indicators_v;
DROP VIEW IF EXISTS elca.indicators_v;

CREATE OR REPLACE VIEW elca.indicators_v AS
    SELECT DISTINCT
        i.*
        , p.process_db_id
    FROM elca.indicators i
        JOIN elca.process_indicators pi ON i.id = pi.indicator_id
        JOIN elca.processes p ON p.id = pi.process_id;


CREATE OR REPLACE VIEW elca_cache.indicators_v AS
    SELECT i.*
        , ii.*
    FROM elca_cache.indicators i
        JOIN elca_cache.items      ii ON ii.id = i.item_id;


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
        , i.is_hidden
        , i.p_order AS indicator_p_order
    FROM elca_cache.indicators ci
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
        LEFT JOIN elca.processes p ON ci.process_id = p.id;

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

CREATE VIEW elca_cache.report_top_process_config_effects_v AS
    SELECT e.project_variant_id
        , c.process_config_id
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , l.phase AS life_cycle_phase
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.is_hidden
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
        , i.is_hidden
        , i.p_order;

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
        , l.ident AS life_cycle_ident
        , l.name AS life_cycle_name
        , i.name AS indicator_name
        , i.ident AS indicator_ident
        , i.unit AS indicator_unit
        , i.is_hidden
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
        , l.ident AS life_cycle_ident
        , l.name AS life_cycle_name
        , i.name AS indicator_name
        , i.ident AS indicator_ident
        , i.unit AS indicator_unit
        , i.is_hidden
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
        , i.is_hidden
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
        , i.is_hidden
        , i.p_order;

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
        , i.is_hidden
        , i.p_order AS indicator_p_order
    FROM elca.project_final_energy_demands f
        JOIN elca.process_configs              pc ON pc.id = f.process_config_id
        JOIN elca_cache.final_energy_demands_v cf ON f.id = cf.final_energy_demand_id
        JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;


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
        , i.is_hidden
        , i.p_order AS indicator_p_order
    FROM elca.project_final_energy_supplies f
        JOIN elca.process_configs              pc ON pc.id = f.process_config_id
        JOIN elca_cache.final_energy_supplies_v cf ON f.id = cf.final_energy_supply_id
        JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'D'
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;

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
        , i.is_hidden
        , i.p_order AS indicator_p_order
    FROM elca.project_transports            t
        JOIN elca.project_transport_means       m ON t.id = m.project_transport_id
        JOIN elca.process_configs              pc ON pc.id = m.process_config_id
        JOIN elca_cache.transport_means_v      cm ON m.id = cm.transport_mean_id
        JOIN elca_cache.indicators             ci ON cm.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;

CREATE VIEW elca_cache.report_total_effects_v AS
    SELECT ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , v.project_variant_id
        , 'Gesamt'::varchar AS category
    FROM elca_cache.project_variants v
        JOIN elca_cache.indicators ci ON ci.item_id = v.item_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON l.ident = ci.life_cycle_ident
    WHERE ci.life_cycle_ident = 'total';

CREATE VIEW elca_cache.report_life_cycle_effects_v AS
    SELECT cv.project_variant_id
        , ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.is_hidden
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

CREATE VIEW elca_cache.report_total_construction_recycling_effects_v AS
    SELECT ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , v.project_variant_id
        , 'D stofflich'::varchar AS category
        , l.p_order AS life_cycle_p_order
        , l.ident AS life_cycle_ident
    FROM elca_cache.element_types_v v
        JOIN elca_cache.indicators ci ON ci.item_id = v.item_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON l.ident = ci.life_cycle_ident
    WHERE v.level = 0
          AND l.phase = 'rec';

CREATE VIEW elca_cache.report_total_energy_recycling_potential AS
    SELECT null::int AS item_id
        , t.indicator_id
        , t.name
        , t.ident
        , t.unit
        , t.is_hidden
        , t.indicator_p_order
        , t.project_variant_id
        , 'D energetisch'::varchar AS category
        , r.life_cycle_p_order
        , r.life_cycle_ident
        , t.value - r.value AS value
    FROM elca_cache.report_life_cycle_effects_v t
        JOIN elca_cache.report_total_construction_recycling_effects_v r ON t.project_variant_id = r.project_variant_id
                                                                           AND t.indicator_id = r.indicator_id
    WHERE t.life_cycle_phase = 'rec';

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
        , i.is_hidden
        , i.p_order AS indicator_p_order
        , t.name AS category
        , ct.level
        , coalesce(t.din_code, '000') AS din_code
        , t.node_id AS element_type_node_id
        , tt.id AS parent_element_type_node_id
    FROM elca_cache.element_types_v ct
        JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
        JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
        LEFT JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1;

CREATE VIEW elca_cache.report_compare_total_and_life_cycle_effects_v AS
    SELECT cva.project_variant_id AS project_variant_a_id
        , cvb.project_variant_id AS project_variant_b_id
        , i.id AS indicator_id
        , cia.value AS value_a
        , cib.value AS value_b
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.is_hidden
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


COMMIT;