BEGIN;
SELECT public.register_patch('reinit-lcc-detailed', 'lcc');

CREATE TABLE lcc.element_cost_progressions
(
      "element_id"                 int     NOT NULL            -- elementId
    , "life_time"                  int     NOT NULL            -- lifeTime
    , "quantity"                   numeric NOT NULL            -- quantity
    , PRIMARY KEY ("element_id", "life_time")
    , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON DELETE CASCADE
);
CREATE TABLE lcc.element_component_cost_progressions
(
      "element_component_id"       int     NOT NULL            -- elementComponentId
    , "life_time"                  int     NOT NULL            -- lifeTime
    , "quantity"                   numeric NOT NULL            -- quantity
    , PRIMARY KEY ("element_component_id", "life_time")
    , FOREIGN KEY ("element_component_id") REFERENCES elca.element_components ("id") ON DELETE CASCADE
);



DROP VIEW IF EXISTS lcc.regular_costs_v;
DROP VIEW IF EXISTS lcc.regular_service_costs_v;
DROP VIEW IF EXISTS lcc.irregular_costs_v;
DROP VIEW IF EXISTS lcc.project_costs_v;
DROP VIEW IF EXISTS lcc.project_costs_all_v;
DROP VIEW IF EXISTS lcc.element_costs_v;
DROP VIEW IF EXISTS lcc.element_composite_costs_v;
DROP VIEW IF EXISTS lcc.element_component_costs_v;
DROP VIEW IF EXISTS lcc.element_cost_totals_v;
DROP VIEW IF EXISTS lcc.element_component_cost_progressions_v;

DROP FUNCTION IF EXISTS lcc.clean_project_results(int, smallint);
DROP FUNCTION IF EXISTS lcc.compute_regular_costs(int, int, int, smallint);
DROP FUNCTION IF EXISTS lcc.compute_results(int);
DROP FUNCTION IF EXISTS lcc.compute_results(int, smallint);
DROP FUNCTION IF EXISTS lcc.project_element_detailed_costs(int);
DROP FUNCTION IF EXISTS lcc.update_detailed_regular_project_costs(int);
DROP FUNCTION IF EXISTS lcc.update_element_costs(int);

DROP FUNCTION IF EXISTS lcc.compute_regular_costs(int, int, int, smallint);
DROP FUNCTION IF EXISTS lcc.compute_project_totals(int, smallint);
DROP FUNCTION IF EXISTS lcc.compute_general_results(int);
DROP FUNCTION IF EXISTS lcc.update_element_component_cost_progressions(int);
DROP FUNCTION IF EXISTS lcc.update_element_cost_progressions(int);
DROP FUNCTION IF EXISTS lcc.update_element_total_costs(int);
DROP FUNCTION IF EXISTS lcc.compute_irregular_element_costs(int);
DROP FUNCTION IF EXISTS lcc.compute_detailed_results(int);

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
        , p.calc_method
    FROM lcc.costs c
        LEFT JOIN lcc.project_costs p ON c.id = p.cost_id;

CREATE VIEW lcc.project_costs_all_v AS
    SELECT c.*
        , CASE WHEN p.ref_value IS NOT NULL THEN p.ref_value ELSE r.ref_value END AS ref_value
        , r.ref_unit
        , s.maintenance_perc
        , s.service_perc
        , i.life_time
        , p.quantity
        , p.project_variant_id
        , p.calc_method
    FROM lcc.costs c
        LEFT JOIN lcc.regular_costs r ON c.id = r.cost_id
        LEFT JOIN lcc.regular_service_costs s ON c.id = s.cost_id
        LEFT JOIN lcc.irregular_costs i ON c.id = i.cost_id
        LEFT JOIN lcc.project_costs p ON c.id = p.cost_id;

CREATE VIEW lcc.element_costs_v AS
    SELECT c.*
        , e.project_variant_id
        , e.element_type_node_id
        , e.quantity AS element_quantity
        , e.ref_unit
        , e.is_composite
    FROM lcc.element_costs c
        JOIN elca.elements e ON e.id = c.element_id;

CREATE VIEW lcc.element_composite_costs_v AS
    SELECT c.*
        , e.composite_element_id
    FROM lcc.element_costs c
        JOIN elca.composite_elements e ON e.element_id = c.element_id;


CREATE VIEW lcc.element_component_costs_v AS
    SELECT c.*
        , e.element_id
        , e.quantity AS component_quantity
        , e.process_config_id
        , e.is_layer
        , e.layer_position
        , e.layer_sibling_id
        , e.is_extant
        , e.life_time
        , e.life_time_delay
        , cc.num_replacements
    FROM lcc.element_component_costs c
        JOIN elca.element_components e ON e.id = c.element_component_id
        LEFT JOIN elca_cache.element_components cc ON c.element_component_id = cc.element_component_id;

