BEGIN;
SELECT public.register_patch('add-uuid-mappings-view', 'elca');

DROP VIEW IF EXISTS elca.uuid_mappings_v;
CREATE VIEW elca.uuid_mappings_v AS
  SELECT id,
         new_uuid,
         orig_uuid
  from elca.uuid_mappings
UNION
SELECT id,
       orig_uuid,
       new_uuid
from elca.uuid_mappings;

COMMIT;