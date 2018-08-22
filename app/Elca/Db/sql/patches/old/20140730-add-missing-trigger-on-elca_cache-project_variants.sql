BEGIN;
SELECT public.register_patch('add-missing-trigger-on-elca_cache-project_variants.sql', 'elca');

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.project_variants
FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

COMMIT;