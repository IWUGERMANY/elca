BEGIN;
SELECT public.register_patch('add-project-process_config_sanities', 'elca');

CREATE TYPE elca.project_process_config_sanity AS (
    context text,
    context_id int,
    context_name text,
    process_config_name text,
    process_db_names text,
    is_invalid bool
);

CREATE OR REPLACE FUNCTION elca.project_process_config_sanities(in_project_variant_id int, in_project_process_db_id int)
    RETURNS SETOF elca.project_process_config_sanity
AS $$

    WITH process_config_assignments AS (
        SELECT
            a.process_config_id,
            p.process_db_id,
            d.name AS process_db_name
        FROM
            elca.process_life_cycle_assignments a
            JOIN
            elca.processes p
                ON p.id = a.process_id
            JOIN
            elca.process_dbs d
                ON d.id = p.process_db_id
    ), elements AS (
        SELECT
            'elements'::text AS context,
            e.id AS context_id,
            t.din_code ||' '|| e.name AS context_name,
            pc.name AS process_config_name,
            array_to_string(array_agg(DISTINCT a.process_db_name), ', ') AS process_db_names,
            bool_and(a.process_db_id IS NULL OR a.process_db_id <> $2) AS is_invalid
        FROM
            elca.elements e
        JOIN
            elca.element_types t ON t.node_id = e.element_type_node_id
        JOIN
            elca.element_components c ON e.id = c.element_id
        JOIN
            elca.process_configs pc ON pc.id = c.process_config_id
        LEFT JOIN
            process_config_assignments a ON pc.id = a.process_config_id
        WHERE
            e.project_variant_id = $1
        GROUP BY
            t.din_code,
            e.id,
            e.name,
            pc.name
        HAVING
            bool_and(a.process_db_id IS NULL OR a.process_db_id <> $2)
    ), final_energy_demands AS (
        SELECT
            'final_energy_demands'::text AS context,
            f.id AS context_id,
            pc.name AS context_name,
            pc.name AS process_config_name,
            array_to_string(array_agg(DISTINCT a.process_db_name), ', ') AS process_db_names,
            bool_and(a.process_db_id IS NULL OR a.process_db_id <> $2) AS is_invalid
        FROM
            elca.project_final_energy_demands f
        JOIN
            elca.process_configs pc ON pc.id = f.process_config_id
        LEFT JOIN
            process_config_assignments a ON pc.id = a.process_config_id
        WHERE
            f.project_variant_id = $1
        GROUP BY
            f.id,
            pc.name
        HAVING
            bool_and(a.process_db_id IS NULL OR a.process_db_id <> $2)
    ), final_energy_supplies AS (
        SELECT
            'final_energy_supplies'::text AS context,
            f.id                                                                 AS context_id,
            pc.name                                                              AS context_name,
            pc.name                                                              AS process_config_name,
            array_to_string(array_agg(DISTINCT a.process_db_name), ', ')       AS process_db_names,
            bool_and(a.process_db_id IS NULL OR a.process_db_id <> $2) AS is_invalid
        FROM
            elca.project_final_energy_supplies f
            JOIN
            elca.process_configs pc
                ON pc.id = f.process_config_id
            LEFT JOIN
            process_config_assignments a
                ON pc.id = a.process_config_id
        WHERE
            f.project_variant_id = $1
        GROUP BY
            f.id,
            pc.name
        HAVING
            bool_and(a.process_db_id IS NULL OR a.process_db_id <> $2)
    )
    SELECT
        context,
        context_id,
        context_name,
        process_config_name,
        process_db_names,
        is_invalid
     FROM
         elements
     WHERE
         is_invalid
    UNION
    SELECT
        context,
        context_id,
        context_name,
        process_config_name,
        process_db_names,
        is_invalid
    FROM
        final_energy_demands
    WHERE
        is_invalid
    UNION
    SELECT
        context,
        context_id,
        context_name,
        process_config_name,
        process_db_names,
        is_invalid
    FROM
        final_energy_supplies
    WHERE
        is_invalid
    ORDER BY
        context_name,
        process_config_name;

$$ LANGUAGE SQL;

COMMIT;