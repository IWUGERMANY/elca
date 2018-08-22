BEGIN;
SELECT public.register_patch('alter-processes-structure', 'elca');
ALTER TABLE elca.processes ADD "name_orig"             varchar(250)    NOT NULL;
COMMIT;
