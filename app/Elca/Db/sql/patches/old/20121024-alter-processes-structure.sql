BEGIN;
SELECT public.register_patch('alter-processes-structure-2', 'elca');
ALTER TABLE elca.processes ADD "description"              text;
ALTER TABLE elca.process_configs ADD "is_reference"  boolean    NOT NULL DEFAULT true;
COMMIT;

