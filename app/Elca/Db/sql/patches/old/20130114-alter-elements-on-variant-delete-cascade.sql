BEGIN;
SELECT public.register_patch('alter-elements-on-variant-delete-cascade', 'elca');

ALTER TABLE elca.elements DROP CONSTRAINT "elements_project_variant_id_fkey";
ALTER TABLE elca.elements ADD  FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE;
COMMIT;
