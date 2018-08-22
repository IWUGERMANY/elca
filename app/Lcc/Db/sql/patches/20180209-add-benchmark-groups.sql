BEGIN;
SELECT
    public.register_patch('20180209-add-benchmark-groups.sql', 'eLCA');

CREATE TABLE lcc.benchmark_thresholds
(
    "id"                   serial   NOT NULL
    ,
    "benchmark_version_id" int      NOT NULL
    ,
    "category"             smallint NOT NULL
    ,
    "score"                int      NOT NULL
    ,
    "value"                numeric  NOT NULL
    ,
    PRIMARY KEY ("id")
    ,
    FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
);

CREATE TABLE lcc.benchmark_groups
(
    "id"                   serial       NOT NULL
    ,
    "benchmark_version_id" int          NOT NULL
    ,
    "category"             smallint     NOT NULL
    ,
    "name"                 varchar(200) NOT NULL
    ,
    PRIMARY KEY ("id")
    ,
    UNIQUE ("benchmark_version_id", "category", "name")
    ,
    FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
);

CREATE TABLE lcc.benchmark_group_thresholds
(
    "id"       serial NOT NULL
    ,
    "group_id" int    NOT NULL
    ,
    "score"    int    NOT NULL
    ,
    "caption"  text   NOT NULL
    ,
    PRIMARY KEY ("id")
    ,
    UNIQUE ("group_id", "score")
    ,
    FOREIGN KEY ("group_id") REFERENCES lcc.benchmark_groups ("id") ON DELETE CASCADE
);

INSERT INTO lcc.benchmark_groups (benchmark_version_id, category, name)
    SELECT v.id
         , c
         , '2.1.1'
        FROM elca.benchmark_versions v
        CROSS JOIN generate_series(1, 2) AS c;

INSERT INTO lcc.benchmark_group_thresholds (group_id, score, caption)
    SELECT g.id
         , 10
         , ''
        FROM lcc.benchmark_groups g;



INSERT INTO lcc.benchmark_thresholds (benchmark_version_id, category, score, value)
    SELECT
        v.id,
        x.category,
        x.score,
        x.value
    FROM elca.benchmark_versions v
        JOIN elca.process_dbs p ON p.id = v.process_db_id
        CROSS JOIN (
                       VALUES
                           (1, 100, 2000),
                           (1, 90, 2180),
                           (1, 80, 2360),
                           (1, 70, 2540),
                           (1, 60, 2720),
                           (1, 50, 2900),
                           (1, 40, 3080),
                           (1, 30, 3260),
                           (1, 20, 3440),
                           (1, 10, 3620),
                           (2, 100, 2400),
                           (2, 90, 2660),
                           (2, 80, 2920),
                           (2, 70, 3180),
                           (2, 60, 3440),
                           (2, 50, 3700),
                           (2, 40, 3960),
                           (2, 30, 4220),
                           (2, 20, 4480),
                           (2, 10, 4740)
                   ) AS x (category, score, value)
    WHERE p.is_en15804_compliant = false;

INSERT INTO lcc.benchmark_thresholds (benchmark_version_id, category, score, value)
    SELECT
        v.id,
        x.category,
        x.score,
        x.value
    FROM elca.benchmark_versions v
        JOIN elca.process_dbs p ON p.id = v.process_db_id
        CROSS JOIN (
                       VALUES
                           (1, 100, 3300),
                           (1, 50, 4800),
                           (1, 10, 6400),
                           (2, 100, 3300),
                           (2, 50, 4800),
                           (2, 10, 6400)
                   ) AS x (category, score, value)
    WHERE p.is_en15804_compliant = true;



COMMIT;