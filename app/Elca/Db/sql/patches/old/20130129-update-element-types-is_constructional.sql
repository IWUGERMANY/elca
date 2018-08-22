BEGIN;
SELECT public.register_patch('update-element_types-is_constructional', 'elca');

UPDATE elca.element_types
   SET is_constructional = false
 WHERE is_constructional IS NULL;

COMMIT;
