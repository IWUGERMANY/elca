BEGIN;
SELECT public.register_patch('20170213-add-role-lcc.sql', 'elca');

INSERT INTO public.nested_nodes (id, root_id, lft, rgt, level, ident)
VALUES (DEFAULT, (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES'), 10, 11, 1, 'LCC');

UPDATE public.nested_nodes SET rgt = 12 WHERE id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES');

INSERT INTO public.roles (node_id, role_name, description)
VALUES ( (SELECT id FROM public.nested_nodes WHERE ident = 'LCC' AND root_id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES')),
         'LCC',
         'Erhält Zugang zum ausführlichen LCC Verfahren');

COMMIT;