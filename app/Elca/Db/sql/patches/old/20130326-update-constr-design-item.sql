BEGIN;
SELECT public.register_patch('update-constr-design-item', 'elca');

UPDATE elca.constr_designs
   SET name = 'Mauerwerksbau'
 WHERE name = 'Massivbau';

COMMIT;
