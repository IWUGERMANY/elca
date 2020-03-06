BEGIN;
SELECT
    public.register_patch('20180109-add-missing-mass-conversion-check.sql', 'eLCA');

DROP VIEW IF EXISTS elca.process_config_sanities_v;
CREATE VIEW elca.process_config_sanities_v AS
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
        , null :: int           AS process_db_id
    FROM elca.process_configs pc
        JOIN (
                 SELECT DISTINCT
                     process_config_id
                     , a.ref_unit AS in
                     , b.ref_unit AS out
                 FROM elca.process_assignments_v a
                     JOIN elca.process_assignments_v b USING (process_config_id)
                 WHERE 'op' NOT IN (a.life_cycle_phase, b.life_cycle_phase) AND a.ref_unit <> b.ref_unit
             ) a ON pc.id = a.process_config_id
        LEFT JOIN elca.process_conversions c
            ON pc.id = c.process_config_id AND (a.in, a.out) IN ((c.in_unit, c.out_unit), (c.out_unit, c.in_unit))
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
        null :: int           AS process_db_id
    FROM elca.process_configs pc
        JOIN elca.process_assignments_v a ON pc.id = a.process_config_id AND a.life_cycle_phase = 'prod' AND a.ref_unit = 'm2'
    WHERE pc.density IS NULL
    UNION
    SELECT
        'MISSING_MASS_CONVERSION' AS status,
        pc.id AS process_config_id,
        pc.name,
        pc.process_category_node_id,
        null :: int           AS process_db_id
    FROM elca.process_configs pc
    JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE
        NOT EXISTS(SELECT * FROM elca.process_conversions c WHERE pc.id = c.process_config_id AND 'kg' IN (c.in_unit, c.out_unit))
    GROUP BY
        pc.id
        , pc.name
        , pc.process_category_node_id
    HAVING 'op' != ANY (array_agg(DISTINCT a.life_cycle_phase))
      AND 'kg' != ANY (array_agg(DISTINCT a.ref_unit));

COMMIT;