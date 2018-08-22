----------------------------------------------------------------------------------------
-- This file is part of the eLCA project
--
-- eLCA
-- A web based life cycle assessment application
--
-- Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
--               BEIBOB Medienfreunde GbR - http://beibob.de/
--
-- eLCA is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- eLCA is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with eLCA. If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------------------------
SET search_path = public;

BEGIN;
-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.compute_regular_costs(int, int, int, smallint);
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
                  * COALESCE(c.ref_value, p.ref_value)     -- project specific refValue
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

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.compute_project_totals(int, smallint);
CREATE OR REPLACE FUNCTION lcc.compute_project_totals(in_project_variant_id int, in_calc_method smallint)
    RETURNS void
AS $$

    DELETE FROM lcc.project_totals
        WHERE (project_variant_id, calc_method) = ($1, $2);

    -- sum up totals
    INSERT INTO lcc.project_totals (project_variant_id, calc_method, grouping, costs)
        SELECT
            p.project_variant_id
            , p.calc_method
            , p.grouping
            , sum(p.quantity) AS total
        FROM lcc.project_cost_progressions p
            JOIN lcc.project_versions c ON (p.project_variant_id, p.calc_method) = (c.project_variant_id, c.calc_method)
            JOIN lcc.versions v ON v.id = c.version_id
        WHERE (p.project_variant_id, p.calc_method) = ($1, $2)
            AND p.life_time > 0
        GROUP BY p.project_variant_id
            , p.calc_method
            , p.grouping;

$$ LANGUAGE SQL;

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.compute_general_results(int);
CREATE OR REPLACE FUNCTION lcc.compute_general_results(in_project_variant_id int)
    RETURNS void
AS $$

DECLARE
    p_calc_method constant smallint := 0;
    p_id                 int;
    p_life_time          int;
    groupings            text ARRAY;

BEGIN
    SELECT
        p.id
        , p.life_time
    INTO p_id, p_life_time
    FROM elca.projects p
        JOIN elca.project_variants v ON p.id = v.project_id
    WHERE v.id = in_project_variant_id;

    -- regular service costs
    PERFORM lcc.compute_regular_costs(in_project_variant_id, p_id, p_life_time, p_calc_method);

    -- irregular costs
    groupings := ARRAY ['KGU400', 'KGU500'];

    PERFORM *
    FROM lcc.project_versions
    WHERE project_variant_id = in_project_variant_id AND kgu300_alt > 0;
    IF FOUND
    THEN
        INSERT INTO lcc.project_cost_progressions (project_variant_id, calc_method, grouping, life_time, quantity)
            SELECT
                l.project_variant_id
                , l.calc_method
                , 'KGU300'  AS grouping
                , iteration AS life_time
                , SUM(l.costs_300 * l.kgu300_alt / 100
                      * (1 + v.common_price_inc) ^ iteration / (1 + v.rate) ^ iteration
                  )         AS total
            FROM lcc.versions v
                JOIN lcc.project_versions l ON v.id = l.version_id
                CROSS JOIN generate_series(1, p_life_time - 1) AS iteration -- iterate over each year
            WHERE (l.project_variant_id, l.calc_method) = (in_project_variant_id, p_calc_method)
            GROUP BY l.project_variant_id
                , l.calc_method
                , iteration;
    ELSE
        groupings := groupings || ARRAY ['KGU300'];
    END IF;

    INSERT INTO lcc.project_cost_progressions (project_variant_id, calc_method, grouping, life_time, quantity)
        SELECT
            p.project_variant_id
            , p.calc_method
            , p.grouping
            , iteration                                                         AS life_time
            , SUM(coalesce(quantity, 0) * (1 + v.common_price_inc) ^ iteration / (1 + v.rate) ^ iteration) AS total
        FROM lcc.versions v
            JOIN lcc.project_versions l ON v.id = l.version_id
            JOIN lcc.project_costs_v p ON (p.version_id = v.id OR project_id = p_id)
                                          AND
                                          (p.project_variant_id, p.calc_method) = (l.project_variant_id, l.calc_method)
            JOIN lcc.irregular_costs i ON p.id = i.cost_id
            CROSS JOIN generate_series(1, p_life_time - 1) AS iteration -- iterate over each year
        WHERE p.grouping = ANY (groupings)
              AND p.quantity IS NOT NULL
              AND (l.project_variant_id, l.calc_method) = (in_project_variant_id, p_calc_method)
              AND iteration % i.life_time = 0
        GROUP BY p.project_variant_id
            , p.calc_method
            , p.grouping
            , iteration;

    -- project totals
    PERFORM lcc.compute_project_totals(in_project_variant_id, p_calc_method);
END;
$$ LANGUAGE plpgsql;

-------------------------------------------------------------------------------
COMMIT;
