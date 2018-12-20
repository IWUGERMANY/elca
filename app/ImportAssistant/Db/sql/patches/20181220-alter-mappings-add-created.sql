BEGIN;
SELECT
    public.register_patch('20181220-alter-mappings-add-created.sql', 'ImportAssistant');

ALTER TABLE import_assistant.process_config_mapping ADD "created" timestamptz(0)  NOT NULL DEFAULT now();

COMMIT;