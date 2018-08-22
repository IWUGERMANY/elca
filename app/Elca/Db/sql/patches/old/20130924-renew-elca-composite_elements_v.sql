BEGIN;
SELECT public.register_patch('renew-elca-composite_elements_v', 'elca');

DROP VIEW IF EXISTS elca.composite_elements_v;
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
       , e.created
       , e.modified
    FROM elca.composite_elements c
    JOIN elca.elements e ON e.id = c.element_id;

COMMIT;
