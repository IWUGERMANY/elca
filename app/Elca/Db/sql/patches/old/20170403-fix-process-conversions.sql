BEGIN;
SELECT public.register_patch('20170403-fix-process-conversions', 'eLCA');

WITH invalid_element_components AS (
    SELECT
        ec.id                    AS element_component_id,
        ec.element_id,
        ec.process_config_id,
        new_pc.name              AS new_process_config,
        old_pc.name              AS old_process_config,
        ec.process_conversion_id AS invalid_process_conversion_id,
        old_c.in_unit,
        old_c.out_unit,
        old_c.factor
    FROM elca.element_components ec
        JOIN elca.process_conversions old_c ON old_c.id = ec.process_conversion_id
        JOIN elca.process_configs new_pc ON new_pc.id = ec.process_config_id
        JOIN elca.process_configs old_pc ON old_pc.id = old_c.process_config_id
        LEFT JOIN elca.process_conversions c
            ON c.process_config_id = ec.process_config_id AND c.id = ec.process_conversion_id
    WHERE c.id IS NULL
),
        fix AS (
        SELECT
            ec.element_component_id,
            ec.element_id,
            ec.process_config_id,
            ec.old_process_config,
            ec.in_unit  AS old_in_unit,
            ec.out_unit  AS old_out_unit,
            ec.factor AS old_factor,
            ec.new_process_config,
            c.in_unit AS new_in_unit,
            c.out_unit AS new_out_unit,
            c.factor AS new_factor,
            c.id AS valid_process_conversion_id
        FROM invalid_element_components ec
            JOIN elca.process_conversions c ON c.process_config_id = ec.process_config_id AND c.in_unit = ec.in_unit AND c.out_unit = ec.out_unit
    )
UPDATE elca.element_components c
SET process_conversion_id = f.valid_process_conversion_id
FROM fix f
WHERE f.element_component_id = c.id;

COMMIT;