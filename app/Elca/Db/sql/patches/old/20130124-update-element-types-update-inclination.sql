BEGIN;
SELECT public.register_patch('update-element_types-update-inclination', 'elca');
UPDATE elca.element_types SET pref_inclination = 270 WHERE pref_inclination = 90;
COMMIT;
