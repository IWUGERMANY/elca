BEGIN;
SELECT public.register_patch('alter-project_constructions-add-is_extand_building', 'elca');

ALTER TABLE elca.project_constructions ADD "is_extant_building"     boolean         NOT NULL DEFAULT false;

COMMIT;