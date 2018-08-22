BEGIN;
SELECT public.register_patch('add-svg-tables', 'elca');
CREATE TABLE elca.svg_patterns
(
   "id"                          serial          NOT NULL                -- svgPatternId
 , "name"                        varchar(150)    NOT NULL                -- pattern name
 , "width"                       numeric         NOT NULL                -- width
 , "height"                      numeric         NOT NULL                -- height
 , PRIMARY KEY ("id")
);

-------------------------------------------------------------------------------

CREATE TABLE elca.process_category_svg_patterns
(
   "process_category_node_id"    int             NOT NULL                -- processCategoryId
 , "svg_pattern_id"              int             NOT NULL                -- svgPatternId
 , PRIMARY KEY ("process_category_node_id", "svg_pattern_id")
 , FOREIGN KEY ("process_category_node_id") REFERENCES elca.process_categories ("node_id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("svg_pattern_id") REFERENCES elca.svg_patterns ("id") ON UPDATE CASCADE ON DELETE CASCADE
);
COMMIT;