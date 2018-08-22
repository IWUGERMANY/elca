BEGIN;
SELECT public.register_patch('alter_project_phases', 'elca');

ALTER TABLE elca.project_phases ADD "step" int NOT NULL DEFAULT 1;

update elca.project_phases set step = id where constr_measure = 1;

COMMIT;
