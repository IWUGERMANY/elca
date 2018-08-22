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

DROP FUNCTION IF EXISTS lcc.update_element_component_cost_progressions(int, int);
CREATE OR REPLACE FUNCTION lcc.update_element_component_cost_progressions(in_project_variant_id int, in_life_time int)
    RETURNS void
AS $$

DELETE FROM lcc.element_component_cost_progressions
    WHERE element_component_id IN (
        SELECT c.id
        FROM elca.element_components c
            JOIN elca.elements e ON e.id = c.element_id
        WHERE project_variant_id = $1
        );

INSERT INTO lcc.element_component_cost_progressions (element_component_id, life_time, quantity)
    SELECT
        c.element_component_id,
        iteration AS life_time,
        coalesce(c.quantity, 0) * (1 + v.common_price_inc) ^ iteration / (1 + v.rate) ^ iteration
    FROM
        lcc.element_component_costs_v c
        JOIN elca.elements e ON e.id = c.element_id
        JOIN lcc.project_versions pv  ON pv.project_variant_id = e.project_variant_id AND pv.calc_method = 1
        JOIN lcc.versions v ON v.id = pv.version_id
        CROSS JOIN generate_series(0, $2 - 1) AS iteration
    WHERE e.project_variant_id = $1
          AND iteration % c.life_time = 0;

$$ LANGUAGE SQL;


DROP FUNCTION IF EXISTS lcc.update_element_cost_progressions(int, int);
CREATE OR REPLACE FUNCTION lcc.update_element_cost_progressions(in_project_variant_id int, in_life_time int)
    RETURNS void
AS $$

DELETE FROM lcc.element_cost_progressions
    WHERE element_id IN (
        SELECT e.id
        FROM elca.elements e
        WHERE e.project_variant_id = $1
        );

INSERT INTO lcc.element_cost_progressions (element_id, life_time, quantity)
    WITH calculated AS (
        SELECT e.element_id
            , ecp.life_time
            , sum(ecp.quantity) AS quantity
        FROM lcc.element_costs_v e
            JOIN lcc.element_component_costs_v ec ON e.element_id = ec.element_id
            JOIN lcc.element_component_cost_progressions ecp ON ecp.element_component_id = ec.element_component_id
        WHERE e.project_variant_id = $1
          AND e.quantity IS NULL AND e.is_composite = false
        GROUP BY e.element_id
            , ecp.life_time
    ),
    edited AS (
            SELECT e.element_id
                , iteration AS life_time
                , e.quantity * (1 + v.common_price_inc) ^ iteration / (1 + v.rate) ^ iteration AS quantity
            FROM lcc.element_costs_v e
                JOIN lcc.project_versions pv  ON pv.project_variant_id = e.project_variant_id AND pv.calc_method = 1
                JOIN lcc.versions v ON v.id = pv.version_id
                CROSS JOIN generate_series(0, $2 - 1) AS iteration
            WHERE e.project_variant_id = $1
                  AND  e.quantity IS NOT NULL
                  AND iteration % e.life_time = 0
        )
    (SELECT element_id
         , life_time
        , quantity
        FROM calculated
    UNION
    SELECT element_id
        , life_time
        , quantity
        FROM edited);

$$ LANGUAGE SQL;


DROP FUNCTION IF EXISTS lcc.update_element_total_costs(int);
CREATE OR REPLACE FUNCTION lcc.update_element_total_costs(in_project_variant_id int)
    RETURNS void
AS $$

-- update non composite element totals
UPDATE lcc.element_costs c
   SET calculated_quantity = x.quantity
  FROM lcc.element_cost_totals_v x
 WHERE x.element_id = c.element_id
   AND x.project_variant_id = $1;

$$ LANGUAGE SQL;

DROP FUNCTION IF EXISTS lcc.update_element_construction_costs(int);
CREATE OR REPLACE FUNCTION lcc.update_element_construction_costs(in_project_variant_id int)
    RETURNS void
AS $$

DECLARE
    p_calc_method constant int := 1;
    groupings            text ARRAY;
    p_life_time          int;
    p_version_id         int;

