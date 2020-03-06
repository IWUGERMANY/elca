BEGIN;
SELECT public.register_patch('20200306-renew-process_config_search_view.sql', 'eLCA');

DROP VIEW IF EXISTS elca.process_config_search_v;
CREATE VIEW elca.process_config_search_v AS
SELECT
    p.id
        , p.name
        , p.description
        , p.process_category_node_id
        , p.is_reference
        , p.element_district_heating
        , p.process_db_ids
        , p.epd_types
        , c.ref_num || ' ' || c.name   AS process_category_node_name
        , c2.ref_num || ' ' || c2.name AS process_category_parent_node_name
FROM elca.process_config_process_dbs_view p
         JOIN elca.process_categories_v c ON c.node_id = p.process_category_node_id
         JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;


COMMIT;
