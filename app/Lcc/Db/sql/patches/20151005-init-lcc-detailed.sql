BEGIN;
SELECT public.register_patch('add-lcc-detailed', 'lcc');

UPDATE lcc.costs SET ident = 'HEATING' WHERE label = 'Energie - Endenergiebedarf Heizwärme laut EnEV';
UPDATE lcc.costs SET ident = 'WATER' WHERE label = 'Energie - Endenergiebedarf Warmwasserbereitung laut EnEV';
UPDATE lcc.costs SET ident = 'VENTILATION' WHERE label = 'Energie - Endenergiebedarf Luftförderung laut EnEV';
UPDATE lcc.costs SET ident = 'COOLING' WHERE label = 'Energie - Endenergiebedarf Klimakälte laut EnEV';
UPDATE lcc.costs SET ident = 'LIGHTING' WHERE label = 'Energie - Endenergiebedarf Beleuchtung laut EnEV';

ALTER TABLE lcc.costs ADD  UNIQUE ("version_id", "ident");

DROP FUNCTION IF EXISTS lcc.compute_results(int);
DROP VIEW IF EXISTS lcc.project_costs_v;
DROP VIEW IF EXISTS lcc.project_costs_all_v;

ALTER TABLE lcc.project_versions ADD COLUMN "calc_method" smallint;
UPDATE lcc.project_versions SET calc_method = 0;
ALTER TABLE lcc.project_versions ALTER COLUMN "calc_method" SET NOT NULL;
ALTER TABLE lcc.project_versions DROP CONSTRAINT project_versions_pkey;
ALTER TABLE lcc.project_versions ADD PRIMARY KEY ("project_variant_id", "calc_method");

ALTER TABLE lcc.project_costs ADD COLUMN "calc_method" smallint;
UPDATE lcc.project_costs SET calc_method = 0;
ALTER TABLE lcc.project_costs ALTER COLUMN "calc_method" SET NOT NULL;
ALTER TABLE lcc.project_costs DROP CONSTRAINT project_costs_pkey;
ALTER TABLE lcc.project_costs ADD PRIMARY KEY ("project_variant_id", "calc_method", "cost_id");

ALTER TABLE lcc.project_totals ADD COLUMN "calc_method" smallint;
UPDATE lcc.project_totals SET calc_method = 0;
ALTER TABLE lcc.project_totals ALTER COLUMN "calc_method" SET NOT NULL;
ALTER TABLE lcc.project_totals DROP CONSTRAINT project_totals_pkey;
ALTER TABLE lcc.project_totals ADD PRIMARY KEY ("project_variant_id", "calc_method", "grouping");

ALTER TABLE lcc.project_cost_progressions ADD COLUMN "calc_method" smallint;
UPDATE lcc.project_cost_progressions SET calc_method = 0;
ALTER TABLE lcc.project_cost_progressions ALTER COLUMN "calc_method" SET NOT NULL;
ALTER TABLE lcc.project_cost_progressions DROP CONSTRAINT project_cost_progressions_pkey;
ALTER TABLE lcc.project_cost_progressions ADD PRIMARY KEY ("project_variant_id", "calc_method", "grouping", "life_time");


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

DROP FUNCTION IF EXISTS lcc.update_element_costs(int);
CREATE OR REPLACE FUNCTION lcc.update_element_costs(in_element_id int)
    RETURNS numeric
AS $$

DECLARE
    b_is_composite bool;
    n_quantity     numeric;
BEGIN

    SELECT
        is_composite
    INTO b_is_composite
    FROM
        elca.elements
    WHERE id = in_element_id;

    IF b_is_composite
    THEN
        SELECT
            sum(COALESCE(c.quantity, c.calculated_quantity) * e.quantity)
        INTO n_quantity
        FROM
            lcc.element_composite_costs_v c
            JOIN elca.elements e ON e.id = c.element_id
        WHERE c.composite_element_id = in_element_id;
    ELSE
        SELECT
            sum(c.quantity * (cc.num_replacements + 1))
        INTO n_quantity
        FROM
            lcc.element_component_costs_v c
            JOIN
            elca_cache.element_components cc
                ON c.element_component_id = cc.element_component_id
        WHERE c.element_id = in_element_id;
    END IF;

    UPDATE lcc.element_costs
    SET calculated_quantity = n_quantity
    WHERE element_id = in_element_id;

    IF NOT FOUND
    THEN
        INSERT INTO lcc.element_costs (element_id, quantity, calculated_quantity)
        VALUES (in_element_id, null, n_quantity);
    END IF;

    IF NOT b_is_composite
    THEN
        PERFORM lcc.update_element_costs(composite_element_id)
        FROM elca.composite_elements
        WHERE element_id = in_element_id;
    END IF;

    RETURN n_quantity;
