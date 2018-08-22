BEGIN;
SELECT public.register_patch('remove-density-of-9926b545-bcb6-4c5c-a038-14b79530c44e', 'elca');

-- remove wrong density from process config which is assigned to process 9926b545-bcb6-4c5c-a038-14b79530c44e
UPDATE elca.process_configs
   SET density = null
 WHERE id = (SELECT DISTINCT process_config_id
               FROM elca.process_assignments_v
              WHERE uuid = '9926b545-bcb6-4c5c-a038-14b79530c44e');

DELETE FROM elca.process_conversions
      WHERE ident = 'DENSITY'
        AND process_config_id = (SELECT DISTINCT process_config_id
               FROM elca.process_assignments_v
              WHERE uuid = '9926b545-bcb6-4c5c-a038-14b79530c44e');

COMMIT;
