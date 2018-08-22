BEGIN;
SELECT public.register_patch('alter-process-indicators-structure', 'elca');
ALTER TABLE elca.process_indicators ADD UNIQUE ("process_id", "indicator_id");
COMMIT;
