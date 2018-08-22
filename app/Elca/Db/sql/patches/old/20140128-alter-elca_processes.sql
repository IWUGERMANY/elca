BEGIN;
SELECT public.register_patch('alter-elca_processes', 'elca');

CREATE TABLE elca.process_scenarios
(
   "id"                 serial          NOT NULL                -- scenarioId
 , "process_config_id"  int             NOT NULL                -- processConfigId
 , "ident"              varchar(250)    NOT NULL                -- ident
 , "group_ident"        varchar(250)                            -- groupIdent
 , "is_default"         boolean         NOT NULL DEFAULT false  -- default scenario for the specified group
 , "description"        text                                    -- scenario description
 , PRIMARY KEY ("id")
 , UNIQUE ("process_config_id", "ident")
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);     

DROP VIEW IF EXISTS elca_cache.report_top_process_effects_v;
DROP VIEW IF EXISTS elca_cache.report_operation_assets_v;
DROP VIEW IF EXISTS elca_cache.report_top_assets_v;
DROP VIEW IF EXISTS elca_cache.report_assets_v;
DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
DROP VIEW IF EXISTS elca.process_assignments_v;
DROP VIEW IF EXISTS elca.processes_v;

ALTER TABLE elca.processes ADD COLUMN "date_of_last_revision" timestamptz(0);
ALTER TABLE elca.processes ADD COLUMN "scenario_id" int;
ALTER TABLE elca.processes ADD FOREIGN KEY ("scenario_id") REFERENCES elca.process_scenarios ("id") ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE elca.processes DROP CONSTRAINT "processes_uuid_process_db_id_key";
ALTER TABLE elca.processes ADD UNIQUE ("process_db_id", "uuid", "life_cycle_ident", "scenario_id");

CREATE VIEW elca.processes_v AS
    SELECT p.*
         , l.name AS life_cycle_name
         , l.phase AS life_cycle_phase
         , l.p_order AS life_cycle_p_order
         , l.description AS life_cycle_description
    FROM elca.processes p
    JOIN elca.life_cycles l ON l.ident = p.life_cycle_ident;

CREATE VIEW elca.process_assignments_v AS
    SELECT p.*
         , a.id AS process_life_cycle_assignment_id
         , a.process_config_id
         , a.ratio
    FROM elca.processes_v p
    JOIN elca.process_life_cycle_assignments a ON p.id = a.process_id;

CREATE VIEW elca.process_configs_extended_search_v AS
    SELECT pc.id
         , pc.process_category_node_id
         , pc.name
         , pc.life_time_info
         , pc.min_life_time
         , pc.min_life_time_info
         , pc.avg_life_time
         , pc.avg_life_time_info
         , pc.max_life_time
         , pc.max_life_time_info
         , pc.density
         , pc.thermal_conductivity
         , pc.thermal_resistance
         , pc.is_reference
         , pc.f_hs_hi
         , pc.uuid
         , pc.created
         , pc.modified
         , to_tsvector('german', pc.name ||' '||
           coalesce(array_to_string(array_agg(DISTINCT p.uuid::text), ' '), '') ||' '||
           coalesce(array_to_string(array_agg(DISTINCT p.name_orig), ' '), '')) AS search_vector
      FROM elca.process_configs pc
      LEFT JOIN elca.process_assignments_v p ON pc.id = p.process_config_id
  GROUP BY pc.id
         , pc.process_category_node_id
         , pc.name
         , pc.life_time_info
         , pc.min_life_time
         , pc.min_life_time_info
         , pc.avg_life_time
         , pc.avg_life_time_info
         , pc.max_life_time
         , pc.max_life_time_info
         , pc.density
         , pc.thermal_conductivity
         , pc.thermal_resistance
         , pc.is_reference
         , pc.f_hs_hi
         , pc.uuid
         , pc.created
         , pc.modified;

