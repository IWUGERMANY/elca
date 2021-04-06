BEGIN;
SELECT public.register_patch('20210330-add-new-ifc-role.sql', 'eLCA');

INSERT INTO public.nested_nodes (id, root_id, lft, rgt, level, ident)
VALUES (DEFAULT, (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES'), 16, 17, 1, 'IFC_VIEWER');

UPDATE public.nested_nodes SET rgt = 18 WHERE id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES');

INSERT INTO public.roles (node_id, role_name, description)
VALUES ( (SELECT id FROM public.nested_nodes WHERE ident = 'IFC_VIEWER' AND root_id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES')),
           'IFC-Projekt',
           'IFC Projekt importieren und grafischer IFC-Viewer');

COMMIT;