BEGIN;
SELECT public.register_patch('add-rec-pot-report-views', 'elca_cache');

DROP VIEW IF EXISTS elca_cache.report_total_energy_recycling_potential;
DROP VIEW IF EXISTS elca_cache.report_total_construction_recycling_effects_v;
CREATE VIEW elca_cache.report_total_construction_recycling_effects_v AS
    SELECT ci.item_id
        , ci.indicator_id
        , ci.value
        , i.name AS name
        , i.ident AS ident
        , i.unit AS unit
        , i.p_order AS indicator_p_order
        , v.project_variant_id
        , 'D stofflich'::varchar AS category
        , l.p_order AS life_cycle_p_order
        , l.ident AS life_cycle_ident
    FROM elca_cache.element_types_v v
        JOIN elca_cache.indicators ci ON ci.item_id = v.item_id
        JOIN elca.indicators i ON i.id = ci.indicator_id
        JOIN elca.life_cycles l ON l.ident = ci.life_cycle_ident
    WHERE v.level = 0
          AND l.phase = 'rec';

CREATE VIEW elca_cache.report_total_energy_recycling_potential AS
    SELECT null::int AS item_id
        , t.indicator_id
        , t.name
        , t.ident
        , t.unit
        , t.indicator_p_order
        , t.project_variant_id
        , 'D energetisch'::varchar AS category
        , r.life_cycle_p_order
        , r.life_cycle_ident
        , t.value - r.value AS value
    FROM elca_cache.report_life_cycle_effects_v t
        JOIN elca_cache.report_total_construction_recycling_effects_v r ON t.project_variant_id = r.project_variant_id
                                                                           AND t.indicator_id = r.indicator_id
    WHERE t.life_cycle_phase = 'rec';

COMMIT;