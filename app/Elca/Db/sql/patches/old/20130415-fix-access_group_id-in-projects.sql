BEGIN;
SELECT public.register_patch('fix-access_group_id-in-projects', 'elca');

UPDATE elca.elements e
   SET access_group_id = (SELECT access_group_id
                            FROM elca.projects p
                            JOIN elca.project_variants v ON p.id = v.project_id
                           WHERE v.id = e.project_variant_id)
 WHERE project_variant_id IS NOT NULL;

COMMIT;