CREATE VIEW elca_cache.report_assets_v AS
    SELECT e.project_variant_id
         , t.din_code AS element_type_din_code
         , t.name AS element_type_name
         , tt.name AS element_type_parent_name
         , tt.din_code AS element_type_parent_din_code
         , t.is_constructional AS element_type_is_constructional
         , t.pref_has_element_image AS has_element_image
         , e.id   AS element_id
         , e.name AS element_name
         , e.quantity AS element_quantity
         , e.ref_unit AS element_ref_unit
         , ce.quantity AS cache_element_quantity
         , ce.ref_unit AS cache_element_ref_unit
         , ce.mass AS element_mass
         , c.id AS element_component_id
         , c.is_layer AS component_is_layer
         , c.layer_size AS component_size
         , c.quantity AS component_quantity
         , c.life_time AS component_life_time
         , c.layer_position AS component_layer_position
         , c.layer_area_ratio AS component_layer_area_ratio
         , a.process_db_id
         , a.name_orig     AS process_name_orig
         , a.scenario_id AS process_scenario_id
         , a.ref_value AS process_ref_value
         , a.ref_unit AS process_ref_unit
         , a.uuid     AS process_uuid
         , a.life_cycle_description AS process_life_cycle_description
         , a.life_cycle_ident AS process_life_cycle_ident
         , a.life_cycle_p_order AS process_life_cycle_p_order
         , a.ratio AS process_ratio
         , pc.name AS process_config_name
         , cc.quantity AS cache_component_quantity
         , cc.ref_unit AS cache_component_ref_unit
         , cc.num_replacements AS cache_component_num_replacements
      FROM elca.elements e
      JOIN elca_cache.elements        ce ON e.id = ce.element_id
      JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
      JOIN elca.element_types_v       tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
      JOIN elca.element_components    c  ON e.id = c.element_id
      JOIN elca_cache.element_components cc ON c.id = cc.element_component_id
      JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
      JOIN elca.process_configs       pc ON pc.id = a.process_config_id;

--------------------------------------------------------------------------------

CREATE VIEW elca_cache.report_top_assets_v AS
    SELECT e.project_variant_id
         , t.din_code AS element_type_din_code
         , t.name AS element_type_name
         , e.id   AS element_id
         , e.name AS element_name
         , e.quantity AS element_quantity
         , e.ref_unit AS element_ref_unit
         , ce.quantity AS cache_element_quantity
         , ce.ref_unit AS cache_element_ref_unit
         , ce.mass AS element_mass
         , c.id AS element_component_id
         , c.is_layer AS component_is_layer
         , c.layer_position AS component_layer_position
         , a.process_db_id
         , a.name_orig     AS process_name_orig
         , a.scenario_id AS process_scenario_id
         , a.ref_value AS process_ref_value
         , a.ref_unit AS process_ref_unit
         , a.life_cycle_description AS process_life_cycle_description
         , a.life_cycle_phase AS process_life_cycle_phase
         , a.ratio AS process_ratio
         , pc.name AS process_config_name
         , cc.quantity AS cache_component_quantity
         , cc.ref_unit AS cache_component_ref_unit
         , cc.mass AS cache_component_mass
         , cc.num_replacements AS cache_component_num_replacements
      FROM elca.elements e
      JOIN elca_cache.elements        ce ON e.id = ce.element_id
      JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
      JOIN elca.element_components    c  ON e.id = c.element_id
      JOIN elca_cache.element_components cc ON c.id = cc.element_component_id
      JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
      JOIN elca.process_configs       pc ON pc.id = a.process_config_id;

CREATE VIEW elca_cache.report_operation_assets_v AS
   SELECT f.id AS final_energy_demand_id
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

--------------------------------------------------------------------------------

CREATE VIEW elca_cache.report_top_process_effects_v AS
    SELECT e.project_variant_id
         , a.process_db_id
         , a.id AS process_id
         , a.name_orig AS process_name_orig
         , a.scenario_id AS process_scenario_id
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
         , a.scenario_id
         , cc.ref_unit
         , ci.indicator_id
         , l.ident
         , i.name
         , i.unit
         , i.p_order;

COMMIT;
