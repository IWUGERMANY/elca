BEGIN;
SELECT
    public.register_patch('20180118-optimize-process-db-delete-cascade.sql', 'eLCA');

CREATE INDEX IX_elca_process_config_sanities_process_db_id ON elca.process_config_sanities (process_db_id);
CREATE INDEX IX_elca_benchmark_versions_process_db_id ON elca.benchmark_versions (process_db_id);
CREATE INDEX IX_soda4lca_imports_process_db_id ON soda4lca.imports (process_db_id);
CREATE INDEX IX_elca_cache_indicators_process_id ON elca_cache.indicators (process_id);

COMMIT;