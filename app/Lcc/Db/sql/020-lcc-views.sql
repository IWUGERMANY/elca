----------------------------------------------------------------------------------------
-- This file is part of the eLCA project
--
-- eLCA
-- A web based life cycle assessment application
--
-- Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
--               BEIBOB Medienfreunde GbR - http://beibob.de/
--
-- eLCA is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- eLCA is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with eLCA. If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------------------------
SET search_path = lcc, public;

BEGIN;
-------------------------------------------------------------------------------

DROP VIEW IF EXISTS lcc.regular_costs_v;
CREATE VIEW lcc.regular_costs_v AS
   SELECT c.*
        , r.*
     FROM lcc.costs c
     JOIN lcc.regular_costs r ON c.id = r.cost_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS lcc.regular_service_costs_v;
CREATE VIEW lcc.regular_service_costs_v AS
   SELECT c.*
        , r.*
     FROM lcc.costs c
     JOIN lcc.regular_service_costs r ON c.id = r.cost_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS lcc.irregular_costs_v;
CREATE VIEW lcc.irregular_costs_v AS
   SELECT c.*
        , i.*
     FROM lcc.costs c
     JOIN lcc.irregular_costs i ON c.id = i.cost_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS lcc.project_costs_v;
CREATE VIEW lcc.project_costs_v AS
       SELECT c.*
            , p.quantity
            , p.ref_value
            , p.project_variant_id
            , p.calc_method
         FROM lcc.costs c
    LEFT JOIN lcc.project_costs p ON c.id = p.cost_id;

-------------------------------------------------------------------------------

DROP VIEW IF EXISTS lcc.project_costs_all_v;
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

-------------------------------------------------------------------------------

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

DROP VIEW IF  EXISTS lcc.element_component_cost_progressions_v;
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

-------------------------------------------------------------------------------

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

-------------------------------------------------------------------------------

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

-------------------------------------------------------------------------------
COMMIT;
