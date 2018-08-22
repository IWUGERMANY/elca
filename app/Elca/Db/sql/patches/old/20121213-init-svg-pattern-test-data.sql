BEGIN;
SELECT public.register_patch('init-svg-pattern-test-data', 'elca');

-- test data
INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (1, 'elca_pattern_daemmung', 164.863, 29.1424);
INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (2, 'elca_pattern_beton_bewehrt', 40, 40);
INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (3, 'elca_pattern_leichtbeton', 40, 40);

INSERT INTO elca.process_category_svg_patterns SELECT node_id, 3 FROM elca.process_categories;
UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 1 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num ILIKE '2.%');
UPDATE elca.process_category_svg_patterns SET svg_pattern_id = 2 WHERE process_category_node_id IN (SELECT node_id FROM elca.process_categories WHERE ref_num = '1.03');

COMMIT;