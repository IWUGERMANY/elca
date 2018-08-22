BEGIN;
SELECT public.register_patch('add-report-views', 'elca');

DROP VIEW IF EXISTS elca_cache.report_total_effects_v;
CREATE VIEW elca_cache.report_total_effects_v AS
     SELECT ci.item_id
          , ci.indicator_id
          , ci.value
          , i.name AS name
          , i.unit AS unit
          , i.p_order AS indicator_p_order
          , t.project_variant_id
          , 'Gesamt' AS category
       FROM elca_cache.element_types_v t
       JOIN elca_cache.indicators ci ON ci.item_id = t.item_id
       JOIN elca.indicators i ON i.id = ci.indicator_id
      WHERE ci.life_cycle_ident = 'total'
        AND t.level = 0;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_element_type_effects_v;
CREATE VIEW elca_cache.report_element_type_effects_v AS
     SELECT ct.project_variant_id
          , ci.item_id
          , ci.indicator_id
          , ci.value
          , i.name AS name
          , i.unit AS unit
          , i.p_order AS indicator_p_order
          , t.din_code ||' '||t.name AS category
          , t.din_code
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.element_types t ON t.node_id = ct.element_type_node_id
       JOIN elca.indicators i ON i.id = ci.indicator_id
      WHERE ci.life_cycle_ident = 'total'
        AND ct.level = 2;

--------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca_cache.report_life_cycle_effects_v;
CREATE VIEW elca_cache.report_life_cycle_effects_v AS
     SELECT ct.project_variant_id
          , ci.item_id
          , ci.indicator_id
          , ci.value
          , i.name AS name
          , i.unit AS unit
          , i.p_order AS indicator_p_order
          , l.name AS category
          , l.p_order AS life_cycle_p_order
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
      WHERE ct.level = 0
        AND ci.is_partial = false;

COMMIT;
