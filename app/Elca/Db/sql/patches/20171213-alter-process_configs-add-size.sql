BEGIN;
SELECT
    public.register_patch('20171213-alter-process_configs-add-size.sql', 'eLCA');

ALTER TABLE elca.process_configs ADD "default_size" numeric;

DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
DROP VIEW IF EXISTS import_assistant.process_config_mapping_conversions_view;
DROP VIEW IF EXISTS elca.process_config_search_v;
DROP VIEW IF EXISTS elca.element_search_v;
DROP VIEW IF EXISTS elca.composite_element_extended_search_v;
DROP VIEW IF EXISTS elca.element_extended_search_v;
DROP VIEW IF EXISTS elca.process_config_process_dbs_view;

CREATE OR REPLACE VIEW elca.process_config_process_dbs_view AS
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
    WHERE lc.phase = 'prod'
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

CREATE OR REPLACE VIEW elca.element_extended_search_v AS
    SELECT
        e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.element_components c ON e.id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = false
    GROUP BY e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified;

CREATE OR REPLACE VIEW elca.composite_element_extended_search_v AS
    SELECT
        e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.composite_elements a ON e.id = a.composite_element_id
        LEFT JOIN elca.element_components c ON a.element_id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = true
    GROUP BY e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified;

CREATE OR REPLACE VIEW elca.element_search_v AS
    SELECT
        e.id
        , e.name
        , e.element_type_node_id
        , e.project_variant_id
        , e.access_group_id
        , e.is_reference
        , t.din_code || ' ' || t.name AS element_type_node_name
        , e.process_db_ids
    FROM elca.element_extended_search_v e
        JOIN elca.element_types t ON t.node_id = e.element_type_node_id;


CREATE VIEW elca.process_config_search_v AS
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
    FROM elca.process_config_process_dbs_view p
        JOIN elca.process_categories_v c ON c.node_id = p.process_category_node_id
        JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;

CREATE OR REPLACE VIEW import_assistant.process_config_mapping_conversions_view AS
    SELECT m.id
        , m.material_name
        , m.process_config_id
        , pc.name AS process_config_name
        , m.is_sibling
        , m.sibling_ratio
        , m.required_additional_layer
        , m.process_db_id
        , pc.epd_types
        , pc.process_db_ids
        , array_agg(DISTINCT c.in_unit) FILTER (WHERE c.id IS NOT NULL) AS units
    FROM import_assistant.process_config_mapping m
        JOIN elca.process_config_process_dbs_view pc ON pc.id = m.process_config_id
        JOIN elca.process_conversions c ON c.process_config_id = m.process_config_id
    GROUP BY m.id
        , m.material_name
        , m.process_config_id
        , pc.name
        , m.is_sibling
        , m.sibling_ratio
        , m.required_additional_layer
        , m.process_db_id
        , pc.epd_types
        , pc.process_db_ids
;

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