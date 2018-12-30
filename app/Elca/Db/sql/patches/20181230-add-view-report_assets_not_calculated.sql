BEGIN;
SELECT public.register_patch('20181230-add-view-report_assets_not_calculated.sql', 'eLCA');

CREATE OR REPLACE VIEW elca_cache.report_assets_not_calculated_v AS
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
         , null AS cache_element_quantity
         , e.ref_unit AS cache_element_ref_unit
         , null AS element_mass
         , c.id AS element_component_id
         , c.process_conversion_id
         , c.is_layer AS component_is_layer
         , c.calc_lca AS component_calc_lca
         , c.is_extant AS component_is_extant
         , c.layer_width AS component_layer_width
         , c.layer_length AS component_layer_length
         , c.layer_size AS component_size
         , c.quantity AS component_quantity
         , c.life_time AS component_life_time
         , c.life_time_delay AS component_life_time_delay
         , c.life_time_info AS component_life_time_info
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
         , null AS cache_component_quantity
         , a.ref_unit AS cache_component_ref_unit
         , null AS cache_component_num_replacements
         , pc.min_life_time, pc.avg_life_time, pc.max_life_time
         , c.life_time NOT IN (coalesce(pc.min_life_time, 0), coalesce(pc.avg_life_time, 0), coalesce(pc.max_life_time, 0)) AS has_non_default_life_time
    FROM elca.elements e
             JOIN elca.element_types_v       t  ON e.element_type_node_id = t.node_id
             JOIN elca.element_types_v       tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
             JOIN elca.element_components    c  ON e.id = c.element_id
             JOIN elca.process_assignments_v a  ON c.process_config_id = a.process_config_id
             JOIN elca.process_configs       pc ON pc.id = a.process_config_id
    WHERE c.calc_lca = false;


COMMIT;