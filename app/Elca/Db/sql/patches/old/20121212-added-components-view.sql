BEGIN;
SELECT public.register_patch('added-components-view', 'elca');

CREATE VIEW elca.element_components_v AS
  SELECT c.*
       , e.name AS element_name
    FROM elca.element_components c
    JOIN elca.elements e ON e.id = c.element_id;

COMMIT;