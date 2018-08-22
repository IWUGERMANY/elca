BEGIN;
SELECT public.register_patch('alter-process-configs-add-f_hs_hi', 'elca');

ALTER TABLE elca.process_configs ADD "f_hs_hi"                numeric;

COMMIT;