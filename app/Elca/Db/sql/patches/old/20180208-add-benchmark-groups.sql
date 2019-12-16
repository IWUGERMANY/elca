BEGIN;
SELECT
    public.register_patch('20180208-add-benchmark-groups.sql', 'eLCA');

CREATE TABLE elca.benchmark_groups
(
      "id"        serial  NOT NULL
    , "benchmark_version_id" int NOT NULL
    , "name"      varchar(200)  NOT NULL
    , PRIMARY KEY ("id")
    , UNIQUE ("benchmark_version_id", "name")
    , FOREIGN KEY ("benchmark_version_id") REFERENCES elca.benchmark_versions ("id") ON DELETE CASCADE
);

CREATE TABLE elca.benchmark_group_indicators
(
      "group_id"  int  NOT NULL
    , "indicator_id"      int NOT NULL
    , PRIMARY KEY ("group_id", "indicator_id")
    , FOREIGN KEY ("group_id") REFERENCES elca.benchmark_groups ("id") ON DELETE CASCADE
    , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators("id") ON DELETE CASCADE
);

CREATE TABLE elca.benchmark_group_thresholds
(
      "id"        serial NOT NULL
    , "group_id"  int    NOT NULL
    , "score"     int     NOT NULL
    , "caption"   text    NOT NULL
    , PRIMARY KEY ("id")
    , FOREIGN KEY ("group_id") REFERENCES elca.benchmark_groups ("id") ON DELETE CASCADE
);

INSERT INTO elca.benchmark_groups (benchmark_version_id, name)
    SELECT v.id
         , x.name
        FROM elca.benchmark_versions v
            JOIN elca.process_dbs db ON v.process_db_id = db.id AND NOT db.is_en15804_compliant
            CROSS JOIN (VALUES
                ('1.1.1'),
                ('1.1.2'),
                ('1.1.3'),
                ('1.1.4'),
                ('1.1.5'),
                ('1.2.1'),
                ('1.2.2')
        ) AS x (name);

INSERT INTO elca.benchmark_groups (benchmark_version_id, name)
    SELECT v.id
        , x.name
    FROM elca.benchmark_versions v
        JOIN elca.process_dbs db ON v.process_db_id = db.id AND db.is_en15804_compliant
        CROSS JOIN (VALUES
            ('1.1.1'),
            ('1.1.2'),
            ('1.1.3'),
            ('1.1.4'),
            ('1.1.5'),
            ('1.2.1')
                   ) AS x (name);


INSERT INTO elca.benchmark_group_indicators (group_id, indicator_id)
    SELECT g.id
         , i.id
        FROM elca.benchmark_groups g
            JOIN elca.benchmark_versions v ON v.id = g.benchmark_version_id
            JOIN elca.process_dbs db ON v.process_db_id = db.id
            JOIN elca.indicators i ON i.is_excluded = false AND
                                      (
                                          (i.ident = 'gwp' AND g.name = '1.1.1') OR
                                          (i.ident = 'odp' AND g.name = '1.1.2') OR
                                          (i.ident = 'pocp' AND g.name = '1.1.3') OR
                                          (i.ident = 'ap' AND g.name = '1.1.4') OR
                                          (i.ident = 'ep' AND g.name = '1.1.5') OR
                                          (i.ident = 'pet' AND g.name = '1.2.1' AND db.is_en15804_compliant) OR
                                          (i.ident = 'penrt' AND g.name = '1.2.1' AND db.is_en15804_compliant) OR
                                          (i.ident = 'pert' AND g.name = '1.2.1' AND db.is_en15804_compliant) OR
                                          (i.ident = 'peNEm' AND g.name = '1.2.1' AND NOT db.is_en15804_compliant) OR
                                          (i.ident = 'peEm' AND g.name = '1.2.2' AND NOT db.is_en15804_compliant) OR
                                          (i.ident = 'pet' AND g.name = '1.2.2' AND NOT db.is_en15804_compliant)
                                      );

INSERT INTO elca.benchmark_group_thresholds (group_id, score, caption)
    SELECT g.id
        ,  CASE WHEN g.name ILIKE '1.1._' THEN 10
                WHEN db.is_en15804_compliant AND g.name = '1.2.1' THEN 2
                WHEN NOT db.is_en15804_compliant AND g.name = '1.2.1' THEN 5
                WHEN NOT db.is_en15804_compliant AND g.name = '1.2.2' THEN 5
           END
        , ''
    FROM elca.benchmark_groups g
        JOIN elca.benchmark_versions v ON v.id = g.benchmark_version_id
        JOIN elca.process_dbs db ON v.process_db_id = db.id
;


COMMIT;