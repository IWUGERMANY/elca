BEGIN;
SELECT public.register_patch('add-composite-elements', 'elca');

DROP VIEW IF EXISTS elca.element_search_v;

ALTER TABLE elca.elements DROP COLUMN composite_element_id;
ALTER TABLE elca.elements DROP COLUMN composite_position;

CREATE TABLE elca.composite_elements
(
   "composite_element_id"           int          NOT NULL                -- compositeElementId
 , "element_id"                     int          NOT NULL                -- element
 , "position"                       int          NOT NULL                -- element position within composite
 , PRIMARY KEY ("composite_element_id", "element_id")
 , FOREIGN KEY ("composite_element_id") REFERENCES elca.elements ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("element_id") REFERENCES elca.elements ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE VIEW elca.composite_assigned_elements_v AS
  SELECT e.*
       , c.composite_element_id
       , c.position AS composite_position
    FROM elca.elements e
    JOIN elca.composite_elements c ON e.id = c.element_id;

CREATE VIEW elca.composite_elements_v AS
  SELECT e.*
       , c.element_id
       , c.position AS composite_position
    FROM elca.elements e
    JOIN elca.composite_elements c ON e.id = c.composite_element_id;

CREATE VIEW elca.element_search_v AS
  SELECT e.id
       , e.name
       , e.element_type_node_id
       , e.project_variant_id
       , e.access_group_id
       , e.is_reference
       , t.din_code ||' '|| t.name AS element_type_node_name
    FROM elca.elements e
    JOIN elca.element_types t  ON t.node_id = e.element_type_node_id;

COMMIT;