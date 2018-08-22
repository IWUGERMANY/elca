BEGIN;
SELECT
    public.register_patch('20171129-add-epd-subtypes-to-mapping_conversions_view.sql', 'eLCA');

DROP VIEW import_assistant.process_config_mapping_conversions_view;
CREATE OR REPLACE VIEW import_assistant.process_config_mapping_conversions_view AS
    SELECT m.id
        , m.material_name
        , m.process_config_id
        , pc.name AS process_config_name
        , m.is_sibling
        , m.sibling_ratio
        , m.required_additional_layer
        , pc.epd_types
        , array_agg(DISTINCT c.in_unit) FILTER (WHERE c.id IS NOT NULL) AS units
    FROM import_assistant.process_config_mapping m
        JOIN elca.process_config_process_dbs_view pc ON pc.id = m.process_config_id
        JOIN elca.process_conversions c ON c.process_config_id = m.process_config_id
    GROUP BY m.id
        , m.material_name
        , m.process_config_id
        , pc.name
        , m.is_sibling
        , m.sibling_ratio
        , m.required_additional_layer
        , pc.epd_types;

COMMIT;