BEGIN;
SELECT public.register_patch('alter-process_configs-add-is_stale', 'elca');

ALTER TABLE elca.process_configs ADD "is_stale" boolean NOT NULL DEFAULT false;

UPDATE elca.process_configs pc
   SET is_stale = true
  FROM elca.process_life_cycle_assignments a
WHERE pc.id IN (SELECT
                    c.id
                FROM
                    elca.process_configs c
                LEFT JOIN
                    elca.process_life_cycle_assignments a
                        ON c.id = a.process_config_id
                WHERE a.id IS NULL
);


CREATE OR REPLACE FUNCTION elca.update_process_config_is_stale()
    RETURNS trigger
AS $$

DECLARE
    proc_info record;
BEGIN
    SELECT pc.id
         , a.id AS assignment_id
      INTO proc_info
    FROM
        elca.process_configs pc
    LEFT JOIN
        elca.process_life_cycle_assignments a ON pc.id = a.process_config_id
    WHERE pc.id = CASE WHEN TG_OP = 'INSERT' THEN NEW.process_config_id ELSE OLD.process_config_id END
    LIMIT 1;

    IF proc_info.assignment_id IS NULL THEN
        UPDATE elca.process_configs SET is_stale = true WHERE id = proc_info.id;
    ELSE
        UPDATE elca.process_configs SET is_stale = false WHERE id = proc_info.id;
    END IF;

    RETURN null;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_process_config_is_stale AFTER INSERT OR DELETE  ON elca.process_life_cycle_assignments
FOR EACH ROW EXECUTE PROCEDURE elca.update_process_config_is_stale();


DROP VIEW IF EXISTS elca.process_configs_extended_search_v;
CREATE VIEW elca.process_configs_extended_search_v AS
    SELECT pc.id
        , pc.process_category_node_id
        , pc.name
        , pc.description
        , pc.life_time_info
        , pc.min_life_time
        , pc.min_life_time_info
        , pc.avg_life_time
        , pc.avg_life_time_info
        , pc.max_life_time
        , pc.max_life_time_info
        , pc.density
        , pc.thermal_conductivity
        , pc.thermal_resistance
        , pc.is_reference
        , pc.f_hs_hi
        , pc.uuid
        , pc.svg_pattern_id
        , pc.is_stale
        , pc.created
        , pc.modified
        , to_tsvector('german', pc.name ||' '||
                                coalesce(array_to_string(array_agg(DISTINCT p.uuid::text), ' '), '') ||' '||
                                coalesce(array_to_string(array_agg(DISTINCT p.name_orig), ' '), '')) AS search_vector
    FROM elca.process_configs pc
        LEFT JOIN elca.process_assignments_v p ON pc.id = p.process_config_id
    GROUP BY pc.id
        , pc.process_category_node_id
        , pc.name
        , pc.description
        , pc.life_time_info
        , pc.min_life_time
        , pc.min_life_time_info
        , pc.avg_life_time
        , pc.avg_life_time_info
        , pc.max_life_time
        , pc.max_life_time_info
        , pc.density
        , pc.thermal_conductivity
        , pc.thermal_resistance
        , pc.is_reference
        , pc.f_hs_hi
        , pc.uuid
        , pc.is_stale
        , pc.created
        , pc.modified;

DROP VIEW IF EXISTS elca.process_config_sanities_v;
CREATE VIEW elca.process_config_sanities_v AS
    SELECT 'STALE' AS status
        , pc.id AS process_config_id
        , pc.name
        , pc.process_category_node_id
        , null::int AS process_db_id
    FROM elca.process_configs pc
    WHERE is_stale = true
    UNION
    SELECT 'MISSING_LIFE_TIME' AS status
        , pc.id AS process_config_id
        , pc.name
        , pc.process_category_node_id
        , null::int AS process_db_id
    FROM elca.process_configs pc
        JOIN elca.process_assignments_v a ON pc.id = a.process_config_id
    WHERE coalesce(pc.min_life_time, pc.avg_life_time, pc.max_life_time) IS NULL
    GROUP BY pc.id
        , pc.name
        , pc.process_category_node_id
        , a.process_db_id
    HAVING 'op' != ANY (array_agg(DISTINCT a.life_cycle_phase))
    UNION
    SELECT 'MISSING_CONVERSIONS' AS status
        , pc.id AS process_config_id
        , pc.name
        , pc.process_category_node_id
        , null::int AS process_db_id
    FROM elca.process_configs pc
        JOIN (SELECT DISTINCT process_config_id
                  , a.ref_unit AS in
                  , b.ref_unit AS out
              FROM elca.process_assignments_v a
                  JOIN elca.process_assignments_v b USING (process_config_id)
              WHERE 'op' NOT IN (a.life_cycle_phase, b.life_cycle_phase) AND a.ref_unit <> b.ref_unit
             ) a ON pc.id = a.process_config_id
        LEFT JOIN elca.process_conversions c ON pc.id = c.process_config_id AND (a.in, a.out) IN ((c.in_unit, c.out_unit), (c.out_unit, c.in_unit))
    WHERE c.id IS NULL
    UNION
    SELECT 'MISSING_PRODUCTION' AS status
        , pc.id AS process_config_id
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
    SELECT 'MISSING_EOL' AS status
        , pc.id AS process_config_id
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
    HAVING 'eol' != ALL (array_agg(DISTINCT a.life_cycle_phase));

COMMIT;