END;
$$ LANGUAGE plpgsql;

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.project_element_detailed_costs(int);
DROP TYPE IF EXISTS lcc.project_element_detailed_costs;
CREATE TYPE lcc.project_element_detailed_costs AS (
    project_variant_id int,
    element_type_node_id int,
    element_id int,
    quantity numeric,
    life_time int,
    num_replacements int
);
CREATE OR REPLACE FUNCTION lcc.project_element_detailed_costs(in_project_variant_id int)
    RETURNS SETOF lcc.project_element_detailed_costs
AS $$
WITH ec_costs AS (
    SELECT
        e.project_variant_id,
        e.element_type_node_id,
        e.id AS element_id,
        cc.quantity * e.quantity AS quantity,
        c.life_time,
        CASE WHEN 50 % c.life_time = 0
            THEN (50 / c.life_time)::int - 1
        ELSE floor( 50  / c.life_time)::int
        END AS num_replacements
    FROM elca.elements e
        JOIN lcc.element_costs ec ON e.id = ec.element_id
        JOIN elca.element_components c ON e.id = c.element_id
        JOIN lcc.element_component_costs cc ON c.id = cc.element_component_id
    WHERE e.project_variant_id = $1
          AND ec.quantity IS NULL
          AND cc.quantity IS NOT NULL
          AND cc.quantity > 0
), e_costs AS (
    SELECT
        e.project_variant_id,
        e.element_type_node_id,
        e.id AS element_id,
        ec.quantity * e.quantity AS quantity,
        ec.life_time,
        CASE WHEN 50 % ec.life_time = 0
            THEN (50 / ec.life_time)::int - 1
        ELSE floor( 50  / ec.life_time)::int
        END AS num_replacements
    FROM elca.elements e
        JOIN lcc.element_costs ec ON e.id = ec.element_id
    WHERE e.project_variant_id = $1
          AND ec.quantity IS NOT NULL
)
SELECT
    project_variant_id,
    element_type_node_id,
    element_id,
    quantity,
    life_time,
    num_replacements
FROM ec_costs
UNION ALL
SELECT
    project_variant_id,
    element_type_node_id,
    element_id,
    quantity,
    life_time,
    num_replacements
FROM e_costs
$$ LANGUAGE SQL;

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.update_detailed_regular_project_costs(int);
CREATE OR REPLACE FUNCTION lcc.update_detailed_regular_project_costs(in_project_variant_id int)
    RETURNS void
AS $$

DECLARE
    p_calc_method constant int := 1;
    p_life_time          int;
    p_version_id         int;
    groupings            text ARRAY;

BEGIN
    groupings := ARRAY ['KGR300', 'KGR400', 'KGR500'];

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

    INSERT INTO lcc.project_costs (project_variant_id, cost_id, calc_method, quantity)
        SELECT
            c.project_variant_id,
            pc.cost_id,
            p_calc_method,
            sum( coalesce(c.quantity, 0) * (1 + c.num_replacements) ) AS quantity
            FROM lcc.project_element_detailed_costs(in_project_variant_id) c
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
                 ) pc ON pc.element_type_node_id = c.element_type_node_id
            GROUP BY
                c.project_variant_id,
                pc.cost_id;
END;
$$ LANGUAGE plpgsql;

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.clean_project_results(int, smallint);
CREATE OR REPLACE FUNCTION lcc.clean_project_results(in_project_variant_id int, in_calc_method smallint)
    RETURNS void
AS $$

    DELETE FROM lcc.project_cost_progressions
        WHERE (project_variant_id, calc_method) = ($1, $2);
    DELETE FROM lcc.project_totals
        WHERE (project_variant_id, calc_method) = ($1, $2);

$$ LANGUAGE SQL;

-------------------------------------------------------------------------------



DROP FUNCTION IF EXISTS lcc.compute_regular_costs(int, int, int, smallint);
CREATE OR REPLACE FUNCTION lcc.compute_regular_costs(in_project_variant_id int, in_project_id int, in_life_time int, in_calc_method smallint)
    RETURNS void
AS $$

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
                    ) ^ iteration
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

