BEGIN;
SELECT public.register_patch('fix-cache-report-total-effect-views', 'elca');

DROP VIEW IF EXISTS elca_cache.report_element_type_effects_v;
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
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
      WHERE lc.phase IN ('total', 'prod', 'maint', 'eol')
        AND ct.level < 3;

DROP VIEW IF EXISTS elca_cache.report_parent_element_type_effects_v;
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
          , tt.id AS parent_element_type_node_id
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
       JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
      WHERE lc.phase IN ('prod', 'maint', 'eol');

COMMIT;
