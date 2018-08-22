BEGIN;
SELECT public.register_patch('replace-process-config-search-view', 'elca');

DROP VIEW IF EXISTS elca.process_config_search_v;
CREATE VIEW elca.process_config_search_v AS
  SELECT p.id
       , p.name
       , p.process_category_node_id
       , p.is_reference
       , c.ref_num ||' '|| c.name AS process_category_node_name
       , c2.ref_num ||' '|| c2.name AS process_category_parent_node_name
    FROM elca.process_configs p
    JOIN elca.process_categories_v c  ON c.node_id = p.process_category_node_id
    JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;

COMMIT;
