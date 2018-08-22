BEGIN;
SELECT public.register_patch('add-pattern-metalle', 'elca');

INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (8, 'elca_pattern_metalle', 1, 1);

UPDATE elca.process_category_svg_patterns
   SET svg_pattern_id = 8
 WHERE process_category_node_id IN (SELECT node_id
                                      FROM elca.process_categories
                                     WHERE ref_num ilike '4%');

SELECT setval('elca.svg_patterns_id_seq', 8);
COMMIT;