BEGIN;
SELECT public.register_patch('update-stk-units', 'elca');

UPDATE elca.process_conversions
   SET in_unit = 'Stück'
 WHERE in_unit = 'Stk';

UPDATE elca.process_conversions
SET out_unit = 'Stück'
WHERE out_unit = 'Stk';

UPDATE elca.elements
SET ref_unit = 'Stück'
WHERE ref_unit = 'Stk';

UPDATE elca_cache.elements
SET ref_unit = 'Stück'
WHERE ref_unit = 'Stk';

UPDATE elca_cache.element_components
SET ref_unit = 'Stück'
WHERE ref_unit = 'Stk';

COMMIT;