BEGIN;
SELECT public.register_patch('alter-project-tables-add-variant-constraint', 'lcc');

ALTER TABLE lcc.project_costs ADD FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE;
ALTER TABLE lcc.project_totals ADD FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON DELETE CASCADE;

COMMIT;
