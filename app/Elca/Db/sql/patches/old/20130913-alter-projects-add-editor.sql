BEGIN;
SELECT public.register_patch('alter-projects-add-editor', 'elca');

ALTER TABLE elca.projects ADD "editor" varchar(250);

COMMIT;
