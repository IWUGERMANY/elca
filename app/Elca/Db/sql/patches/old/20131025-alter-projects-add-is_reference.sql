BEGIN;
SELECT public.register_patch('alter-projects-add-is_reference', 'elca');

ALTER TABLE elca.projects ADD "is_reference" boolean NOT NULL DEFAULT false;

COMMIT;
