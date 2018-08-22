BEGIN;
SELECT public.register_patch('alter-process_configs-add-textfield', 'elca');

DROP VIEW IF EXISTS elca.process_config_search_v;
DROP VIEW IF EXISTS elca.process_configs_extended_search_v;

ALTER TABLE elca.process_configs ADD COLUMN "description" text;

CREATE VIEW elca.process_config_search_v AS
    SELECT p.id
        , p.name
        , p.description
        , p.process_category_node_id
        , p.is_reference
        , c.ref_num ||' '|| c.name AS process_category_node_name
        , c2.ref_num ||' '|| c2.name AS process_category_parent_node_name
    FROM elca.process_configs p
        JOIN elca.process_categories_v c  ON c.node_id = p.process_category_node_id
        JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;


CREATE VIEW elca.process_configs_extended_search_v AS
    SELECT pc.id
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
        , pc.uuid
        , pc.svg_pattern_id
        , pc.created
        , pc.modified
        , to_tsvector('german', pc.name ||' '|| pc.description ||' '||
                                coalesce(array_to_string(array_agg(DISTINCT p.uuid::text), ' '), '') ||' '||
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
        , pc.uuid
        , pc.created
        , pc.modified;

COMMIT;