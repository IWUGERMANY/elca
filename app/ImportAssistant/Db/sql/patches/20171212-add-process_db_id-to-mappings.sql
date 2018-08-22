BEGIN;
SELECT
    public.register_patch('20171212-add-process_db_id-to-mappings.sql', 'eLCA');

DROP VIEW import_assistant.process_config_mapping_conversions_view;

ALTER TABLE import_assistant.process_config_mapping ADD process_db_id int;
UPDATE import_assistant.process_config_mapping SET process_db_id = (SELECT max(id) FROM elca.process_dbs WHERE is_active);
ALTER TABLE import_assistant.process_config_mapping ALTER process_db_id SET NOT NULL;
ALTER TABLE import_assistant.process_config_mapping ADD FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON DELETE CASCADE;
CREATE INDEX IX_import_assistant_process_config_mapping_process_db_id ON import_assistant.process_config_mapping (process_db_id);

CREATE OR REPLACE VIEW import_assistant.process_config_mapping_conversions_view AS
    SELECT m.id
        , m.material_name
        , m.process_config_id
        , pc.name AS process_config_name
        , m.is_sibling
        , m.sibling_ratio
        , m.required_additional_layer
        , m.process_db_id
        , pc.epd_types
        , pc.process_db_ids
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
        , m.process_db_id
        , pc.epd_types
        , pc.process_db_ids
;


COMMIT;