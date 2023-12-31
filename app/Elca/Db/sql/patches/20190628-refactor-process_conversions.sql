BEGIN;
SELECT public.register_patch('20190628-refactor-process_conversions.sql', 'eLCA');

CREATE TABLE elca.process_conversion_versions
(
      "conversion_id"          int             NOT NULL                -- conversionId
    , "process_db_id"          int             NOT NULL                -- processDbId
    , "factor"                 numeric         NOT NULL                -- conversion factor
    , "ident"                  varchar(20)                             -- internal ident
    , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
    , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
    , PRIMARY KEY ("conversion_id", "process_db_id")
    , FOREIGN KEY ("conversion_id") REFERENCES elca.process_conversions ("id") ON UPDATE CASCADE ON DELETE CASCADE
    , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

INSERT INTO elca.process_conversion_versions (conversion_id, process_db_id, factor, ident, created, modified)
  SELECT DISTINCT c.id
        , pa.process_db_id
        , c.factor
        , c.ident
        , c.created
        , c.modified
   FROM elca.process_conversions c
         JOIN elca.process_assignments_v pa ON c.process_config_id = pa.process_config_id;

DROP VIEW IF EXISTS elca.process_config_sanities_v;
DROP VIEW IF EXISTS elca_cache.project_variant_process_config_mass_v;

ALTER TABLE elca.process_conversions DROP COLUMN "factor";
ALTER TABLE elca.process_conversions DROP COLUMN "ident";

CREATE OR REPLACE VIEW elca.process_conversions_v AS
SELECT c.id
        , c.process_config_id
        , v.process_db_id
        , c.in_unit
        , c.out_unit
        , v.factor
        , v.ident
        , v.created
        , v.modified
FROM elca.process_conversions c
         JOIN elca.process_conversion_versions v ON c.id = v.conversion_id;

CREATE OR REPLACE VIEW elca_cache.project_variant_process_config_mass_v AS
SELECT e.project_variant_id
     , c.process_config_id
     , p.name
     , sum(cec.mass) AS mass
     , sum(CASE WHEN pc.factor IS NOT NULL THEN cec.mass / pc.factor ELSE null END) AS volume
FROM elca_cache.element_components cec
         JOIN elca.element_components c ON c.id = cec.element_component_id
         JOIN elca.elements e ON e.id = c.element_id
         JOIN elca.process_configs p ON p.id = c.process_config_id
         JOIN elca.project_variants pv ON pv.id = e.project_variant_id
         JOIN elca.projects proj ON proj.id = pv.project_id
         LEFT JOIN elca.process_conversions_v pc ON p.id = pc.process_config_id
    AND (pc.in_unit, pc.out_unit) = ('m3', 'kg')
    AND (pc.process_db_id = proj.process_db_id)
GROUP BY e.project_variant_id
       , c.process_config_id
       , p.name;


DROP VIEW IF EXISTS elca.process_config_sanities_v;
CREATE OR REPLACE VIEW elca.process_config_sanities_v AS
    SELECT
        'STALE'     AS status
            , pc.id       AS process_config_id
            , pc.name
            , pc.process_category_node_id
            , null :: int AS process_db_id
    FROM elca.process_configs pc
    WHERE is_stale = true
    UNION
    SELECT
        'MISSING_LIFE_TIME' AS status
            , pc.id               AS process_config_id
            , pc.name
            , pc.process_category_node_id
            , null :: int         AS process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE coalesce(pc.min_life_time, pc.avg_life_time, pc.max_life_time) IS NULL
    GROUP BY pc.id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id
    HAVING 'op' != ANY (array_agg(DISTINCT a.life_cycle_phase))
    UNION
    SELECT
        'MISSING_CONVERSIONS' AS status
            , pc.id                 AS process_config_id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id           AS process_db_id
    FROM elca.process_configs pc
             JOIN (
        SELECT DISTINCT
            process_config_id
                , process_db_id
                , a.ref_unit AS in
                , b.ref_unit AS out
        FROM elca.process_assignments_v a
                 JOIN elca.process_assignments_v b USING (process_config_id, process_db_id)
        WHERE 'op' NOT IN (a.life_cycle_phase, b.life_cycle_phase)
                AND a.ref_unit <> b.ref_unit
    ) a ON pc.id = a.process_config_id
             LEFT JOIN elca.process_conversions_v c ON pc.id = c.process_config_id AND (a.in, a.out) IN ((c.in_unit, c.out_unit), (c.out_unit, c.in_unit))
    WHERE c.id IS NULL
    UNION
    SELECT
        'MISSING_PRODUCTION' AS status
            , pc.id                AS process_config_id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE a.life_cycle_phase != 'op'
    GROUP BY pc.id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id
    HAVING 'prod' != ALL (array_agg(DISTINCT a.life_cycle_phase))
    UNION
    SELECT
        'MISSING_EOL' AS status
            , pc.id         AS process_config_id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE a.life_cycle_phase != 'op'
    GROUP BY pc.id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id
    HAVING 'eol' != ALL (array_agg(DISTINCT a.life_cycle_phase))
    UNION
    SELECT DISTINCT
        'MISSING_DENSITY' AS status,
        pc.id AS process_config_id,
        pc.name,
        pc.process_category_node_id,
        a.process_db_id   AS process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id AND a.life_cycle_phase = 'prod' AND a.ref_unit = 'm2'
             LEFT JOIN elca.process_conversions_v c ON pc.id = c.process_config_id AND c.process_db_id = a.process_db_id AND c.in_unit = 'm3' AND c.out_unit = 'kg'
    WHERE c.id IS NULL
    UNION
    SELECT
        'MISSING_MASS_CONVERSION' AS status,
        pc.id AS process_config_id,
        pc.name,
        pc.process_category_node_id,
        a.process_db_id           AS process_db_id
    FROM elca.process_configs pc
             JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE
        NOT EXISTS(SELECT * FROM elca.process_conversions_v c WHERE (pc.id, a.process_db_id) = (c.process_config_id, a.process_db_id) AND 'kg' IN (c.in_unit, c.out_unit))
    GROUP BY
        pc.id
            , pc.name
            , pc.process_category_node_id
            , a.process_db_id
    HAVING 'op' != ANY (array_agg(DISTINCT a.life_cycle_phase))
            AND 'kg' != ANY (array_agg(DISTINCT a.ref_unit));


COMMIT;
