BEGIN;
SELECT
    public.register_patch('20180214-fix-lcc-calculation.sql', 'LCC');

DROP VIEW IF EXISTS lcc.element_cost_totals_v;
CREATE OR REPLACE VIEW lcc.element_cost_totals_v AS
    WITH calculated AS (
        SELECT
            element_id,
            sum(p.quantity) AS sum
        FROM
            lcc.element_component_cost_progressions_v p
        WHERE p.quantity IS NOT NULL
              AND p.life_time > 0
        GROUP BY element_id
    ),
            edited AS (
            SELECT
                element_id,
                sum(p.quantity) AS sum
            FROM
                lcc.element_cost_progressions p
            WHERE p.life_time > 0
            GROUP BY element_id
        ),
            elements AS (
            SELECT id AS element_id
                , project_variant_id
            FROM elca.elements
        )
    SELECT
        e.element_id,
        e.project_variant_id,
        coalesce(ce.sum, c.sum) AS quantity
    FROM elements e
        LEFT JOIN (calculated c
            FULL OUTER JOIN edited ce USING (element_id)
            ) ON e.element_id IN (c.element_id, ce.element_id);



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
                CROSS JOIN generate_series(1, p_life_time) AS iteration -- iterate over each year
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
            CROSS JOIN generate_series(1, p_life_time) AS iteration -- iterate over each year
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


DROP FUNCTION IF EXISTS lcc.update_element_component_cost_progressions(int);
DROP FUNCTION IF EXISTS lcc.update_element_cost_progressions(int);

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
        CROSS JOIN generate_series(0, $2) AS iteration
    WHERE e.project_variant_id = $1
          AND iteration % c.life_time = 0;

$$ LANGUAGE SQL;

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
                CROSS JOIN generate_series(0, $2) AS iteration
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

COMMIT;