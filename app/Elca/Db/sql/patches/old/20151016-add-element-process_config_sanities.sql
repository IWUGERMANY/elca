BEGIN;
SELECT public.register_patch('add-element-process_config_sanities', 'elca');

CREATE OR REPLACE VIEW elca.element_process_config_sanities_v AS
    SELECT
        e.id AS element_id,
        e.name AS element_name,
        t.din_code,
        CASE WHEN c.is_layer THEN c.layer_position
        ELSE NULL
        END AS layer_position,
        pc.name AS process_config_name,
        e.access_group_id
    FROM
        elca.process_configs pc
        JOIN
        elca.element_components c ON pc.id = c.process_config_id
        JOIN
        elca.elements e ON e.id = c.element_id
        JOIN
        elca.element_types t ON t.node_id = e.element_type_node_id

    WHERE pc.is_stale = true
          AND e.project_variant_id IS NULL;
COMMIT;