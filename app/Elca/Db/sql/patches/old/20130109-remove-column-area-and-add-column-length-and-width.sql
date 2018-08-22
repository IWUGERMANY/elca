BEGIN;
SELECT public.register_patch('remove-column-area-and-add-column-length-and-width', 'elca');
ALTER TABLE elca.elements DROP COLUMN "area";
ALTER TABLE elca.element_components ADD "layer_length"        numeric         DEFAULT 1;
ALTER TABLE elca.element_components ADD "layer_width"         numeric         DEFAULT 1;

DROP VIEW IF EXISTS elca.element_components_v;
CREATE VIEW elca.element_components_v AS
  SELECT c.*
       , e.name AS element_name
    FROM elca.element_components c
    JOIN elca.elements e ON e.id = c.element_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.element_layers_v;
CREATE VIEW elca.element_layers_v AS
  SELECT *
    FROM elca.element_components
   WHERE is_layer = true;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS elca.element_single_components_v;
CREATE VIEW elca.element_single_components_v AS
  SELECT *
    FROM elca.element_components
   WHERE is_layer = false;


COMMIT;
