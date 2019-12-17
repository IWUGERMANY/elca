BEGIN;
SELECT
    public.register_patch('20180308-add-reference-project-views.sql', 'eLCA');

DROP VIEW IF EXISTS elca_cache.ref_project_construction_effects_v CASCADE;
CREATE OR REPLACE VIEW elca_cache.ref_project_construction_effects_v AS
    SELECT p.benchmark_version_id
        , ci.indicator_id
        , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
        , min(ci.value / (p.life_time * c.net_floor_space)) AS min
        , max(ci.value / (p.life_time * c.net_floor_space)) AS max
        , count(*) AS counter
    FROM elca.projects p
        JOIN elca.project_variants       v ON v.project_id = p.id
        JOIN elca.project_constructions  c ON v.id = c.project_variant_id
        JOIN elca_cache.element_types   ct ON ct.project_variant_id = v.id
        JOIN elca.element_types_v        t ON t.level = 0 AND t.node_id = ct.element_type_node_id
        JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total'
    WHERE p.is_reference = true
          AND ci.value > 0
    GROUP BY p.benchmark_version_id
        , ci.indicator_id;

DROP MATERIALIZED VIEW IF EXISTS elca_cache.reference_projects_effects_v;
CREATE MATERIALIZED VIEW elca_cache.reference_projects_effects_v AS
    SELECT p.benchmark_version_id
        , element_type_node_id
        , din_code
        , ci.indicator_id
        , avg(ci.value / (p.life_time * c.net_floor_space)) AS avg
        , stddev_pop(ci.value / (p.life_time * c.net_floor_space)) AS stddev
        , min(ci.value / (p.life_time * c.net_floor_space)) AS min
        , max(ci.value / (p.life_time * c.net_floor_space)) AS max
        , count(*) AS samples
    FROM elca.projects p
        JOIN elca.project_variants       v ON v.project_id = p.id
        JOIN elca.project_constructions  c ON c.project_variant_id = v.id
        JOIN elca_cache.element_types   ct ON ct.project_variant_id = p.current_variant_id
        JOIN elca.element_types_v        t ON t.level > 0 AND t.node_id = ct.element_type_node_id
        JOIN elca_cache.indicators      ci ON ci.item_id = ct.item_id AND ci.life_cycle_ident = 'total'
    WHERE p.benchmark_version_id IS NOT NULL
          AND p.is_reference = true
          AND ci.value > 0
    GROUP BY p.benchmark_version_id
        , element_type_node_id
        , din_code
        , ci.indicator_id;

REFRESH MATERIALIZED VIEW elca_cache.reference_projects_effects_v;


INSERT INTO elca.settings (section, ident, numeric_value)
    SELECT 'elca.admin.benchmark.projections.'||v.benchmark_version_id,
           split_part(s.ident, '.', 2)||'.'||split_part(s.ident, '.', 1),
           s.numeric_value
        FROM elca.settings s
            CROSS JOIN (
                SELECT DISTINCT benchmark_version_id FROM elca_cache.reference_projects_effects_v
                ) v
    WHERE section = 'elca.admin.benchmarks';



COMMIT;