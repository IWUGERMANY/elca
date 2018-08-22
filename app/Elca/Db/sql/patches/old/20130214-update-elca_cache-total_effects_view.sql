BEGIN;
SELECT public.register_patch('update-elca_cache-total_effects_view', 'elca');

DROP VIEW IF EXISTS elca_cache.report_total_effects_v;
CREATE VIEW elca_cache.report_total_effects_v AS
     SELECT ci.item_id
          , ci.indicator_id
          , ci.value
          , i.name AS name
          , i.ident AS ident
          , i.unit AS unit
          , i.p_order AS indicator_p_order
          , t.project_variant_id
          , 'Gesamt' AS category
       FROM elca_cache.element_types_v t
       JOIN elca_cache.indicators ci ON ci.item_id = t.item_id
       JOIN elca.indicators i ON i.id = ci.indicator_id
      WHERE ci.life_cycle_ident = 'total'
        AND t.level = 0;

COMMIT;
