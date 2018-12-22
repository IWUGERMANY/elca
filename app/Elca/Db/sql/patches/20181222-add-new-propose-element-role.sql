BEGIN;
SELECT public.register_patch('20181222-add-new-propose-element-role.sql', 'eLCA');

INSERT INTO public.nested_nodes (id, root_id, lft, rgt, level, ident)
VALUES (DEFAULT, (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES'), 14, 15, 1, 'PROPOSE_ELEMENTS');

UPDATE public.nested_nodes SET rgt = 16 WHERE id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES');

INSERT INTO public.roles (node_id, role_name, description)
VALUES ( (SELECT id FROM public.nested_nodes WHERE ident = 'PROPOSE_ELEMENTS' AND root_id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES')),
           'Bauteile vorschlagen',
           'Erhält die Möglichkeit Bauteile als Vorlagen vorzuschlagen');

COMMIT;