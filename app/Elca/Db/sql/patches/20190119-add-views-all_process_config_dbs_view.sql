BEGIN;
SELECT public.register_patch('20190119-add-views-all_process_config_dbs_view.sql', 'eLCA');

CREATE OR REPLACE VIEW elca.process_config_search_all_v AS
    SELECT
        p.id
         , p.name
         , p.description
         , p.process_category_node_id
         , p.is_reference
         , p.process_db_ids
         , p.epd_types
         , c.ref_num || ' ' || c.name   AS process_category_node_name
         , c2.ref_num || ' ' || c2.name AS process_category_parent_node_name
    FROM elca.all_process_config_process_dbs_view p
             JOIN elca.process_categories_v c ON c.node_id = p.process_category_node_id
             JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;

CREATE OR REPLACE VIEW elca.all_process_config_process_dbs_view AS
    SELECT pc.id
         , pc.name
         , pc.process_category_node_id
         , pc.description
         , pc.avg_life_time
         , pc.min_life_time
         , pc.max_life_time
         , pc.life_time_info
         , pc.avg_life_time_info
         , pc.min_life_time_info
         , pc.max_life_time_info
         , pc.density
         , pc.thermal_conductivity
         , pc.thermal_resistance
         , pc.is_reference
         , pc.f_hs_hi
         , pc.default_size
         , pc.uuid
         , pc.svg_pattern_id
         , pc.is_stale
         , pc.created
         , pc.modified
         , array_agg(DISTINCT p.process_db_id) AS process_db_ids
         , array_agg(DISTINCT p.epd_type) FILTER (WHERE epd_type IS NOT NULL) AS epd_types
    FROM elca.process_configs pc
             JOIN elca.process_life_cycle_assignments a ON pc.id = a.process_config_id
             JOIN elca.processes p ON p.id = a.process_id
             JOIN elca.life_cycles lc ON lc.ident = p.life_cycle_ident
    GROUP BY pc.id
           , pc.name
           , pc.process_category_node_id
           , pc.description
           , pc.avg_life_time
           , pc.min_life_time
           , pc.max_life_time
           , pc.life_time_info
           , pc.avg_life_time_info
           , pc.min_life_time_info
           , pc.max_life_time_info
           , pc.density
           , pc.thermal_conductivity
           , pc.thermal_resistance
           , pc.is_reference
           , pc.f_hs_hi
           , pc.default_size
           , pc.uuid
           , pc.svg_pattern_id
           , pc.is_stale
           , pc.created
           , pc.modified
;

COMMIT;