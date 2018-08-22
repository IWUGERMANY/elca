BEGIN;
SELECT public.register_patch('add-new-constrDesign-erdberuehrt', 'elca');

INSERT INTO elca.constr_designs(id, name, ident) VALUES (DEFAULT, 'Erdberührt', 5);
COMMIT;
