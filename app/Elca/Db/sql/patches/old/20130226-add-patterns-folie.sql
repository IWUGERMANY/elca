BEGIN;
SELECT public.register_patch('add-patterns-folie', 'elca');

INSERT INTO elca.svg_patterns (id, name, width, height) VALUES (7, 'elca_pattern_folie', 20, 5);

UPDATE elca.process_category_svg_patterns
   SET svg_pattern_id = 7
 WHERE process_category_node_id IN (SELECT node_id
                                      FROM elca.process_categories
                                     WHERE ref_num ilike '6%');

SELECT setval('elca.svg_patterns_id_seq', 7);
COMMIT;