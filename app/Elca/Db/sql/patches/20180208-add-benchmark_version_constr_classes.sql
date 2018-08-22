BEGIN;
SELECT
    public.register_patch('20180208-add-benchmark_version_constr_classes.sql', 'eLCA');

CREATE TABLE elca.benchmark_version_constr_classes
(
      "id"                     serial          NOT NULL                -- benchmarkLifeCycleUsageSpecificationId
    , "benchmark_version_id"   int             NOT NULL                -- benchmarkVersionId
    , "constr_class_id"        int             NOT NULL
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "constr_class_id")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
    , FOREIGN KEY ("constr_class_id") REFERENCES elca.constr_classes("id") ON DELETE CASCADE
);

INSERT INTO elca.benchmark_version_constr_classes(benchmark_version_id, constr_class_id)
    SELECT v.id
         , cc.id
        FROM elca.benchmark_versions v
            JOIN elca.benchmark_systems s ON s.id = v.benchmark_system_id
            CROSS JOIN elca.constr_classes cc
            WHERE cc.ref_num::text ilike '612_'
AND (s.name, v.name) = ('NaWoh', '3.1 Beta');


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


COMMIT;