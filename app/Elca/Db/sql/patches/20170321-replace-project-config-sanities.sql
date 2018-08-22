BEGIN;
SELECT public.register_patch('20170321-replace-project-config-sanities.sql', 'eLCA');

DROP FUNCTION IF EXISTS elca.project_process_config_sanities(int,int);
DROP TYPE elca.project_process_config_sanity;
CREATE TYPE elca.project_process_config_sanity AS (
    context text,
    context_id int,
    context_name text,
    parent_context_id int,
    parent_context text,
    process_config_name text,
    process_config_id int,
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
        ce.composite_element_id AS parent_context_id,
        ce.composite_din_code ||' '|| ce.composite_element AS parent_context,
        t.din_code ||' '|| e.name AS context_name,
        pc.name AS process_config_name,
        pc.id AS process_config_id,
        array_to_string(array_agg(DISTINCT a.process_db_name), ', ') AS process_db_names,
        bool_and(a.process_db_id IS NULL OR a.process_db_id <> in_project_process_db_id) AS is_invalid
    FROM
        elca.elements e
        JOIN
        elca.element_types t ON t.node_id = e.element_type_node_id
        JOIN
        elca.element_components c ON e.id = c.element_id
        JOIN
        elca.process_configs pc ON pc.id = c.process_config_id
        LEFT JOIN (SELECT ce.composite_element_id
                       , ce.element_id
                       , t.din_code AS composite_din_code
                       , e.name AS composite_element
                   FROM elca.composite_elements ce
                       JOIN elca.elements e ON ce.composite_element_id = e.id
                       JOIN elca.element_types t ON t.node_id = e.element_type_node_id

                  ) ce ON ce.element_id = e.id
        LEFT JOIN
        process_config_assignments a ON pc.id = a.process_config_id
    WHERE
        e.project_variant_id = in_project_variant_id
    GROUP BY
        t.din_code,
        ce.composite_element_id,
        ce.composite_element,
        ce.composite_din_code,
        e.id,
        e.name,
        pc.name,
        pc.id
    HAVING
        bool_and(a.process_db_id IS NULL OR a.process_db_id <> in_project_process_db_id)
), final_energy_demands AS (
    SELECT
        'final_energy_demands'::text AS context,
        f.id AS context_id,
        null::int AS parent_context_id,
        ''::text AS parent_context,
        pc.name AS context_name,
        pc.name AS process_config_name,
        pc.id AS process_config_id,
        array_to_string(array_agg(DISTINCT a.process_db_name), ', ') AS process_db_names,
        bool_and(a.process_db_id IS NULL OR a.process_db_id <> in_project_process_db_id) AS is_invalid
    FROM
        elca.project_final_energy_demands f
        JOIN
        elca.process_configs pc ON pc.id = f.process_config_id
        LEFT JOIN
        process_config_assignments a ON pc.id = a.process_config_id
    WHERE
        f.project_variant_id = in_project_variant_id
    GROUP BY
        f.id,
        pc.name,
        pc.id
    HAVING
        bool_and(a.process_db_id IS NULL OR a.process_db_id <> in_project_process_db_id)
), final_energy_supplies AS (
    SELECT
        'final_energy_supplies'::text AS context,
        f.id                                                                 AS context_id,
        null::int AS parent_context_id,
        ''::text AS parent_context,
        pc.name                                                              AS context_name,
        pc.name                                                              AS process_config_name,
        pc.id AS process_config_id,
        array_to_string(array_agg(DISTINCT a.process_db_name), ', ')       AS process_db_names,
        bool_and(a.process_db_id IS NULL OR a.process_db_id <> in_project_process_db_id) AS is_invalid
    FROM
        elca.project_final_energy_supplies f
        JOIN
        elca.process_configs pc
            ON pc.id = f.process_config_id
        LEFT JOIN
        process_config_assignments a
            ON pc.id = a.process_config_id
    WHERE
        f.project_variant_id = in_project_variant_id
    GROUP BY
        f.id,
        pc.name,
        pc.id
    HAVING
        bool_and(a.process_db_id IS NULL OR a.process_db_id <> in_project_process_db_id)
)
SELECT
    context,
    context_id,
    context_name,
    parent_context_id,
    parent_context,
    process_config_name,
    process_config_id,
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
    parent_context_id,
    parent_context,
    process_config_name,
    process_config_id,
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
    parent_context_id,
    parent_context,
    process_config_name,
    process_config_id,
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