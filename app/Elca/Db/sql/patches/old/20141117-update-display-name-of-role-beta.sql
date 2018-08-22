BEGIN;
SELECT public.register_patch('update-display-name-of-role-beta', 'elca');

UPDATE public.roles
   SET role_name = 'Forscher'
  WHERE role_name = 'Beta-Tester';

COMMIT;