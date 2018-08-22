BEGIN;
SELECT public.register_patch('init-benchmark-systems', 'elca');

CREATE TABLE elca.benchmark_systems
(
    "id"                    serial          NOT NULL                -- benchmarkSystemId
  , "name"                  varchar(150)    NOT NULL                -- system name
  , "is_active"             boolean         NOT NULL DEFAULT false  -- active flag
  , "description"           text                                    -- description
  , PRIMARY KEY ("id")
);


CREATE TABLE elca.benchmark_versions
(
    "id"                    serial          NOT NULL                -- benchmarkVersionId
  , "benchmark_system_id"   int             NOT NULL                -- benchmarkSystemId
  , "name"                  varchar(150)    NOT NULL                -- system name
  , "process_db_id"         int                                     -- processDbId
  , "is_active"             boolean         NOT NULL DEFAULT false  -- active flag
  , PRIMARY KEY ("id")
  , FOREIGN KEY ("benchmark_system_id") REFERENCES elca.benchmark_systems ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);


CREATE TABLE elca.benchmark_thresholds
(
    "id"                    serial        NOT NULL                  -- benchmarkThresholdId
  , "benchmark_version_id"  int           NOT NULL                  -- benchmarkVersionId
  , "indicator_id"          int           NOT NULL                  -- indicatorId
  , "score"                 int           NOT NULL                  -- score value
  , "value"                 numeric       NOT NULL                  -- threshold value
  , PRIMARY KEY ("id")
  , UNIQUE ("benchmark_version_id", "indicator_id", "score")
  , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE OR REPLACE VIEW elca.benchmark_thresholds_v AS
  SELECT t.*
       , i.ident AS indicator_ident
    FROM elca.benchmark_thresholds t
    JOIN elca.indicators           i ON i.id = t.indicator_id;

ALTER TABLE elca.projects ADD "benchmark_version_id" int;
ALTER TABLE elca.projects ADD FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE elca.process_dbs ADD "is_en15804_compliant" BOOLEAN NOT NULL DEFAULT TRUE;

UPDATE elca.process_dbs
SET is_en15804_compliant = false
WHERE version IN ('2009', '2011');

COMMIT;