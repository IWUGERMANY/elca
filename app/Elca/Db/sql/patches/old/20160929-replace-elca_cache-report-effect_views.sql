BEGIN;
SELECT public.register_patch('replace-elca_cache-report_effect_views', 'elca_cache');
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
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
        LEFT JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1;


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
        , l.ident AS life_cycle_ident
        , ci.indicator_id AS indicator_id
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.p_order AS indicator_p_order
        , ci.value AS indicator_value
    FROM elca.elements e
        JOIN elca.element_types_v   t ON e.element_type_node_id = t.node_id
        JOIN elca.element_types_v  tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
        JOIN elca_cache.elements_v ce ON e.id = ce.element_id
        JOIN elca_cache.indicators ci ON ce.item_id = ci.item_id
        JOIN elca.life_cycles       l ON l.ident = ci.life_cycle_ident
        JOIN elca.indicators        i ON i.id = ci.indicator_id
       WHERE l.phase IN ('total', 'prod', 'maint', 'eol', 'rec');

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
        , l.ident AS life_cycle_ident
        , l.name AS life_cycle_name
        , i.name AS indicator_name
        , i.ident AS indicator_ident
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
        , l.ident AS life_cycle_ident
        , l.name AS life_cycle_name
        , i.name AS indicator_name
        , i.ident AS indicator_ident
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
        JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'D'
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;

COMMIT;