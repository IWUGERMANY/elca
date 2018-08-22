BEGIN;
SELECT public.register_patch('add-more-report-views', 'elca');

DROP VIEW IF EXISTS elca_cache.report_top_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_top_process_config_effects_v AS
    SELECT e.project_variant_id
         , c.process_config_id
         , pc.name AS process_config_name
         , ci.indicator_id AS indicator_id
         , l.phase AS life_cycle_phase
         , i.name AS indicator_name
         , i.unit AS indicator_unit
         , i.p_order AS indicator_p_order
         , cc.ref_unit
         , sum(cc.quantity) AS quantity
         , sum(ci.value)    AS indicator_value
      FROM elca.elements                 e
      JOIN elca.element_components       c  ON c.element_id  = e.id
      JOIN elca.process_configs          pc ON pc.id = c.process_config_id
      JOIN elca_cache.element_components cc ON c.id  = cc.element_component_id
      JOIN elca_cache.indicators         ci ON cc.item_id = ci.item_id
      JOIN elca.indicators               i  ON i.id = ci.indicator_id
      JOIN elca.life_cycles              l  ON l.ident = ci.life_cycle_ident
     WHERE l.phase = 'total'
  GROUP BY e.project_variant_id
         , c.process_config_id
         , pc.name
         , cc.ref_unit
         , ci.indicator_id
         , l.phase
         , i.name
         , i.unit
         , i.p_order;

DROP VIEW IF EXISTS elca_cache.report_element_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_element_process_config_effects_v AS
    SELECT c.element_id
         , c.id AS element_component_id
         , c.process_config_id
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
     WHERE l.phase IN ('maint', 'prod', 'eol');


DROP VIEW IF EXISTS elca_cache.report_composite_element_process_config_effects_v CASCADE;
CREATE VIEW elca_cache.report_composite_element_process_config_effects_v AS
    SELECT a.composite_element_id
         , e.id AS element_id
         , e.name AS element_name
         , c.id AS element_component_id
         , c.process_config_id
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
     WHERE l.phase IN ('maint', 'prod', 'eol');

COMMIT;
