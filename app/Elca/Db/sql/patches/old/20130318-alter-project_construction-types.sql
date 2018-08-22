BEGIN;
SELECT public.register_patch('alter-project_constructions-types', 'elca');

ALTER TABLE elca.project_constructions ALTER COLUMN net_floor_space TYPE numeric;
ALTER TABLE elca.project_constructions ALTER COLUMN gross_floor_space TYPE numeric;
ALTER TABLE elca.project_constructions ALTER COLUMN floor_space TYPE numeric;
ALTER TABLE elca.project_constructions ALTER COLUMN property_size TYPE numeric;

COMMIT;
