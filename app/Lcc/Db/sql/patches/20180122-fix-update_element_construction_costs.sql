BEGIN;
SELECT
    public.register_patch('20180122-fix-update_element_construction_costs.sql', 'lcc');

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

COMMIT;