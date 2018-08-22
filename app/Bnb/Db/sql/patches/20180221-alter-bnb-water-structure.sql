BEGIN;
SELECT
    public.register_patch('20180221-alter-bnb-water-structure.sql', 'eLCA');

ALTER TABLE bnb.water ADD "niederschlag_genutzt_ohne_wandlung" numeric;
ALTER TABLE bnb.water ADD "niederschlag_kanalisation" numeric;

COMMIT;