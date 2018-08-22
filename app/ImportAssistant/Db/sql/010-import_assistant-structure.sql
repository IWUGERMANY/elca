SET client_encoding = 'UTF8';
BEGIN;
CREATE SCHEMA import_assistant;
COMMIT;

SET search_path = import_assistant, public;

BEGIN;

CREATE TABLE import_assistant.process_config_mapping
(
      "id"                        serial       NOT NULL
    , "material_name"         varchar(200) NOT NULL
    , "process_config_id"         int          NOT NULL
    , "is_sibling"                boolean      NOT NULL DEFAULT false
    , "sibling_ratio"             numeric
    , "required_additional_layer" boolean      NOT NULL DEFAULT false
    , "process_db_id"             int          NOT NULL
    , PRIMARY KEY ("id")
    , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON DELETE CASCADE
    , FOREIGN KEY ("process_db_id") REFERENCES elca.process_dbs ("id") ON DELETE CASCADE
);

CREATE INDEX IX_import_assistant_process_config_mapping_process_db_id ON import_assistant.process_config_mapping (process_db_id);


COMMIT;