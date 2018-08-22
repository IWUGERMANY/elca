BEGIN;
SELECT public.register_patch('add-opaque-elements', 'elca');

DROP VIEW IF EXISTS elca_cache.report_assets_v;
DROP VIEW IF EXISTS elca_cache.report_top_assets_v;
DROP VIEW IF EXISTS elca_cache.report_effects_v;
DROP VIEW IF EXISTS elca_cache.report_parent_element_type_effects_v;
DROP VIEW IF EXISTS elca_cache.report_element_type_effects_v;
DROP VIEW IF EXISTS elca.element_types_v;

ALTER TABLE elca.element_types ADD "is_opaque" boolean;
ALTER TABLE elca.element_types ADD "pref_ref_unit" varchar(10);

UPDATE elca.element_types
   SET is_opaque = false
 WHERE din_code IN (334, 362, 344); -- Außentüren und -fenster, Dachfenster, Dachöffnungen, Innentüren und -fenster

CREATE VIEW elca.element_types_v AS
  SELECT n.*
       , e.*
  FROM public.nested_nodes n
  JOIN elca.element_types e ON n.id = e.node_id;

CREATE VIEW elca_cache.report_effects_v AS
    SELECT e.id AS element_id
         , e.project_variant_id
         , e.name AS element_name
         , e.quantity AS element_quantity
         , e.ref_unit AS element_ref_unit
         , e.element_type_node_id
         , t.din_code AS element_type_din_code
         , t.name AS element_type_name
         , t.is_constructional AS element_type_is_constructional
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
     WHERE l.phase IN ('total', 'prod', 'maint', 'eol')
  GROUP BY e.id
         , e.project_variant_id
         , e.name
         , e.quantity
         , e.ref_unit
         , e.element_type_node_id
         , t.din_code
         , t.name
         , t.is_constructional
         , tt.name
         , tt.din_code
         , l.phase
         , ci.indicator_id
         , i.name
         , i.unit
         , i.p_order;

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
         , c.layer_size AS component_size
         , c.quantity AS component_quantity
         , c.life_time AS component_life_time
         , c.layer_position AS component_layer_position
         , c.layer_area_ratio AS component_layer_area_ratio
         , a.process_db_id
         , a.name_orig     AS process_name_orig
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

CREATE VIEW elca_cache.report_parent_element_type_effects_v AS
     SELECT ct.project_variant_id
          , ci.item_id
          , ci.indicator_id
          , ci.value
          , lc.phase AS life_cycle_phase
          , i.name AS name
          , i.unit AS unit
          , i.p_order AS indicator_p_order
          , t.din_code ||' '||t.name AS category
          , t.din_code
          , t.node_id AS element_type_node_id
          , tt.id AS parent_element_type_node_id
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
       JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
      WHERE lc.phase IN ('prod', 'maint', 'eol');

CREATE VIEW elca_cache.report_element_type_effects_v AS
     SELECT ct.project_variant_id
          , ci.item_id
          , ci.indicator_id
          , ci.value
          , lc.phase AS life_cycle_phase
          , i.name AS name
          , i.unit AS unit
          , i.p_order AS indicator_p_order
          , t.din_code ||' '||t.name AS category
          , coalesce(t.din_code, '000') AS din_code
          , t.node_id AS element_type_node_id
          , tt.id AS parent_element_type_node_id
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
       JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
      WHERE lc.phase IN ('total', 'prod', 'maint', 'eol')
        AND ct.level BETWEEN 1 AND 2;

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
         , a.process_db_id
         , a.name_orig     AS process_name_orig
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


COMMIT;
