BEGIN;
SELECT public.register_patch('insert-more-svg-patterns', 'elca');

INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (4, 'elca_pattern_holz_diele', 10, 10);
INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (5, 'elca_pattern_putz', 8.32, 8.32);
INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (6, 'elca_pattern_sand', 8.32, 8.32);

UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 4 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num ILIKE '3.%');
UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 5 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num = '1.04');
UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 6 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num = '1.01');

COMMIT;