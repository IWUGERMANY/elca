BEGIN;
SELECT public.register_patch('add-transport-report-views', 'elca');

DROP VIEW IF EXISTS elca_cache.report_transport_assets_v;
CREATE VIEW elca_cache.report_transport_assets_v AS
  SELECT tm.id AS transport_mean_id
       , t.project_variant_id
       , t.id AS transport_id
       , t.name AS transport_name
       , t.quantity AS transport_quantity
       , pc.name AS process_config_name
       , p.id AS process_id
       , p.process_db_id
       , p.name_orig AS process_name_orig
       , p.scenario_id AS process_scenario_id
       , p.ref_value AS process_ref_value
       , p.ref_unit AS process_ref_unit
       , p.uuid AS process_uuid
       , p.life_cycle_description AS life_cycle_description
       , p.life_cycle_ident AS life_cycle_ident
       , p.life_cycle_phase AS life_cycle_phase
       , p.life_cycle_p_order AS life_cycle_p_order
       , ct.quantity AS total
       , ct.ref_unit AS total_unit
    FROM elca.project_transport_means tm
    JOIN elca.project_transports t ON t.id = tm.project_transport_id
    JOIN elca_cache.transport_means ct ON tm.id = ct.transport_mean_id
    JOIN elca.process_configs pc ON pc.id = tm.process_config_id
    JOIN elca.process_assignments_v p ON p.process_config_id = tm.process_config_id AND p.life_cycle_ident = 'A4';


DROP VIEW IF EXISTS elca_cache.report_transport_effects_v CASCADE;
CREATE VIEW elca_cache.report_transport_effects_v AS
  SELECT m.id AS transport_mean_id
    , t.id AS transport_id
    , t.name AS element_name
    , t.project_variant_id
    , cm.quantity AS element_quantity
    , cm.ref_unit AS element_ref_unit
    , pc.name AS process_config_name
    , ci.indicator_id AS indicator_id
    , ci.value AS indicator_value
    , i.ident AS indicator_ident
    , i.name AS indicator_name
    , i.unit AS indicator_unit
    , i.p_order AS indicator_p_order
  FROM elca.project_transports            t
    JOIN elca.project_transport_means       m ON t.id = m.project_transport_id
    JOIN elca.process_configs              pc ON pc.id = m.process_config_id
    JOIN elca_cache.transport_means_v      cm ON m.id = cm.transport_mean_id
    JOIN elca_cache.indicators             ci ON cm.item_id = ci.item_id AND ci.life_cycle_ident = 'total'
    JOIN elca.indicators                    i ON i.id = ci.indicator_id;

COMMIT;
