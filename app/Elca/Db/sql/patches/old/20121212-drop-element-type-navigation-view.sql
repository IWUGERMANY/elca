BEGIN;
SELECT public.register_patch('dropped-element-type-navigation-view', 'elca');
DROP VIEW IF EXISTS elca.element_types_navigation_v;
COMMIT;