BEGIN;
SELECT public.register_patch('alter-process_configs-extend-life-times', 'elca');

DROP VIEW IF EXISTS elca.process_configs_extended_search_v;

ALTER TABLE elca.process_configs ALTER default_life_time DROP NOT NULL;
ALTER TABLE elca.process_configs RENAME default_life_time TO avg_life_time;
ALTER TABLE elca.process_configs ADD "min_life_time"          int;
ALTER TABLE elca.process_configs ADD "max_life_time"          int;
ALTER TABLE elca.process_configs ADD "life_time_info"          text;
ALTER TABLE elca.process_configs ADD "avg_life_time_info"  text;
ALTER TABLE elca.process_configs ADD "min_life_time_info"      text;
ALTER TABLE elca.process_configs ADD "max_life_time_info"      text;

UPDATE elca.process_configs
   SET min_life_time = avg_life_time
     , avg_life_time = null;

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
         , pc.created
         , pc.modified;

COMMIT;
