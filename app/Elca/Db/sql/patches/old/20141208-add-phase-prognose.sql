BEGIN;
SELECT public.register_patch('add-phase-prognose', 'elca');
insert into elca.project_phases values (DEFAULT, 'Prognose', 'PROJECTION', 1, 0);
insert into elca.project_phases values (DEFAULT, 'Prognose', 'PROJECTION', 2, 0);
COMMIT;