BEGIN;
SELECT public.register_patch('alter-elca-process_categories', 'elca');

ALTER TABLE elca.process_categories ADD "svg_pattern_id" int;
ALTER TABLE elca.process_categories ADD FOREIGN KEY ("svg_pattern_id") REFERENCES elca.svg_patterns ("id") ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE elca.process_categories c
   SET svg_pattern_id = (SELECT p.svg_pattern_id
                           FROM elca.process_category_svg_patterns p
                          WHERE p.process_category_node_id = c.node_id
   );

DROP TABLE IF EXISTS elca.process_category_svg_patterns;

CREATE OR REPLACE VIEW elca.process_categories_v AS
  SELECT n.*
    , c.*
  FROM public.nested_nodes n
    JOIN elca.process_categories c ON n.id = c.node_id;

COMMIT;