BEGIN
    groupings := ARRAY ['KGR300', 'KGR400', 'KGR500', 'KGU300', 'KGU400', 'KGU500'];

    SELECT
        p.life_time,
        pv.version_id
    INTO
        p_life_time,
        p_version_id
    FROM
        elca.projects p
        JOIN
        elca.project_variants v ON p.id = v.project_id
        JOIN (
                     lcc.versions v
                     JOIN lcc.project_versions l ON v.id = l.version_id AND v.calc_method = p_calc_method
             ) pv ON pv.project_variant_id = v.id
    WHERE v.id = in_project_variant_id;

    DELETE FROM lcc.project_costs
    WHERE
        (project_variant_id, calc_method) = (in_project_variant_id, p_calc_method)
        AND cost_id IN (SELECT
                            id
                        FROM lcc.costs
                        WHERE version_id = p_version_id
                              AND grouping = ANY (groupings)
        );

    INSERT INTO lcc.project_costs (project_variant_id, cost_id, quantity, calc_method)
        SELECT ec.project_variant_id
             , pc.cost_id
             , sum(ec.element_quantity * ecp.quantity)
             , p_calc_method
          FROM lcc.element_cost_progressions ecp
          JOIN lcc.element_costs_v ec ON ec.element_id = ecp.element_id
          JOIN (SELECT
                      t.id AS element_type_node_id,
                      c.id AS cost_id,
                      c.din276_code
                FROM
                      elca.element_types_v t
                 JOIN lcc.costs c ON t.din_code = c.din276_code
                                                AND grouping = ANY (groupings)
                                             AND version_id = p_version_id
                WHERE t.level = 3
               ) pc ON pc.element_type_node_id = ec.element_type_node_id
          WHERE ec.project_variant_id = in_project_variant_id
            AND ecp.life_time = 0
        GROUP BY
              ec.project_variant_id,
              pc.cost_id;

END;
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS lcc.compute_irregular_element_costs(int);
CREATE OR REPLACE FUNCTION lcc.compute_irregular_element_costs(in_project_variant_id int)
    RETURNS void
AS $$

DECLARE
    p_calc_method constant int := 1;
    groupings            text ARRAY;

BEGIN
    groupings := ARRAY ['KGU300', 'KGU400', 'KGU500'];

    DELETE FROM lcc.project_cost_progressions
    WHERE
        (project_variant_id, calc_method) = (in_project_variant_id, p_calc_method)
        AND grouping = ANY (groupings);

    INSERT INTO lcc.project_cost_progressions (project_variant_id, grouping, life_time, quantity, calc_method)
        SELECT
            ec.project_variant_id,
            'KGU' || (t.din_code / 100)::int * 100,
            ecp.life_time,
            sum(coalesce(ecp.quantity, 0) * ec.element_quantity),
            p_calc_method
        FROM
            lcc.element_cost_progressions ecp
        JOIN lcc.element_costs_v ec ON ec.element_id = ecp.element_id
        JOIN elca.element_types t ON t.node_id = ec.element_type_node_id
        WHERE ec.project_variant_id = in_project_variant_id
        GROUP BY
            ec.project_variant_id,
            (t.din_code / 100)::int * 100,
            ecp.life_time;
END;
$$ LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS lcc.compute_detailed_results(int);
CREATE OR REPLACE FUNCTION lcc.compute_detailed_results(in_project_variant_id int)
    RETURNS void
AS $$

DECLARE
    p_calc_method constant smallint := 1;
    p_life_time          int;
    p_project_id int;

BEGIN
    SELECT
        p.id
        , p.life_time
    INTO p_project_id, p_life_time
    FROM elca.projects p
        JOIN elca.project_variants v ON p.id = v.project_id
    WHERE v.id = in_project_variant_id;

    -- construction cost progressions
    PERFORM lcc.update_element_component_cost_progressions(in_project_variant_id, p_life_time);
    PERFORM lcc.update_element_cost_progressions(in_project_variant_id, p_life_time);
    PERFORM lcc.update_element_total_costs(in_project_variant_id);

    -- construction costs
    PERFORM lcc.update_element_construction_costs(in_project_variant_id);

    -- regular costs
    PERFORM lcc.compute_regular_costs(in_project_variant_id, p_project_id, p_life_time, p_calc_method);

    -- irregular costs based on construction
    PERFORM lcc.compute_irregular_element_costs(in_project_variant_id);

    -- project totals
    PERFORM lcc.compute_project_totals(in_project_variant_id, p_calc_method);
END;
$$ LANGUAGE plpgsql;


-------------------------------------------------------------------------------
COMMIT;