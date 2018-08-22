BEGIN;
SELECT public.register_patch('add-lcc-construction', 'lcc');


CREATE TABLE lcc.element_costs
(
      "element_id"                 int     NOT NULL                 -- elementId
    , "quantity"                   numeric                          -- quantity
    , "life_time"                  int                              -- lifeTime
    , "calculated_quantity"        numeric                          -- calculated quantity
    , PRIMARY KEY ("element_id")
    , FOREIGN KEY ("element_id") REFERENCES elca.elements ("id") ON DELETE CASCADE
);


CREATE TABLE lcc.element_component_costs
(
      "element_component_id"       int     NOT NULL                 -- elementComponentId
    , "quantity"                   numeric NOT NULL                 -- quantity
    , PRIMARY KEY ("element_component_id")
    , FOREIGN KEY ("element_component_id") REFERENCES elca.element_components ("id") ON DELETE CASCADE
);

DROP VIEW IF EXISTS lcc.element_costs_v;
CREATE VIEW lcc.element_costs_v AS
    SELECT c.*
        , e.project_variant_id
        , e.element_type_node_id
        , e.quantity AS element_quantity
        , e.ref_unit
        , e.is_composite
    FROM lcc.element_costs c
        JOIN elca.elements e ON e.id = c.element_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS lcc.element_composite_costs_v;
CREATE VIEW lcc.element_composite_costs_v AS
    SELECT c.*
        , e.composite_element_id
    FROM lcc.element_costs c
        JOIN elca.composite_elements e ON e.element_id = c.element_id;


-------------------------------------------------------------------------------

DROP VIEW IF EXISTS lcc.element_component_costs_v;
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

-------------------------------------------------------------------------------

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
COMMIT;