BEGIN;
SELECT public.register_patch('add-composite-element-costs-view', 'lcc');

DROP VIEW IF  EXISTS lcc.composite_element_cost_progressions_v;
CREATE VIEW lcc.composite_element_cost_progressions_v AS
    SELECT c.composite_element_id
        , c.element_id
        , c.position
        , e.name
        , ec.life_time
        , ec.quantity
    FROM elca.composite_elements c
        JOIN elca.elements e ON e.id = c.element_id
        LEFT JOIN lcc.element_cost_progressions ec ON c.element_id = ec.element_id;

COMMIT;