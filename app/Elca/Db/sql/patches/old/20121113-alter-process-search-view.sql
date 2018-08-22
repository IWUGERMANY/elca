BEGIN;
SELECT public.register_patch('alter-process-search-view', 'elca');

DROP VIEW IF EXISTS elca.process_search_v;
CREATE VIEW elca.process_search_v AS
  SELECT p.id
       , p.process_db_id
       , p.name
       , p.life_cycle_ident
       , p.process_category_node_id
       , c.ref_num ||' '|| c.name AS process_category_node_name
       , c2.ref_num ||' '|| c2.name AS process_category_parent_node_name
    FROM elca.processes p
    JOIN elca.process_categories_v c  ON c.node_id = p.process_category_node_id
    JOIN elca.process_categories_v c2 ON c.lft BETWEEN c2.lft AND c2.rgt AND c.level = c2.level + 1;

COMMIT;