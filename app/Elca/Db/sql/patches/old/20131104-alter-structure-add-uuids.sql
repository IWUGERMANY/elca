BEGIN;
SELECT public.register_patch('alter-structure-add-uuids', 'elca');

--------------------------------------------------------------------------------
-- process dbs
ALTER TABLE elca.process_dbs ADD "uuid"   uuid NOT NULL DEFAULT uuid_generate_v4();
ALTER TABLE elca.process_dbs ADD  UNIQUE ("uuid");
UPDATE elca.process_dbs SET uuid = uuid_generate_v5(uuid_ns_dns(), name);

--------------------------------------------------------------------------------
-- process configs

ALTER TABLE elca.process_configs ADD "uuid"   uuid NOT NULL DEFAULT uuid_generate_v4();
ALTER TABLE elca.process_configs ADD  UNIQUE ("uuid");
UPDATE elca.process_configs p
   SET uuid = uuid_generate_v5(uuid_ns_dns(), c.ref_num||' '||p.name)
  FROM elca.process_categories c
 WHERE c.node_id = p.process_category_node_id;

--------------------------------------------------------------------------------
-- elements

ALTER TABLE elca.elements ADD "uuid"   uuid NOT NULL DEFAULT uuid_generate_v4();
ALTER TABLE elca.elements ADD  UNIQUE ("uuid");

DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
DROP VIEW IF EXISTS elca.composite_elements_v;
DROP VIEW IF EXISTS elca.element_extended_search_v;
DROP VIEW IF EXISTS elca.composite_element_extended_search_v;

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

CREATE VIEW elca.composite_elements_v AS
  SELECT c.composite_element_id
       , c.position
       , c.element_id
       , e.id
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
    FROM elca.composite_elements c
    JOIN elca.elements e ON e.id = c.element_id;

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
       , e.uuid
       , e.created
       , e.modified
       , to_tsvector('german', e.id ||' '|| e.name ||' '|| coalesce(e.description ||' ', '') ||
         array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
    FROM elca.elements e
    LEFT JOIN elca.element_components c ON e.id = c.element_id
    LEFT JOIN elca.process_configs   pc ON pc.id = c.process_config_id
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
       , e.uuid
       , e.created
       , e.modified
       , to_tsvector('german', e.id ||' '|| e.name ||' '|| coalesce(e.description ||' ', '') ||
         array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
    FROM elca.elements e
    LEFT JOIN elca.composite_elements a ON e.id = a.composite_element_id
    LEFT JOIN elca.element_components c ON a.element_id = c.element_id
    LEFT JOIN elca.process_configs   pc ON pc.id = c.process_config_id
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


COMMIT;
