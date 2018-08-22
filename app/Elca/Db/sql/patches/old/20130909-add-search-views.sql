BEGIN;
SELECT public.register_patch('add-extended-search-views', 'elca');

DROP VIEW IF EXISTS elca.element_extended_search_v;
CREATE VIEW elca.element_extended_search_v AS
  SELECT e.id
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
       , e.created
       , e.modified
       , to_tsvector('german', e.id ||' '|| e.name ||' '|| coalesce(e.description ||' ', '') ||
         array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
    FROM elca.elements e
    JOIN elca.element_components c ON e.id = c.element_id
    JOIN elca.process_configs   pc ON pc.id = c.process_config_id
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
       , e.created
       , e.modified;

DROP VIEW IF EXISTS elca.composite_element_extended_search_v;
CREATE VIEW elca.composite_element_extended_search_v AS
  SELECT e.id
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
       , e.created
       , e.modified
       , to_tsvector('german', e.id ||' '|| e.name ||' '|| coalesce(e.description ||' ', '') ||
         array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
    FROM elca.elements e
    JOIN elca.composite_elements a ON e.id = a.composite_element_id
    JOIN elca.element_components c ON a.element_id = c.element_id
    JOIN elca.process_configs   pc ON pc.id = c.process_config_id
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
       , e.created
       , e.modified;


DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
CREATE VIEW elca.process_configs_extended_search_v AS
    SELECT pc.id
         , pc.process_category_node_id
         , pc.name
         , pc.default_life_time
         , pc.density
         , pc.thermal_conductivity
         , pc.thermal_resistance
         , pc.is_reference
         , pc.f_hs_hi
         , pc.created
         , pc.modified
         , to_tsvector('german', pc.name ||' '||
           array_to_string(array_agg(DISTINCT p.uuid::text), ' ') ||' '||
           array_to_string(array_agg(DISTINCT p.name_orig), ' ')) AS search_vector
      FROM elca.process_configs pc
      JOIN elca.process_assignments_v p ON pc.id = p.process_config_id
  GROUP BY pc.id
         , pc.process_category_node_id
         , pc.name
         , pc.default_life_time
         , pc.density
         , pc.thermal_conductivity
         , pc.thermal_resistance
         , pc.is_reference
         , pc.f_hs_hi
         , pc.created
         , pc.modified;

COMMIT;
