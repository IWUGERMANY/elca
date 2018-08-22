BEGIN;
SELECT
    public.register_patch('20171127-init-process-config-attribute.sql', 'eLCA');

INSERT INTO elca.process_config_attributes (process_config_id, ident, numeric_value)
    SELECT DISTINCT m.process_config_id
         , '4108_compat' AS ident,
        1
    FROM import_assistant.process_config_mapping m
        LEFT JOIN elca.process_config_attributes a ON m.process_config_id = a.process_config_id
    WHERE a.id IS NULL;

COMMIT;