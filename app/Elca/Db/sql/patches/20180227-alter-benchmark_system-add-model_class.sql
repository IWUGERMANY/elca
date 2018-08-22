BEGIN;
SELECT
    public.register_patch('20180227-alter-benchmark_system-add-model_class.sql', 'eLCA');

DROP VIEW IF EXISTS elca.benchmark_versions_with_constr_classes;
ALTER TABLE elca.benchmark_versions DROP COLUMN "reference_area";
CREATE VIEW elca.benchmark_versions_with_constr_classes AS
    SELECT v.id,
        v.benchmark_system_id,
        v.name,
        v.process_db_id,
        v.is_active,
        v.use_reference_model,
        array_agg(c.constr_class_id) FILTER (WHERE c.id IS NOT NULL) as constr_class_ids
    FROM elca.benchmark_versions v
        LEFT JOIN elca.benchmark_version_constr_classes c ON v.id = c.benchmark_version_id
    GROUP BY v.id,
        v.benchmark_system_id,
        v.name,
        v.process_db_id,
        v.is_active,
        v.use_reference_model;

ALTER TABLE elca.benchmark_systems ADD "model_class"           varchar(250);
UPDATE elca.benchmark_systems SET model_class = 'Bnb\Model\Benchmark\BnbBenchmarkSystemModel';
ALTER TABLE elca.benchmark_systems ALTER "model_class" SET NOT NULL ;

COMMIT;