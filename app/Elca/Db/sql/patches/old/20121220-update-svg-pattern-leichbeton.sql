BEGIN;
SELECT public.register_patch('20121220-update-svg-pattern-leichbeton', 'elca');

UPDATE elca.svg_patterns SET width = 16.5, height = 16.5 WHERE name = 'elca_pattern_leichtbeton';

COMMIT;