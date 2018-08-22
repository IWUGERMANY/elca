BEGIN;
SELECT public.register_patch('remove-NOT_FOUND-ref_unit', 'elca');

UPDATE elca.processes
   SET ref_unit = 'kg'
 WHERE ref_unit = 'NOT_FOUND'
   AND version = '2009';

DELETE FROM elca.process_conversions WHERE in_unit = 'NOT_FOUND' OR out_unit = 'NOT_FOUND';

COMMIT;




