BEGIN;
SELECT public.register_patch('add-cache-report-top-assets-view', 'elca');

DROP VIEW IF EXISTS elca_cache.report_top_assets_v;
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

COMMIT;
