BEGIN;
SELECT public.register_patch('20170522-add-new-testing-role.sql', 'eLCA');

INSERT INTO public.nested_nodes (id, root_id, lft, rgt, level, ident)
VALUES (DEFAULT, (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES'), 12, 13, 1, 'TESTING');

UPDATE public.nested_nodes SET rgt = 14 WHERE id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES');

INSERT INTO public.roles (node_id, role_name, description)
VALUES ( (SELECT id FROM public.nested_nodes WHERE ident = 'TESTING' AND root_id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES')),
         'Tests',
         'Erhält Zugang zu Funktionen, die noch getestet werden müssen');

COMMIT;