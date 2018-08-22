BEGIN;
SELECT public.register_patch('fix-composite-elements-model', 'elca');

ALTER TABLE elca.composite_elements DROP CONSTRAINT "composite_elements_pkey";
ALTER TABLE elca.composite_elements ADD PRIMARY KEY ("composite_element_id", "position");

CREATE INDEX IX_elca_composite_elements_composite_element_id_element_id ON elca.composite_elements ("composite_element_id", "element_id");

DROP VIEW IF EXISTS elca.composite_assigned_elements_v;
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
    JOIN elca.elements e ON e.id = c.composite_element_id;

COMMIT;
