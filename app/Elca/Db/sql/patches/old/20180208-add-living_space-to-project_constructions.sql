BEGIN;
SELECT
    public.register_patch('20180208-add-living_space-to-project_constructions.sql', 'eLCA');

ALTER TABLE elca.project_constructions ADD living_space numeric;

COMMIT;