BEGIN;
SELECT public.register_patch('fix-reports-add-rec-phase', 'elca');

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

DROP VIEW IF EXISTS elca_cache.report_element_type_effects_v;
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
  WHERE lc.phase IN ('total', 'prod', 'maint', 'eol', 'rec')
        AND ct.level BETWEEN 1 AND 2;


DROP VIEW IF EXISTS elca_cache.report_parent_element_type_effects_v;
CREATE VIEW elca_cache.report_parent_element_type_effects_v AS
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
  WHERE lc.phase IN ('prod', 'maint', 'eol', 'rec');

DROP VIEW IF EXISTS elca_cache.report_compare_element_type_effects_v;
CREATE VIEW elca_cache.report_compare_element_type_effects_v AS
  SELECT cta.project_variant_id AS project_variant_a_id
    , ctb.project_variant_id AS project_variant_b_id
    , i.id AS indicator_id
    , cia.value AS value_a
    , cib.value AS value_b
    , lc.ident AS life_cycle_ident
    , lc.name AS life_cycle_name
    , lc.phase AS life_cycle_phase
    , i.name AS name
    , i.unit AS unit
    , i.p_order AS indicator_p_order
    , t.din_code ||' '||t.name AS category
    , cta.level AS level
    , coalesce(t.din_code, '000') AS din_code
    , t.node_id AS element_type_node_id
    , tt.id AS parent_element_type_node_id
  FROM elca_cache.element_types_v cta
    CROSS JOIN elca_cache.element_types_v ctb
    JOIN elca_cache.indicators cia ON cia.item_id = cta.item_id
    JOIN elca_cache.indicators cib ON cib.item_id = ctb.item_id
    JOIN elca.element_types t ON t.node_id = cta.element_type_node_id AND t.node_id = ctb.element_type_node_id
    JOIN elca.element_types_v tt ON cta.lft BETWEEN tt.lft AND tt.rgt AND tt.level = cta.level - 1
                                    AND ctb.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ctb.level - 1
    JOIN elca.indicators i ON i.id = cia.indicator_id AND i.id = cib.indicator_id
    JOIN elca.life_cycles lc ON lc.ident = cia.life_cycle_ident AND cib.life_cycle_ident = lc.ident
  WHERE lc.phase IN ('total', 'prod', 'maint', 'eol', 'rec')
        AND cta.level = ctb.level
        AND cta.level BETWEEN 1 AND 3;

COMMIT;