CREATE VIEW lcc.element_component_cost_progressions_v AS
    SELECT c.id AS element_component_id
        , c.element_id
        , pc.name AS process_config_name
        , c.is_layer
        , c.layer_position
        , p.life_time
        , p.quantity
    FROM elca.element_components c
        JOIN elca.process_configs pc ON pc.id = c.process_config_id
        LEFT JOIN lcc.element_component_cost_progressions p ON c.id = p.element_component_id;

CREATE OR REPLACE VIEW lcc.element_cost_totals_v AS
    WITH calculated AS (
        SELECT
            element_id,
            sum(p.quantity) AS sum
        FROM
            lcc.element_component_cost_progressions_v p
        WHERE p.quantity IS NOT NULL
        GROUP BY element_id
    ),
            edited AS (
            SELECT
                element_id,
                sum(p.quantity) AS sum
            FROM
                lcc.element_cost_progressions p
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
        CROSS JOIN generate_series(0, $3 - 1) AS iteration -- iterate over each year
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
        CROSS JOIN generate_series(0, $3 - 1) AS iteration -- iterate over each year
    WHERE p.grouping IN ('KGR300', 'KGR400', 'KGR500')
          AND (l.project_variant_id, l.calc_method) = ($1, $4)
          AND (s.maintenance_perc <> 0 OR s.service_perc <> 0) --
    GROUP BY p.project_variant_id
        , p.calc_method
        , p.grouping
        , iteration;

$$ LANGUAGE SQL;

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
    GROUP BY p.project_variant_id
        , p.calc_method
        , p.grouping;

$$ LANGUAGE SQL;


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
                CROSS JOIN generate_series(0, p_life_time - 1) AS iteration -- iterate over each year
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
            CROSS JOIN generate_series(0, p_life_time - 1) AS iteration -- iterate over each year
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



CREATE OR REPLACE FUNCTION lcc.update_element_component_cost_progressions(in_project_variant_id int)
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
        coalesce(c.quantity, 0) * (1 + c.num_replacements) * (1 + v.common_price_inc) ^ iteration / (1 + v.rate) ^ iteration
    FROM
        lcc.element_component_costs_v c
        JOIN elca.elements e ON e.id = c.element_id
        JOIN lcc.project_versions pv  ON pv.project_variant_id = e.project_variant_id AND pv.calc_method = 1
        JOIN lcc.versions v ON v.id = pv.version_id
        CROSS JOIN generate_series(0, 49) AS iteration
    WHERE e.project_variant_id = $1
          AND iteration % c.life_time = 0;

$$ LANGUAGE SQL;

CREATE OR REPLACE FUNCTION lcc.update_element_cost_progressions(in_project_variant_id int)
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
            CROSS JOIN generate_series(0,49) AS iteration
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
                     JOIN lcc.project_versions l ON v.id = l.version_id
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
            , cost_id
            , sum(ec.element_quantity * ecp.quantity)
            , p_calc_method
        FROM lcc.element_cost_progressions ecp
            JOIN lcc.element_costs_v ec ON ec.element_id = ecp.element_id
            JOIN (SELECT
                      t.id AS element_type_node_id,
                      pc.id AS cost_id,
                      pc.din276_code
                  FROM
                      elca.element_types_v t
                      JOIN lcc.project_costs_v pc ON
                                                      (CASE WHEN
                                                          pc.din276_code % 10 = 0 AND t.din_code <> 327
                                                          THEN (t.din_code / 10) :: int * 10 = pc.din276_code
                                                       ELSE t.din_code = pc.din276_code
                                                       END)
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


CREATE OR REPLACE FUNCTION lcc.compute_detailed_results(in_project_variant_id int)
    RETURNS void
AS $$

DECLARE
    p_calc_method constant smallint := 1;
    p_project_id int;

BEGIN
    SELECT project_id INTO p_project_id
    FROM elca.project_variants WHERE id = in_project_variant_id;

    -- construction cost progressions
    PERFORM lcc.update_element_component_cost_progressions(in_project_variant_id);
    PERFORM lcc.update_element_cost_progressions(in_project_variant_id);
    PERFORM lcc.update_element_total_costs(in_project_variant_id);

    -- construction costs
    PERFORM lcc.update_element_construction_costs(in_project_variant_id);

    -- regular costs
    PERFORM lcc.compute_regular_costs(in_project_variant_id, p_project_id, 50, p_calc_method);

    -- irregular costs based on construction
    PERFORM lcc.compute_irregular_element_costs(in_project_variant_id);

    -- project totals
    PERFORM lcc.compute_project_totals(in_project_variant_id, p_calc_method);
END;
$$ LANGUAGE plpgsql;

COMMIT;