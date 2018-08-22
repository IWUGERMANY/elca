BEGIN;
SELECT public.register_patch('alter-elemenet_components-add-is_extant', 'elca');

ALTER TABLE elca.element_components ADD "is_extant" boolean NOT NULL DEFAULT false;

DROP VIEW IF EXISTS elca_cache.element_mass_v;
DROP VIEW IF EXISTS elca.element_components_v;
DROP VIEW IF EXISTS elca.element_layers_v;
DROP VIEW IF EXISTS elca.element_single_components_v;
DROP VIEW IF EXISTS elca_cache.element_components_v;
DROP VIEW IF EXISTS elca_cache.report_assets_v;
DROP VIEW IF EXISTS elca_cache.report_top_assets_v;
DROP VIEW IF EXISTS elca_cache.report_element_process_config_effects_v;
DROP VIEW IF EXISTS elca_cache.report_composite_element_process_config_effects_v;

CREATE VIEW elca.element_components_v AS
  SELECT c.*
    , e.name AS element_name
  FROM elca.element_components c
    JOIN elca.elements e ON e.id = c.element_id;

CREATE VIEW elca.element_layers_v AS
  SELECT *
  FROM elca.element_components
  WHERE is_layer = true;

CREATE VIEW elca.element_single_components_v AS
  SELECT *
  FROM elca.element_components
  WHERE is_layer = false;

CREATE OR REPLACE VIEW elca_cache.element_components_v AS
  SELECT i.*
    , e.*
  FROM elca_cache.element_components e
    JOIN elca_cache.items    i ON i.id = e.item_id;

CREATE OR REPLACE VIEW elca_cache.element_mass_v AS
  SELECT parent_id AS element_item_id
    , sum(coalesce(mass, 0)) AS element_mass
  FROM elca_cache.element_components_v
  GROUP BY parent_id;

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
  FROM elca.elements e
    JOIN elca_cache.elements        ce ON e.id = ce.element_id
    JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
    JOIN elca.element_types_v       tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
    JOIN elca.element_components    c  ON e.id = c.element_id
    JOIN elca_cache.element_components cc ON c.id = cc.element_component_id
    JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
    JOIN elca.process_configs       pc ON pc.id = a.process_config_id;


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
  WHERE l.phase IN ('maint', 'prod', 'eol', 'total');

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
  WHERE l.phase IN ('maint', 'prod', 'eol', 'total');


COMMIT;