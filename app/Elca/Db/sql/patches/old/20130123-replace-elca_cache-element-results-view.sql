BEGIN;
SELECT public.register_patch('replace-elca_cache-element_results-view', 'elca');

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

COMMIT;
