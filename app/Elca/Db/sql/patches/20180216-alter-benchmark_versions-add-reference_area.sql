BEGIN;
SELECT
    public.register_patch('20180216-alter-benchmark_versions-add-reference_area.sql', 'eLCA');

DROP VIEW IF EXISTS elca.benchmark_versions_with_constr_classes;

ALTER TABLE elca.benchmark_versions ADD "reference_area"        varchar(200)    NOT NULL DEFAULT 'netFloorSpace';

CREATE VIEW elca.benchmark_versions_with_constr_classes AS
    SELECT v.id,
        v.benchmark_system_id,
        v.name,
        v.process_db_id,
        v.is_active,
        v.use_reference_model,
        v.reference_area,
        array_agg(c.constr_class_id) FILTER (WHERE c.id IS NOT NULL) as constr_class_ids
    FROM elca.benchmark_versions v
        LEFT JOIN elca.benchmark_version_constr_classes c ON v.id = c.benchmark_version_id
    GROUP BY v.id,
        v.benchmark_system_id,
        v.name,
        v.process_db_id,
        v.is_active,
        v.use_reference_model;


COMMIT;