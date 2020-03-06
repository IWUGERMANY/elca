BEGIN;
SELECT public.register_patch('20191219-add-final-energy-demands-kwk-constraint.sql', 'elca');

ALTER TABLE elca.project_final_energy_demands
    ADD FOREIGN KEY ("kwk_id") REFERENCES elca.project_kwks("id") ON UPDATE CASCADE ON DELETE CASCADE;

COMMIT;