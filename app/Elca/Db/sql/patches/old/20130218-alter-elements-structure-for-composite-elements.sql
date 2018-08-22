BEGIN;
SELECT public.register_patch('alter-elements-structure-for-composite-element', 'elca');

ALTER TABLE elca.elements ADD COLUMN "is_composite"           boolean NOT NULL DEFAULT false;
ALTER TABLE elca.elements ADD COLUMN "composite_element_id"   int;
ALTER TABLE elca.elements ADD COLUMN "composite_position"     int;
ALTER TABLE elca.elements ADD FOREIGN KEY ("composite_element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE SET NULL;

DROP VIEW IF EXISTS elca.element_search_v;
CREATE VIEW elca.element_search_v AS
  SELECT e.id
       , e.name
       , e.element_type_node_id
       , e.composite_element_id
       , e.project_variant_id
       , e.access_group_id
       , e.is_reference
       , t.din_code ||' '|| t.name AS element_type_node_name
    FROM elca.elements e
    JOIN elca.element_types t  ON t.node_id = e.element_type_node_id;

CREATE TABLE elca.element_attributes
(
   "id"                      serial          NOT NULL            -- elementAttributeId
 , "element_id"              int             NOT NULL            -- elementId
 , "ident"                   varchar(150)    NOT NULL            -- attribute identifier
 , "caption"                 varchar(150)    NOT NULL            -- attribute caption
 , "numeric_value"           numeric                             -- numeric value
 , "text_value"              text                                -- text value
 , PRIMARY KEY ("id")
 , UNIQUE ("element_id", "ident")
 , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

ALTER TABLE elca_cache.elements ADD composite_item_id integer;
ALTER TABLE elca_cache.elements ADD  FOREIGN KEY ("composite_item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE SET NULL;

DROP VIEW IF EXISTS elca_cache.report_effects_v;
DROP VIEW IF EXISTS elca_cache.element_type_mass_v;
DROP VIEW IF EXISTS elca_cache.elements_v;
CREATE OR REPLACE VIEW elca_cache.elements_v AS
  SELECT i.*
       , e.*
   FROM elca_cache.elements e
   JOIN elca_cache.items    i ON i.id = e.item_id;

CREATE OR REPLACE VIEW elca_cache.element_type_mass_v AS
     SELECT t.item_id AS element_type_item_id
          , t.parent_id AS element_type_parent_id
          , sum(coalesce(e.mass, 0)) AS element_type_mass
      FROM elca_cache.element_types_v t
 LEFT JOIN elca_cache.elements_v e ON t.item_id = e.parent_id
  GROUP BY t.item_id
         , t.parent_id;

CREATE VIEW elca_cache.report_effects_v AS
    SELECT e.id AS element_id
         , e.project_variant_id
         , e.name AS element_name
         , e.quantity AS element_quantity
         , e.ref_unit AS element_ref_unit
         , e.element_type_node_id
         , t.din_code AS element_type_din_code
         , t.name AS element_type_name
         , t.is_constructional AS element_type_is_constructional
         , tt.name AS element_type_parent_name
         , tt.din_code AS element_type_parent_din_code
         , l.phase AS life_cycle_phase
         , ci.indicator_id AS indicator_id
         , i.name AS indicator_name
         , i.unit AS indicator_unit
         , i.p_order AS indicator_p_order
         , sum(ci.value) AS indicator_value
      FROM elca.elements e
      JOIN elca.element_types_v   t ON e.element_type_node_id = t.node_id
      JOIN elca.element_types_v  tt ON t.lft BETWEEN tt.lft AND tt.rgt AND tt.level = t.level - 1
      JOIN elca_cache.elements_v ce ON e.id = ce.element_id
      JOIN elca_cache.indicators ci ON ce.item_id = ci.item_id
      JOIN elca.life_cycles       l ON l.ident = ci.life_cycle_ident
      JOIN elca.indicators        i ON i.id = ci.indicator_id
     WHERE l.phase IN ('total', 'prod', 'maint', 'eol')
  GROUP BY e.id
         , e.project_variant_id
         , e.name
         , e.quantity
         , e.ref_unit
         , e.element_type_node_id
         , t.din_code
         , t.name
         , t.is_constructional
         , tt.name
         , tt.din_code
         , l.phase
         , ci.indicator_id
         , i.name
         , i.unit
         , i.p_order;

CREATE OR REPLACE VIEW elca_cache.composite_element_mass_v AS
     SELECT composite_item_id
          , sum(coalesce(mass, 0)) AS element_mass
      FROM elca_cache.elements_v
     WHERE composite_item_id IS NOT NULL
   GROUP BY composite_item_id;


COMMIT;
