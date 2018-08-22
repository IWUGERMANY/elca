BEGIN;
SELECT public.register_patch('add-project_indicator_benchmarks', 'elca');

CREATE TABLE elca.project_indicator_benchmarks
(
    "project_variant_id"      int             NOT NULL                -- projectVariantId
  , "indicator_id"            int             NOT NULL                -- indicatorId
  , "benchmark"               int             NOT NULL                -- benchmark
  , PRIMARY KEY ("project_variant_id", "indicator_id")
  , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("indicator_id") REFERENCES elca.indicators ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

DROP VIEW IF EXISTS elca_cache.report_life_cycle_effects_v;
CREATE VIEW elca_cache.report_life_cycle_effects_v AS
     SELECT ct.project_variant_id
          , ci.item_id
          , ci.indicator_id
          , ci.value
          , i.name AS name
          , i.ident AS ident
          , i.unit AS unit
          , i.p_order AS indicator_p_order
          , l.name AS category
          , l.ident AS life_cycle_ident
          , l.phase AS life_cycle_phase
          , l.p_order AS life_cycle_p_order
       FROM elca_cache.element_types_v ct
       JOIN elca_cache.indicators ci ON ci.item_id = ct.item_id
       JOIN elca.indicators i ON i.id = ci.indicator_id
       JOIN elca.life_cycles l ON ci.life_cycle_ident = l.ident
      WHERE ct.level = 0
        AND ci.is_partial = false;

DROP VIEW IF EXISTS elca_cache.ref_project_construction_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.ref_project_construction_effects_v AS
    SELECT p.process_db_id
         , ci.indicator_id
         , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
         , min(ci.value / (p.life_time * c.net_floor_space)) AS min
         , max(ci.value / (p.life_time * c.net_floor_space)) AS max
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
