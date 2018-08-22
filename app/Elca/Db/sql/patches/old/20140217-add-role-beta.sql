BEGIN;
SELECT public.register_patch('add-role-beta', 'elca');

-- remove duplicate role root
DELETE FROM public.nested_nodes WHERE id = 686;

INSERT INTO public.roles (node_id, role_name, description)
    VALUES ( (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES' AND id=root_id),
            'ELCA_ROLES',
            '');
INSERT INTO public.nested_nodes (id, root_id, lft, rgt, level, ident)
    VALUES (DEFAULT, (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES'), 6, 7, 1, 'BETA');
INSERT INTO public.roles (node_id, role_name, description)
    VALUES ( (SELECT id FROM public.nested_nodes WHERE ident = 'BETA' AND root_id = (SELECT id FROM public.nested_nodes WHERE ident = 'ELCA_ROLES')),
            'Beta-Tester',
            'Erhält Zugang zur Beta-Testplattform');

COMMIT;
