BEGIN;
SELECT public.register_patch('fix-replacement-calculation', 'lcc');

DROP FUNCTION IF EXISTS lcc.update_element_component_cost_progressions(int);
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
        coalesce(c.quantity, 0) * (1 + v.common_price_inc) ^ iteration / (1 + v.rate) ^ iteration
    FROM
        lcc.element_component_costs_v c
        JOIN elca.elements e ON e.id = c.element_id
        JOIN lcc.project_versions pv  ON pv.project_variant_id = e.project_variant_id AND pv.calc_method = 1
        JOIN lcc.versions v ON v.id = pv.version_id
        CROSS JOIN generate_series(0, 49) AS iteration
    WHERE e.project_variant_id = $1
          AND iteration % c.life_time = 0;

$$ LANGUAGE SQL;


COMMIT;