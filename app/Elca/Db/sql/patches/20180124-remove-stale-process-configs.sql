BEGIN;
SELECT
    public.register_patch('20180124-remove-stale-process-configs.sql', 'eLCA');

-- remove unreferenced stale process_configs
WITH stale_process_configs AS (
    SELECT
        pc.id,
        pc.name,
        pc.created,
        pc.modified
    FROM elca.process_configs pc
        LEFT JOIN elca.process_assignments_v a ON a.process_config_id = pc.id
    WHERE a.id IS NULL AND pc.is_stale
),
unused_process_configs AS (
        SELECT
            p.*
        FROM stale_process_configs p
            LEFT JOIN elca.element_components ec ON ec.process_config_id = p.id
        WHERE ec.id IS NULL
    )
DELETE FROM elca.process_configs WHERE id IN (SELECT id FROM unused_process_configs);

-- update referenced element components with UNKNOWN process_config
-- and add old process config name as attribute
WITH stale_process_configs AS (
    SELECT
        pc.id,
        pc.name,
        pc.created,
        pc.modified
    FROM elca.process_configs pc
        LEFT JOIN elca.process_assignments_v a ON a.process_config_id = pc.id
    WHERE a.id IS NULL AND pc.is_stale
),
invalid_element_components AS (
        SELECT
            ec.*,
            p.name AS old_process_config_name
        FROM stale_process_configs p
            LEFT JOIN elca.element_components ec ON ec.process_config_id = p.id
        WHERE ec.id IS NOT NULL
    ),
fixed_element_components AS (
UPDATE elca.element_components
    SET process_config_id = ( SELECT id FROM elca.process_configs WHERE uuid = 'ffffffff-ffff-ffff-ffff-ffffffffffff')
WHERE id IN ( SELECT id FROM invalid_element_components)
)
INSERT INTO elca.element_component_attributes (element_component_id, ident, numeric_value, text_value)
    SELECT id, 'elca.unknown', null, old_process_config_name
        FROM invalid_element_components;


-- update process conversion id
WITH unknown_element_components AS (
    SELECT
        ec.id,
        ec.process_conversion_id,
        c.in_unit
    FROM elca.element_components ec
        JOIN elca.process_conversions c ON c.id = ec.process_conversion_id
        JOIN elca.process_configs pc ON pc.id = c.process_config_id
    WHERE ec.process_config_id = (
        SELECT
            id
        FROM elca.process_configs
        WHERE uuid = 'ffffffff-ffff-ffff-ffff-ffffffffffff')
          AND ec.process_config_id <> pc.id
),
unknown_conversions AS (
        SELECT c.*
          FROM elca.process_conversions c
              JOIN elca.process_configs pc ON pc.id = c.process_config_id
        WHERE pc.uuid = 'ffffffff-ffff-ffff-ffff-ffffffffffff'
)
UPDATE elca.element_components u
   SET process_conversion_id = (
       SELECT id
       FROM unknown_conversions
       WHERE in_unit = x.in_unit
       LIMIT 1
   )
FROM unknown_element_components x
WHERE u.id = x.id;


-- remove all remaining stale process configs
WITH stale_process_configs AS (
    SELECT
        pc.id,
        pc.name,
        pc.created,
        pc.modified
    FROM elca.process_configs pc
        LEFT JOIN elca.process_assignments_v a ON a.process_config_id = pc.id
    WHERE a.id IS NULL AND pc.is_stale
)
DELETE FROM elca.process_configs WHERE id IN (SELECT id FROM stale_process_configs);


CREATE OR REPLACE VIEW elca.element_process_config_sanities_v AS
    SELECT
          e.id    AS element_id
        , e.name  AS element_name
        , t.din_code
        , CASE WHEN c.is_layer
        THEN c.layer_position
          ELSE NULL
          END     AS layer_position
        , pc.name AS process_config_name
        , e.access_group_id
    FROM
        elca.process_configs pc
        JOIN
        elca.element_components c ON pc.id = c.process_config_id
        JOIN
        elca.elements e ON e.id = c.element_id
        JOIN
        elca.element_types t ON t.node_id = e.element_type_node_id

    WHERE pc.is_stale = true OR pc.uuid = 'ffffffff-ffff-ffff-ffff-ffffffffffff'
                                AND e.project_variant_id IS NULL;

COMMIT;