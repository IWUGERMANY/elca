BEGIN;
SELECT public.register_patch('add-elca_cache-report_top_process_config_effects-view', 'elca');


DROP VIEW IF EXISTS elca_cache.report_top_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_top_process_config_effects_v AS
    SELECT e.project_variant_id
         , a.process_db_id
         , a.process_config_id AS process_config_id
         , pc.name AS process_config_name
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
         , a.process_config_id
         , pc.name
         , cc.ref_unit
         , ci.indicator_id
         , l.ident
         , i.name
         , i.unit
         , i.p_order;

COMMIT;
