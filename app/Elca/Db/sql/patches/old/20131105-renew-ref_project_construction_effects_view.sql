BEGIN;
SELECT public.register_patch('renew-ref_project_construction_effects-view', 'elca');


DROP VIEW IF EXISTS elca_cache.ref_project_construction_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.ref_project_construction_effects_v AS
    SELECT p.process_db_id
         , ci.indicator_id
         , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
         , min(ci.value / (p.life_time * c.net_floor_space)) AS min
         , max(ci.value / (p.life_time * c.net_floor_space)) AS max
         , count(*) AS counter
      FROM elca.projects p
      JOIN elca.project_variants       v ON p.id = v.project_id
      JOIN elca.project_constructions  c ON v.id = c.project_variant_id
      JOIN elca_cache.element_types   ct ON ct.project_variant_id = v.id
      JOIN elca.element_types_v        t ON t.level = 1 AND t.node_id = ct.element_type_node_id
      JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total'
     WHERE p.is_reference = true
  GROUP BY p.process_db_id
         , ci.indicator_id;

COMMIT;
