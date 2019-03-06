BEGIN;
SELECT public.register_patch('20190306-fix-general-costs-calculation.sql', 'lcc');

CREATE OR REPLACE FUNCTION lcc.compute_regular_costs(in_project_variant_id int, in_project_id int, in_life_time int, in_calc_method smallint)
    RETURNS void
AS $$
    -- clear progressions
DELETE FROM lcc.project_cost_progressions
WHERE (project_variant_id, calc_method) = ($1, $4);

    -- regular costs
INSERT INTO lcc.project_cost_progressions (project_variant_id, calc_method, grouping, life_time, quantity)
SELECT
    p.project_variant_id
     , p.calc_method
     , p.grouping
     , iteration AS life_time
     , SUM(coalesce(p.quantity, 0)                  -- quantity per year
               * COALESCE(c.ref_value, p.ref_value, 0)  -- project specific refValue
               * (1 + CASE WHEN p.grouping = 'WATER'
                               THEN v.water_price_inc
                           WHEN p.grouping = 'ENERGY'
                               THEN v.energy_price_inc
                           WHEN p.grouping = 'CLEANING'
                               THEN v.cleaning_price_inc
                           ELSE 0
        END
                     ) ^ iteration / (1 + v.rate) ^ iteration
    * CASE WHEN p.ident = 'CREDIT_EEG' AND iteration > 20
               THEN -1
           WHEN p.ident = 'CREDIT_EEG' AND iteration <= 20
               THEN 0
           ELSE 1
               END
    )         AS total
FROM lcc.versions v
         JOIN lcc.project_versions l ON v.id = l.version_id
         JOIN lcc.project_costs_v p
ON p.version_id = v.id AND (p.project_variant_id, p.calc_method) = (l.project_variant_id, l.calc_method)
         JOIN lcc.regular_costs c ON p.id = c.cost_id
         CROSS JOIN generate_series(1, $3) AS iteration -- iterate over each year
WHERE p.grouping IN ('WATER', 'ENERGY', 'CLEANING')
    AND (l.project_variant_id, l.calc_method) = ($1, $4)
GROUP BY p.project_variant_id
       , p.calc_method
       , p.grouping
       , iteration;

    -- regular service costs
INSERT INTO lcc.project_cost_progressions (project_variant_id, calc_method, grouping, life_time, quantity)
SELECT
    p.project_variant_id
     , p.calc_method
     , p.grouping
     , iteration AS life_time
     , SUM(coalesce(p.quantity, 0)                               -- quantity per year
               * (s.maintenance_perc + s.service_perc)
               * (1 + v.common_price_inc) ^ iteration
    / (1 + v.rate) ^ iteration
    )         AS total
FROM lcc.versions v
         JOIN lcc.project_versions l ON v.id = l.version_id
         JOIN lcc.project_costs_v p ON (p.version_id = v.id OR project_id = $2)
    AND
                                       (p.project_variant_id, p.calc_method) = (l.project_variant_id, l.calc_method)
         JOIN lcc.regular_service_costs s ON p.id = s.cost_id
         CROSS JOIN generate_series(1, $3) AS iteration -- iterate over each year
WHERE p.grouping IN ('KGR300', 'KGR400', 'KGR500')
    AND (l.project_variant_id, l.calc_method) = ($1, $4)
    AND (s.maintenance_perc <> 0 OR s.service_perc <> 0) --
GROUP BY p.project_variant_id
       , p.calc_method
       , p.grouping
       , iteration;

$$ LANGUAGE SQL;

COMMIT;