DROP FUNCTION IF EXISTS lcc.compute_project_totals(intm smallint);
CREATE OR REPLACE FUNCTION lcc.compute_project_totals(in_project_variant_id int, in_calc_method smallint)
    RETURNS void
AS $$

    -- sum up totals
    INSERT INTO lcc.project_totals (project_variant_id, calc_method, grouping, costs)
        SELECT
            p.project_variant_id
            , p.calc_method
            , p.grouping
            , sum(p.quantity / (1 + v.rate) ^ p.life_time) AS total
        FROM lcc.project_cost_progressions p
            JOIN lcc.project_versions c ON (p.project_variant_id, p.calc_method) = (c.project_variant_id, c.calc_method)
            JOIN lcc.versions v ON v.id = c.version_id
        WHERE (p.project_variant_id, p.calc_method) = ($1, $2)
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

    -- clear progressions and totals
    PERFORM lcc.clean_project_results(in_project_variant_id, p_calc_method);

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
                      * (1 + v.common_price_inc) ^ iteration
                  )         AS total
            FROM lcc.versions v
                JOIN lcc.project_versions l ON v.id = l.version_id
                CROSS JOIN generate_series(1, p_life_time) AS iteration -- iterate over each year
            WHERE (l.project_variant_id, l.calc_method) = (in_project_variant_id, p_calc_method)
                  AND iteration <> p_life_time
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
            , SUM(coalesce(quantity, 0) * (1 + v.common_price_inc) ^ iteration) AS total
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
              AND iteration % i.life_time = 0 AND iteration <> p_life_time
        GROUP BY p.project_variant_id
            , p.calc_method
            , p.grouping
            , iteration;

    -- project totals
    PERFORM lcc.compute_project_totals(in_project_variant_id, p_calc_method);
END;
$$ LANGUAGE plpgsql;

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.compute_detailed_results(int);
CREATE OR REPLACE FUNCTION lcc.compute_detailed_results(in_project_variant_id int)
    RETURNS void
AS $$

DECLARE

    p_calc_method constant smallint := 1;
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

    -- clear progressions and totals
    PERFORM lcc.clean_project_results(in_project_variant_id, p_calc_method);

    -- regular costs
    PERFORM lcc.update_detailed_regular_project_costs(in_project_variant_id);
    PERFORM lcc.compute_regular_costs(in_project_variant_id, p_id, p_life_time, p_calc_method);

    -- irregular costs
    INSERT INTO lcc.project_cost_progressions (project_variant_id, grouping, life_time, quantity, calc_method)
        SELECT
            c.project_variant_id,
            'KGU' || (t.din_code / 100)::int * 100,
            iteration,
            sum(coalesce(c.quantity, 0) * (1 + c.num_replacements) * (1 + v.common_price_inc) ^ iteration),
            p_calc_method
        FROM
            lcc.versions v
            JOIN lcc.project_versions pv ON v.id = pv.version_id
            JOIN lcc.project_element_detailed_costs(in_project_variant_id) c ON c.project_variant_id = pv.project_variant_id
            JOIN elca.element_types t ON t.node_id = c.element_type_node_id
            CROSS JOIN generate_series(1, 50) AS iteration
        WHERE
            iteration % c.life_time = 0 AND
            iteration <> 50
        GROUP BY
            c.project_variant_id,
            (t.din_code / 100)::int * 100,
            iteration;

    -- sum up totals
    PERFORM lcc.compute_project_totals(in_project_variant_id, p_calc_method);

END;
$$ LANGUAGE plpgsql;

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.compute_results(int);
CREATE OR REPLACE FUNCTION lcc.compute_results(in_project_variant_id int)
    RETURNS void
AS $$

    SELECT lcc.compute_general_results($1);
    SELECT lcc.compute_detailed_results($1);
$$ LANGUAGE SQL;

-------------------------------------------------------------------------------

DROP FUNCTION IF EXISTS lcc.compute_results(int, smallint);
CREATE OR REPLACE FUNCTION lcc.compute_results(in_project_variant_id int, in_calc_method smallint)
    RETURNS void
AS $$
BEGIN
    IF in_calc_method = 0 THEN
        PERFORM lcc.compute_general_results(in_project_variant_id);
    ELSE
        PERFORM lcc.compute_detailed_results(in_project_variant_id);
    END IF;
END;
$$ LANGUAGE plpgsql;

-------------------------------------------------------------------------------
COMMIT;