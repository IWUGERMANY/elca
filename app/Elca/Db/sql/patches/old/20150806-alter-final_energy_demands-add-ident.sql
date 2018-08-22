BEGIN;
SELECT public.register_patch('alter-final_energy_demands-add-ident', 'elca');

DROP VIEW IF EXISTS elca_cache.report_final_energy_demand_assets_v;
DROP VIEW IF EXISTS elca_cache.report_final_energy_demand_effects_v;

ALTER TABLE elca.project_final_energy_demands
    ADD "ident" varchar(30);


CREATE VIEW elca_cache.report_final_energy_demand_assets_v AS
    SELECT f.id
        , f.project_variant_id
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
        , f.ident
        , f.heating
        , f.water
        , f.lighting
        , f.ventilation
        , f.cooling
        , cf.quantity AS total
        , cf.ref_unit AS total_unit
    FROM elca.project_final_energy_demands f
        JOIN elca_cache.final_energy_demands cf ON f.id = cf.final_energy_demand_id
        JOIN elca.process_configs pc ON pc.id = f.process_config_id
        JOIN elca.process_assignments_v p ON p.process_config_id = f.process_config_id AND p.life_cycle_phase = 'op';


CREATE VIEW elca_cache.report_final_energy_demand_effects_v AS
    SELECT f.id
        , f.project_variant_id
        , f.ident
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