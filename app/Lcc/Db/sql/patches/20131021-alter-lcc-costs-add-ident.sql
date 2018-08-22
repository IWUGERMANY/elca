BEGIN;
SELECT public.register_patch('alter-lcc-costs-add-ident', 'lcc');

DROP VIEW IF EXISTS lcc.project_costs_v;

DROP VIEW IF EXISTS lcc.regular_costs_v;
DROP VIEW IF EXISTS lcc.regular_service_costs_v;
DROP VIEW IF EXISTS lcc.irregular_costs_v;

ALTER TABLE lcc.costs ADD ident varchar(100);

CREATE VIEW lcc.regular_costs_v AS
   SELECT c.*
        , r.*
     FROM lcc.costs c
     JOIN lcc.regular_costs r ON c.id = r.cost_id;

CREATE VIEW lcc.regular_service_costs_v AS
   SELECT c.*
        , r.*
     FROM lcc.costs c
     JOIN lcc.regular_service_costs r ON c.id = r.cost_id;

CREATE VIEW lcc.irregular_costs_v AS
   SELECT c.*
        , i.*
     FROM lcc.costs c
     JOIN lcc.irregular_costs i ON c.id = i.cost_id;

CREATE VIEW lcc.project_costs_v AS
       SELECT c.*
            , p.quantity
            , p.ref_value
            , p.project_variant_id
         FROM lcc.costs c
    LEFT JOIN lcc.project_costs p ON c.id = p.cost_id;

UPDATE lcc.costs
   SET ident = 'CREDIT_EEG'
 WHERE din276_code = 316
   AND grouping = 'ENERGY'
   AND label = 'Eigennutzung nach Ende der Einspeisevergütung (Energiemenge)';


DROP FUNCTION IF EXISTS lcc.compute_results(int);
CREATE OR REPLACE FUNCTION lcc.compute_results(in_project_variant_id int)
          RETURNS void
AS $$

DECLARE
        p_id int;
        p_life_time int;
BEGIN
        SELECT p.id, p.life_time INTO p_id, p_life_time
          FROM elca.projects p
          JOIN elca.project_variants v ON p.id = v.project_id
         WHERE v.id = in_project_variant_id;

        -- clear progressions and totals
        DELETE FROM lcc.project_cost_progressions
              WHERE project_variant_id = in_project_variant_id;
        DELETE FROM lcc.project_totals
              WHERE project_variant_id = in_project_variant_id;

        -- regular costs
        INSERT INTO lcc.project_cost_progressions (project_variant_id, grouping, life_time, quantity)
            SELECT p.project_variant_id
                 , p.grouping
                 , iteration AS life_time
                 , SUM( coalesce(p.quantity, 0)                  -- quantity per year
                      * COALESCE( c.ref_value, p.ref_value )     -- project specific refValue
                      * (1 + CASE WHEN p.grouping = 'WATER'    THEN v.water_price_inc
                                  WHEN p.grouping = 'ENERGY'   THEN v.energy_price_inc
                                  WHEN p.grouping = 'CLEANING' THEN v.cleaning_price_inc
                                  ELSE 0
                              END
                        ) ^ iteration
                      * CASE WHEN p.ident = 'CREDIT_EEG' AND iteration  > 20 THEN -1
                             WHEN p.ident = 'CREDIT_EEG' AND iteration <= 20 THEN 0
                             ELSE 1
                        END
                    ) AS total
              FROM lcc.versions v
              JOIN lcc.project_versions l ON v.id = l.version_id
              JOIN lcc.project_costs_v p ON p.version_id = v.id AND p.project_variant_id = l.project_variant_id
              JOIN lcc.regular_costs c ON p.id = c.cost_id
        CROSS JOIN generate_series(1, p_life_time) AS iteration   -- iterate over each year
             WHERE p.grouping IN ('WATER', 'ENERGY', 'CLEANING')
               AND l.project_variant_id = in_project_variant_id
          GROUP BY p.project_variant_id
                 , p.grouping
                 , iteration;

        -- regular service costs
        INSERT INTO lcc.project_cost_progressions (project_variant_id, grouping, life_time, quantity)
            SELECT p.project_variant_id
                 , p.grouping
                 , iteration AS life_time
                 , SUM( coalesce(p.quantity, 0)                               -- quantity per year
                      * (s.maintenance_perc + s.service_perc)
                      * (1 + v.common_price_inc) ^ iteration
                   ) AS total
              FROM lcc.versions v
              JOIN lcc.project_versions l ON v.id = l.version_id
              JOIN lcc.project_costs_v p ON (p.version_id = v.id OR project_id = p_id) AND p.project_variant_id = l.project_variant_id
              JOIN lcc.regular_service_costs s ON p.id = s.cost_id
        CROSS JOIN generate_series(1, p_life_time) AS iteration   -- iterate over each year
             WHERE p.grouping IN ('KGR300', 'KGR400', 'KGR500')
               AND l.project_variant_id = in_project_variant_id
               AND (s.maintenance_perc <> 0 OR s.service_perc <> 0)       --
          GROUP BY p.project_variant_id
                 , p.grouping
                 , iteration;

        -- irregular costs
        INSERT INTO lcc.project_cost_progressions (project_variant_id, grouping, life_time, quantity)
            SELECT p.project_variant_id
                 , p.grouping
                 , iteration AS life_time
                 , SUM( CASE WHEN p.grouping = 'KGU300' AND l.kgu300_alt > 0 THEN l.costs_300 * l.kgu300_alt / 100
                             WHEN p.grouping = 'KGU400' AND l.kgu400_alt > 0 THEN l.costs_400 * l.kgu400_alt / 100
                             WHEN p.grouping = 'KGU500' AND l.kgu500_alt > 0 THEN l.costs_500 * l.kgu500_alt / 100
                             ELSE coalesce(quantity, 0)
                        END
                      * (1 + v.common_price_inc) ^ iteration
                   ) AS total
              FROM lcc.versions v
              JOIN lcc.project_versions l ON v.id = l.version_id
              JOIN lcc.project_costs_v p ON (p.version_id = v.id OR project_id = p_id) AND p.project_variant_id = l.project_variant_id
              JOIN lcc.irregular_costs i ON p.id = i.cost_id
        CROSS JOIN generate_series(1, p_life_time) AS iteration   -- iterate over each year
             WHERE p.grouping IN ('KGU300', 'KGU400', 'KGU500')
               AND l.project_variant_id = in_project_variant_id
               AND iteration % i.life_time = 0 AND iteration <> p_life_time
          GROUP BY p.project_variant_id
                 , p.grouping
                 , iteration;

        -- sum up totals
        INSERT INTO lcc.project_totals (project_variant_id, grouping, costs)
                SELECT p.project_variant_id
                     , p.grouping
                     , sum(p.quantity / (1 + v.rate) ^ p.life_time) AS total
                  FROM lcc.project_cost_progressions p
                  JOIN lcc.project_versions  c ON p.project_variant_id = c.project_variant_id
                  JOIN lcc.versions          v ON v.id = c.version_id
                 WHERE p.project_variant_id = in_project_variant_id
              GROUP BY p.project_variant_id
                     , p.grouping;
END;
$$ LANGUAGE plpgsql;

COMMIT;
