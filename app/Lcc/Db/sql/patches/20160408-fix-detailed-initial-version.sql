BEGIN;
SELECT public.register_patch('fix-detailed-initial-version', 'lcc');

DELETE FROM lcc.versions WHERE calc_method = 1;

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
            ELSE 0
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
            ELSE 0
            END ) / 100 AS maintenance_perc

    FROM lcc.costs c
    WHERE c.grouping ILIKE 'KGR%'
          AND c.version_id = (SELECT id FROM lcc.versions WHERE calc_method = 1 ORDER BY id DESC LIMIT 1);



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


COMMIT;