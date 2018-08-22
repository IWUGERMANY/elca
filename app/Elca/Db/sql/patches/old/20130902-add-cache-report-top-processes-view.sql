BEGIN;
SELECT public.register_patch('add-cache-report-top-processes-view', 'elca');

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
          , tt.id AS parent_element_type_node_id
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles lc ON lc.ident = ci.life_cycle_ident
       LEFT JOIN elca.element_types_v tt ON ct.lft BETWEEN tt.lft AND tt.rgt AND tt.level = ct.level - 1
      WHERE lc.phase IN ('total', 'prod', 'maint', 'eol')
        AND ct.level < 3;

DROP VIEW IF EXISTS elca_cache.report_top_process_effects_v CASCADE;
CREATE VIEW elca_cache.report_top_process_effects_v AS
    SELECT e.project_variant_id
         , a.process_db_id
         , a.id AS process_id
         , a.name_orig AS process_name_orig
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
         , cc.ref_unit
         , ci.indicator_id
         , l.ident
         , i.name
         , i.unit
         , i.p_order;

COMMIT;
