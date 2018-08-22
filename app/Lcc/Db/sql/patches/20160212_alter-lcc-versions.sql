BEGIN;
SELECT public.register_patch('alter-lcc-versions', 'lcc');

ALTER TABLE lcc.versions ADD "calc_method" smallint;
UPDATE lcc.versions SET calc_method = 0;
ALTER TABLE lcc.versions ALTER "calc_method" SET NOT NULL;

INSERT INTO lcc.versions (id, name, version, rate, common_price_inc, energy_price_inc, water_price_inc, cleaning_price_inc, created, modified, calc_method)
    VALUES (DEFAULT, 'Preisstand 2015', null, 1.5/100, 2.0/100, 5.0/100, 2.0/100, 2.0/100, DEFAULT, null, 1);

INSERT INTO lcc.costs (version_id, grouping, din276_code, label, headline, project_id, ident)
    SELECT
        (SELECT id FROM lcc.versions WHERE calc_method = 1 AND name = 'Preisstand 2015'),
        c.grouping,
        c.din276_code,
        c.label,
        c.headline,
        null,
        c.ident
    FROM lcc.versions v
    JOIN lcc.costs c ON v.id = c.version_id
   WHERE v.id = (SELECT id FROM lcc.versions WHERE calc_method = 0 ORDER BY id DESC LIMIT 1)
     AND c.grouping IN ('ENERGY', 'CLEANING', 'WATER');

INSERT INTO lcc.regular_costs (cost_id, ref_value, ref_unit)
    SELECT  c.id AS cost_id,
        r.ref_value,
        r.ref_unit
    FROM lcc.costs c
        JOIN lcc.costs cc ON (c.grouping, c.din276_code, c.label) = (cc.grouping, cc.din276_code, cc.label)
        JOIN lcc.regular_costs r ON r.cost_id = cc.id
    WHERE cc.grouping IN ('ENERGY', 'CLEANING', 'WATER')
          AND cc.version_id = (SELECT id FROM lcc.versions WHERE calc_method = 0 ORDER BY id DESC LIMIT 1)
          AND c.version_id = (SELECT id FROM lcc.versions WHERE calc_method = 1 ORDER BY id DESC LIMIT 1);

INSERT INTO lcc.costs (version_id, grouping, din276_code, label, headline, project_id, ident)
    SELECT
        (SELECT id FROM lcc.versions WHERE name = 'Preisstand 2015') AS version_id,
        'KGR' || (t.din_code / 100)::int * 100 AS grouping,
        t.din_code AS din276_code,
        t.name AS label,
        null AS headline,
        null AS project_id,
        null AS ident
    FROM elca.element_types_v t
   WHERE t.level = 3
  ORDER BY t.lft;

INSERT INTO lcc.regular_service_costs (cost_id, service_perc, maintenance_perc)
    SELECT c.id AS cost_id,
        (CASE WHEN c.din276_code BETWEEN 300 AND 399 THEN 0.1
                WHEN c.din276_code BETWEEN 410 AND 419 THEN 1.01
                WHEN c.din276_code BETWEEN 420 AND 429 THEN 0.41
                WHEN c.din276_code BETWEEN 430 AND 439 THEN 0.96
                WHEN c.din276_code BETWEEN 440 AND 449 THEN 0.6
                WHEN c.din276_code BETWEEN 450 AND 459 THEN 1.04
                WHEN c.din276_code BETWEEN 460 AND 469 THEN 1.76
                WHEN c.din276_code BETWEEN 480 AND 489 THEN 1.16
                WHEN c.din276_code BETWEEN 534 AND 538 THEN 0.1
                WHEN c.din276_code BETWEEN 541 AND 543 THEN 0.93
                WHEN c.din276_code BETWEEN 544 AND 545 THEN 0.88
                WHEN c.din276_code BETWEEN 546 AND 547 THEN 0.43
                WHEN c.din276_code IN (551, 576)       THEN 0.1
                ELSE null
           END) / 100 AS service_perc,

        (CASE WHEN c.din276_code BETWEEN 300 AND 399 THEN 0.35
                WHEN c.din276_code BETWEEN 410 AND 419 THEN 0.98
                WHEN c.din276_code BETWEEN 420 AND 429 THEN 0.66
                WHEN c.din276_code BETWEEN 430 AND 439 THEN 1.1
                WHEN c.din276_code BETWEEN 440 AND 449 THEN 0.7
                WHEN c.din276_code BETWEEN 450 AND 459 THEN 1.04
                WHEN c.din276_code BETWEEN 460 AND 469 THEN 1.78
                WHEN c.din276_code BETWEEN 480 AND 489 THEN 0.76
                WHEN c.din276_code BETWEEN 534 AND 538 THEN 0.35
                WHEN c.din276_code BETWEEN 541 AND 543 THEN 1.07
                WHEN c.din276_code BETWEEN 544 AND 545 THEN 0.8
                WHEN c.din276_code BETWEEN 546 AND 547 THEN 1.07
                WHEN c.din276_code IN (551, 576)       THEN 0.35
                ELSE null
           END ) / 100 AS maintenance_perc

    FROM lcc.costs c
    WHERE c.grouping ILIKE 'KGR%'
      AND c.version_id = (SELECT id FROM lcc.versions WHERE calc_method = 1 ORDER BY id DESC LIMIT 1)
      AND CASE WHEN c.din276_code BETWEEN 300 AND 399 THEN true
               WHEN c.din276_code BETWEEN 410 AND 419 THEN true
               WHEN c.din276_code BETWEEN 420 AND 429 THEN true
               WHEN c.din276_code BETWEEN 430 AND 439 THEN true
               WHEN c.din276_code BETWEEN 440 AND 449 THEN true
               WHEN c.din276_code BETWEEN 450 AND 459 THEN true
               WHEN c.din276_code BETWEEN 460 AND 469 THEN true
               WHEN c.din276_code BETWEEN 480 AND 489 THEN true
               WHEN c.din276_code BETWEEN 534 AND 538 THEN true
               WHEN c.din276_code BETWEEN 541 AND 543 THEN true
               WHEN c.din276_code BETWEEN 544 AND 545 THEN true
               WHEN c.din276_code BETWEEN 546 AND 547 THEN true
               WHEN c.din276_code IN (551, 576)       THEN true
               ELSE false
          END;


INSERT INTO lcc.costs (version_id, grouping, din276_code, label, headline, project_id, ident)
    SELECT
        (SELECT id FROM lcc.versions WHERE name = 'Preisstand 2015') AS version_id,
        'KGU' || (t.din_code / 100)::int * 100 AS grouping,
        t.din_code AS din276_code,
        t.name AS label,
        null AS headline,
        null AS project_id,
        null AS ident
    FROM elca.element_types_v t
    WHERE t.level = 3
    ORDER BY t.lft;

INSERT INTO lcc.irregular_costs (cost_id, life_time)
    SELECT c.id AS cost_id,
           (CASE
            WHEN c.din276_code BETWEEN 420 AND 469 THEN 25
            WHEN c.din276_code BETWEEN 480 AND 489 THEN 10
            ELSE 50
            END) AS life_time

    FROM lcc.costs c
    WHERE c.grouping ILIKE 'KGU%'
          AND c.version_id = (SELECT id FROM lcc.versions WHERE calc_method = 1 ORDER BY id DESC LIMIT 1);



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
                      JOIN lcc.project_costs_v pc ON t.din_code = pc.din276_code
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

COMMIT;
