BEGIN;
SELECT public.register_patch('alter-project_constructions-structure', 'elca');

ALTER TABLE elca.project_constructions ALTER gross_floor_space DROP NOT NULL;
ALTER TABLE elca.project_constructions ALTER floor_space DROP NOT NULL;
ALTER TABLE elca.project_constructions ALTER net_floor_space DROP NOT NULL;

COMMIT;
