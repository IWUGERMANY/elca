BEGIN;
SELECT public.register_patch('alter-projects-add-password', 'elca');

ALTER TABLE elca.projects ADD "password"               varchar(60);
COMMIT;