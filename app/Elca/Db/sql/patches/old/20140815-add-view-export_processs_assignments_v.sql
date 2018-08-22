BEGIN;
SELECT public.register_patch('add-view-export_process_assignments_v', 'elca');

CREATE OR REPLACE VIEW elca.export_process_assignments_v AS
  SELECT p.*
    , c.ref_num AS category_ref_num
    , a.id AS process_life_cycle_assignment_id
    , a.process_config_id
    , a.ratio
  FROM elca.processes p
    JOIN elca.process_life_cycle_assignments a ON p.id = a.process_id
    JOIN elca.process_categories c ON c.node_id = p.process_category_node_id;

COMMIT;