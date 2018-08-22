BEGIN;
SELECT
    public.register_patch('20171122-update-sibling-ratio.sql', 'eLCA');

UPDATE import_assistant.process_config_mapping
    SET sibling_ratio = sibling_ratio / 100
WHERE sibling_ratio IS NOT NULL;

COMMIT;