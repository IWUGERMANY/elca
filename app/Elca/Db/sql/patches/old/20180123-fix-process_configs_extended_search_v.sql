BEGIN;
SELECT
    public.register_patch('20180123-fix-process_configs_extended_search_v.sql', 'eLCA');

DROP VIEW elca.process_configs_extended_search_v;
CREATE VIEW elca.process_configs_extended_search_v AS
    SELECT
        pc.id
        , pc.process_category_node_id
        , pc.name
        , pc.description
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
        , pc.default_size
        , pc.uuid
        , pc.svg_pattern_id
        , pc.is_stale
        , pc.created
        , pc.modified
        , to_tsvector('german', pc.name || ' ' ||
                                coalesce(array_to_string(array_agg(DISTINCT p.uuid :: text), ' '), '') || ' ' ||
                                coalesce(array_to_string(array_agg(DISTINCT p.name_orig), ' '), '')) AS search_vector
    FROM elca.process_configs pc
        LEFT JOIN elca.process_assignments_v p ON pc.id = p.process_config_id
    GROUP BY pc.id
        , pc.process_category_node_id
        , pc.name
        , pc.description
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
        , pc.default_size
        , pc.uuid
        , pc.is_stale
        , pc.created
        , pc.modified;

COMMIT;