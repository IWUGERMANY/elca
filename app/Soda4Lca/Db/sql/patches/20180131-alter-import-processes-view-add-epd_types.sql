BEGIN;
SELECT
    public.register_patch('20180131-alter-import-processes-view-add-epd_types.sql', 'eLCA');

DROP VIEW IF EXISTS soda4lca.processes_v;
CREATE VIEW soda4lca.processes_v AS
    SELECT DISTINCT p.import_id
        , p.version
        , p.latest_version
        , p.status
        , p.error_code
        , p.uuid
        , p.details
        , COALESCE(c.ref_num ||' '|| a.name_orig, p.class_id||' '||p.name) AS name
        , CASE WHEN count(DISTINCT a.life_cycle_name) > 0 THEN array_to_string(array_agg(DISTINCT a.life_cycle_name), ', ')
          ELSE p.epd_modules
          END AS modules
        ,  replace(array_to_string(array_agg(DISTINCT a.epd_type), ', '), ' dataset', '') AS epd_types
    FROM soda4lca.processes         p
        LEFT JOIN elca.process_assignments_v a ON a.uuid = p.uuid
        LEFT JOIN elca.process_categories    c ON c.node_id = a.process_category_node_id
    GROUP BY p.import_id
        , c.ref_num
        , p.name
        , p.uuid
        , p.class_id
        , p.epd_modules
        , a.name_orig
        , p.version
        , p.status
        , p.error_code
        , p.details;

DROP VIEW IF EXISTS soda4lca.processes_with_process_configs_v;
CREATE VIEW soda4lca.processes_with_process_configs_v AS
    SELECT DISTINCT p.import_id
        , p.version
        , p.latest_version
        , p.status
        , p.error_code
        , p.uuid
        , p.details
        , COALESCE(c.ref_num ||' '|| a.name_orig, p.class_id||' '||p.name) AS name
        , array_to_string(array_agg(DISTINCT '"'||pc.name||'"'), ', ') AS process_configs
        , CASE WHEN count(DISTINCT a.life_cycle_name) > 0 THEN array_to_string(array_agg(DISTINCT a.life_cycle_name), ', ')
          ELSE p.epd_modules
          END AS modules
        ,  replace(array_to_string(array_agg(DISTINCT a.epd_type), ', '), ' dataset', '') AS epd_types
    FROM soda4lca.processes         p
        LEFT JOIN elca.process_assignments_v a ON a.uuid = p.uuid
        LEFT JOIN elca.process_categories    c ON c.node_id = a.process_category_node_id
        LEFT JOIN elca.process_configs      pc ON pc.id = a.process_config_id
    GROUP BY p.import_id
        , c.ref_num
        , p.name
        , p.uuid
        , p.class_id
        , p.epd_modules
        , a.name_orig
        , p.version
        , p.status
        , p.error_code
        , p.details;

COMMIT;