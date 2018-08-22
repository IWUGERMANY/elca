BEGIN;
SELECT
    public.register_patch('20180216-fix-lcc-irregular-cost-calculation.sql', 'eLCA');

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


COMMIT;