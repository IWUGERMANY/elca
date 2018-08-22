BEGIN;
SELECT public.register_patch('replace-report-views', 'elca');

DROP VIEW IF EXISTS elca_cache.report_effects_v CASCADE;
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

DROP VIEW IF EXISTS elca_cache.report_operation_effects_v CASCADE;
CREATE VIEW elca_cache.report_operation_effects_v AS
    SELECT f.id AS final_energy_demand_id
         , f.project_variant_id
         , cf.quantity AS element_quantity
         , cf.ref_unit AS element_ref_unit
         , pc.name AS element_name
         , ci.indicator_id AS indicator_id
         , ci.value AS indicator_value
         , i.name AS indicator_name
         , i.unit AS indicator_unit
         , i.p_order AS indicator_p_order
      FROM elca.project_final_energy_demands f
      JOIN elca.process_configs              pc ON pc.id = f.process_config_id
      JOIN elca_cache.final_energy_demands_v cf ON f.id = cf.final_energy_demand_id
      JOIN elca_cache.indicators             ci ON cf.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
      JOIN elca.indicators                    i ON i.id = ci.indicator_id;

COMMIT;
