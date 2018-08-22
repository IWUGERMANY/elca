BEGIN;
SELECT public.register_patch('renew-svg-patterns-assignments', 'elca');

UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 4 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num ILIKE '3.%');
UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 5 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num = '1.04');
UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 6 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num = '1.01');

COMMIT;